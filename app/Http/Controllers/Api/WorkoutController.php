<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreWorkoutRequest;
use App\Http\Requests\UpdateWorkoutRequest;
use App\Http\Resources\WorkoutResource;
use App\Models\Workout;
use App\Services\WorkoutService;

class WorkoutController extends Controller
{
    public function __construct(protected WorkoutService $service) {}

    public function index()
    {
        $this->authorize('viewAny', Workout::class);
        $workouts=$this->service->getAll(auth()->id());
        return WorkoutResource::collection($workouts);
    }

    public function store(StoreWorkoutRequest $request)
    {
        $this->authorize('create', Workout::class);
        $data = $request->validated();
        $data['user_id'] = $request->user()->id;
        $workout=$this->service->create($data);
        return new WorkoutResource($workout);
    }

    public function show(Workout $workout)
    {
        $this->authorize('view', $workout);
        return new WorkoutResource($workout);
    }

    public function update(UpdateWorkoutRequest $request, Workout $workout){
        $this->authorize('update', $workout);
        $updated=$this->service->update($workout,$request->validated());
        return new WorkoutResource($updated);
    }

    public function finish(Workout $workout)
    {
        $this->authorize('finish', $workout);
        return new WorkoutResource($this->service->finish($workout));
    }

    public function destroy(Workout $workout)
    {
        $this->authorize('delete', $workout);
        $this->service->delete($workout);
        return response()->json(null, 204);
    }
}
