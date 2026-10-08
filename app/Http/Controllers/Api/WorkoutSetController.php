<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreWorkoutSetRequest;
use App\Http\Requests\UpdateWorkoutSetRequest;
use App\Http\Resources\WorkoutSetResource;
use App\Models\Workout;
use App\Models\WorkoutExercise;
use App\Models\WorkoutSet;
use App\Services\WorkoutSetService;

class WorkoutSetController extends Controller
{
    public function __construct(protected WorkoutSetService $service) {}

    public function index(Workout $workout, WorkoutExercise $workoutExercise)
    {
        $this->authorize('viewAny', [WorkoutSet::class, $workoutExercise]);
        $workoutSets = $this->service->getAllForWorkoutExercise($workoutExercise);
        return WorkoutSetResource::collection($workoutSets);
    }

    public function store(StoreWorkoutSetRequest $request, Workout $workout, WorkoutExercise $workoutExercise)
    {
        $this->authorize('create', [WorkoutSet::class, $workoutExercise]);
        $workoutSet = $this->service->create($workoutExercise, $request->validated());
        return new WorkoutSetResource($workoutSet);
    }

    public function show(Workout $workout, WorkoutExercise $workoutExercise, WorkoutSet $workoutSet)
    {
        $this->authorize('view', $workoutSet);
        return new WorkoutSetResource($workoutSet);
    }

    public function update(UpdateWorkoutSetRequest $request, Workout $workout, WorkoutExercise $workoutExercise, WorkoutSet $workoutSet)
    {
        $this->authorize('update', $workoutSet);
        $updated = $this->service->update($workoutSet, $request->validated());
        return new WorkoutSetResource($updated);
    }

    public function destroy(Workout $workout, WorkoutExercise $workoutExercise, WorkoutSet $workoutSet)
    {
        $this->authorize('delete', $workoutSet);
        $this->service->delete($workoutSet);
        return response()->json(null, 204);
    }
}
