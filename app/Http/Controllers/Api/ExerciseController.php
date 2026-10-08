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
        $this->authorize('viewAny', Exercise::class);
        $exercises = $this->service->getAll(auth()->id());
        return ExerciseResource::collection($exercises);
    }

    public function store(StoreExerciseRequest $request)
    {
        $this->authorize('create', Exercise::class);
        $data = $request->validated();
        $data['user_id'] = $request->user()->id;
        $exercise = $this->service->create($data);
        return new ExerciseResource($exercise);
    }

    public function show(Exercise $exercise)
    {
        $this->authorize('view', $exercise);
        $exercise->load('category');
        return new ExerciseResource($exercise);
    }

    public function update(UpdateExerciseRequest $request, Exercise $exercise)
    {
        $this->authorize('update', $exercise);
        $updated = $this->service->update($exercise, $request->validated());
        return new ExerciseResource($updated);
    }

    public function destroy(Exercise $exercise)
    {
        $this->authorize('delete', $exercise);
        $this->service->delete($exercise);
        return response()->json(null, 204);
    }
}
