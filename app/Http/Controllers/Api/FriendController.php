<?php

namespace App\Http\Controllers\Api;

use App\Enums\FriendshipStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreFriendRequestRequest;
use App\Http\Resources\FriendRequestResource;
use App\Http\Resources\FriendResource;
use App\Http\Resources\FriendWorkoutResource;
use App\Http\Resources\PublicUserResource;
use App\Http\Resources\UserSearchResultResource;
use App\Models\Friendship;
use App\Models\User;
use App\Services\FriendshipService;
use Illuminate\Http\Request;

class FriendController extends Controller
{
    public function __construct(protected FriendshipService $service) {}

    public function search(Request $request)
    {
        $this->authorize('searchUsers', User::class);
        $query = $request->validate(['q' => 'required|string|min:2|max:20'])['q'];

        return UserSearchResultResource::collection($this->service->search($request->user(), $query));
    }

    public function index(Request $request)
    {
        $this->authorize('viewFriends', User::class);

        return FriendResource::collection($this->service->friends($request->user()));
    }

    /** A friend's page: who they are, the workout they are on right now (if any) and their history. */
    public function show(User $user)
    {
        $this->authorize('viewWorkouts', $user);
        $live = $this->service->liveWorkout($user);

        return response()->json(['data' => [
            'user' => new PublicUserResource($user),
            'online' => $live !== null,
            'live_workout' => $live ? new FriendWorkoutResource($live) : null,
            'history' => FriendWorkoutResource::collection($this->service->history($user, $live)),
        ]]);
    }

    public function destroy(Request $request, User $user)
    {
        $this->authorize('removeFriend', User::class);
        $friendship = $this->service->friendshipWith($request->user(), $user);
        abort_if($friendship?->status !== FriendshipStatus::Accepted, 404);
        $this->service->remove($friendship);

        return response()->json(null, 204);
    }

    public function requests(Request $request)
    {
        $this->authorize('viewFriendRequests', User::class);
        $pending = $this->service->pendingRequests($request->user());

        return response()->json(['data' => [
            'incoming' => FriendRequestResource::collection($pending['incoming']),
            'outgoing' => FriendRequestResource::collection($pending['outgoing']),
        ]]);
    }

    public function sendRequest(StoreFriendRequestRequest $request)
    {
        $this->authorize('sendFriendRequest', User::class);
        $other = User::findOrFail($request->validated('user_id'));
        abort_if($other->username === null, 404);

        $friendship = $this->service->sendRequest($request->user(), $other);

        return response()->json(['data' => [
            'id' => $friendship->id,
            'status' => $friendship->status->value,
        ]], 201);
    }

    public function acceptRequest(Friendship $friendship)
    {
        $this->authorize('accept', $friendship);
        $this->service->accept($friendship);

        return response()->json(null, 204);
    }

    /** Declines a request sent to the user, or cancels one the user sent. */
    public function deleteRequest(Friendship $friendship)
    {
        $this->authorize('delete', $friendship);
        $this->service->remove($friendship);

        return response()->json(null, 204);
    }
}
