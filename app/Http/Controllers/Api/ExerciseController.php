<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreExerciseRequest;
use App\Http\Requests\UpdateExerciseRequest;
use App\Http\Resources\ExerciseResource;
use App\Models\Exercise;
use App\Services\ExerciseService;

class ExerciseController extends Controller
{
    public function __construct(protected ExerciseService $service) {}

    public function index()
    {
        $exercises = $this->service->getAll();
        return ExerciseResource::collection($exercises);
    }

    public function store(StoreExerciseRequest $request)
    {
        $exercise = $this->service->create($request->validated());
        return new ExerciseResource($exercise);
    }

    public function show(Exercise $exercise)
    {
        $exercise->load('category');
        return new ExerciseResource($exercise);
    }

    public function update(UpdateExerciseRequest $request, Exercise $exercise)
    {
        $updated = $this->service->update($exercise, $request->validated());
        return new ExerciseResource($updated);
    }

    public function destroy(Exercise $exercise)
    {
        $this->service->delete($exercise);
        return response()->json(null, 204);
    }
}
