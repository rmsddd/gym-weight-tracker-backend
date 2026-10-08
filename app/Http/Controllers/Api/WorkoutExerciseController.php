<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreWorkoutExerciseRequest;
use App\Http\Requests\UpdateWorkoutExerciseRequest;
use App\Http\Resources\WorkoutExerciseResource;
use App\Models\Workout;
use App\Models\WorkoutExercise;
use App\Services\WorkoutExerciseService;

class WorkoutExerciseController extends Controller
{
    public function __construct(protected WorkoutExerciseService $service) {}

    public function index(Workout $workout)
    {
        $this->authorize('viewAny', [WorkoutExercise::class, $workout]);
        $workoutExercises = $this->service->getAllForWorkout($workout);
        return WorkoutExerciseResource::collection($workoutExercises);
    }

    public function store(StoreWorkoutExerciseRequest $request, Workout $workout)
    {
        $this->authorize('create', [WorkoutExercise::class, $workout]);
        $workoutExercise = $this->service->create($workout, $request->validated());
        return new WorkoutExerciseResource($workoutExercise->load(['exercise.category', 'sets']));
    }

    public function show(Workout $workout, WorkoutExercise $workoutExercise)
    {
        $this->authorize('view', $workoutExercise);
        return new WorkoutExerciseResource($workoutExercise->load(['exercise.category', 'sets']));
    }

    public function update(UpdateWorkoutExerciseRequest $request, Workout $workout, WorkoutExercise $workoutExercise)
    {
        $this->authorize('update', $workoutExercise);
        $updated = $this->service->update($workoutExercise, $request->validated());
        return new WorkoutExerciseResource($updated->load(['exercise.category', 'sets']));
    }

    public function destroy(Workout $workout, WorkoutExercise $workoutExercise)
    {
        $this->authorize('delete', $workoutExercise);
        $this->service->delete($workoutExercise);
        return response()->json(null, 204);
    }
}
