<?php

namespace App\Services;

use App\Enums\FriendshipStatus;
use App\Models\Friendship;
use App\Models\User;
use App\Models\Workout;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class FriendshipService
{
    private const SEARCH_LIMIT = 20;

    private const WORKOUT_DETAIL = ['workoutExercises.exercise.category', 'workoutExercises.sets'];

    /**
     * Users whose username contains the query, those starting with it first. Each one carries the
     * row it shares with the searcher, if any, so the result can say Add, Sent, Accept or Friends.
     */
    public function search(User $user, string $query): Collection
    {
        $query = mb_strtolower($query);
        // _ is allowed in usernames but is a LIKE wildcard; "!" escapes the same in MySQL and SQLite
        $escaped = str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $query);

        $results = User::whereNotNull('username')
            ->where('id', '!=', $user->id)
            ->whereRaw("username LIKE ? ESCAPE '!'", ["%{$escaped}%"])
            ->orderByRaw("CASE WHEN username LIKE ? ESCAPE '!' THEN 0 ELSE 1 END", ["{$escaped}%"])
            ->orderBy('username')
            ->limit(self::SEARCH_LIMIT)
            ->get();

        $rows = Friendship::involving($user->id)->get()->keyBy(fn (Friendship $f) => $f->otherThan($user->id));

        return $results->each(fn (User $result) => $result->setRelation('friendship', $rows->get($result->id)));
    }

    /**
     * Sends a request, or accepts the one the other person already sent: two people asking each
     * other mean they both want it.
     */
    public function sendRequest(User $user, User $other): Friendship
    {
        if ($other->id === $user->id) {
            throw ValidationException::withMessages(['user_id' => 'You cannot add yourself.']);
        }

        return DB::transaction(function () use ($user, $other) {
            $existing = Friendship::between($user->id, $other->id)->lockForUpdate()->first();

            if ($existing === null) {
                return Friendship::create(['requester_id' => $user->id, 'addressee_id' => $other->id]);
            }

            if ($existing->isPending() && $existing->addressee_id === $user->id) {
                return $this->accept($existing);
            }

            throw ValidationException::withMessages([
                'user_id' => $existing->isPending() ? 'You already sent a request.' : 'You are already friends.',
            ]);
        });
    }

    public function accept(Friendship $friendship): Friendship
    {
        $friendship->update(['status' => FriendshipStatus::Accepted]);

        return $friendship->fresh();
    }

    /** Declining, cancelling and unfriending all remove the row; nobody is told which it was. */
    public function remove(Friendship $friendship): void
    {
        $friendship->delete();
    }

    public function friendshipWith(User $user, User $other): ?Friendship
    {
        return Friendship::between($user->id, $other->id)->first();
    }

    /** Requests waiting on the user, and those the user sent that are still waiting. */
    public function pendingRequests(User $user): array
    {
        $pending = Friendship::involving($user->id)
            ->where('status', FriendshipStatus::Pending)
            ->with('requester', 'addressee')
            ->latest()
            ->get();

        return [
            'incoming' => $pending->where('addressee_id', $user->id)->values(),
            'outgoing' => $pending->where('requester_id', $user->id)->values(),
        ];
    }

    /** The user's friends, each with the workout that makes them online (if any) as `live_workout`. */
    public function friends(User $user): Collection
    {
        $friends = User::whereIn('id', $user->friendIds())->get();

        $live = Workout::live()
            ->whereIn('user_id', $friends->modelKeys())
            ->get()
            ->unique('user_id')
            ->keyBy('user_id');

        return $friends
            ->each(fn (User $friend) => $friend->setRelation('liveWorkout', $live->get($friend->id)))
            ->sortBy(fn (User $friend) => [$friend->liveWorkout === null, mb_strtolower($friend->name)])
            ->values();
    }

    public function liveWorkout(User $user): ?Workout
    {
        return $user->workouts()->live()->with(self::WORKOUT_DETAIL)->first();
    }

    /** Every workout of the friend except the one shown live above them, newest first. */
    public function history(User $friend, ?Workout $live): Collection
    {
        return $friend->workouts()
            ->when($live, fn ($q) => $q->where('id', '!=', $live->id))
            ->with(self::WORKOUT_DETAIL)
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->get();
    }
}
