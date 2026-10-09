<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\SaveWorkoutAsTemplateRequest;
use App\Http\Requests\StoreWorkoutTemplateRequest;
use App\Http\Resources\WorkoutTemplateResource;
use App\Models\Workout;
use App\Models\WorkoutTemplate;
use App\Services\WorkoutTemplateService;

class WorkoutTemplateController extends Controller
{
    public function __construct(protected WorkoutTemplateService $service) {}

    public function index()
    {
        $this->authorize('viewAny', WorkoutTemplate::class);
        return WorkoutTemplateResource::collection($this->service->getAll(auth()->id()));
    }

    public function store(StoreWorkoutTemplateRequest $request)
    {
        $this->authorize('create', WorkoutTemplate::class);
        $data = $request->validated();
        $template = $this->service->create($request->user()->id, $data['name'], $data['exercise_ids']);
        return new WorkoutTemplateResource($template);
    }

    public function show(WorkoutTemplate $template)
    {
        $this->authorize('view', $template);
        return new WorkoutTemplateResource($template->load('exercises.exercise.category'));
    }

    public function fromWorkout(SaveWorkoutAsTemplateRequest $request, Workout $workout)
    {
        $this->authorize('view', $workout);
        $this->authorize('create', WorkoutTemplate::class);
        $template = $this->service->createFromWorkout($workout, $request->validated()['name']);
        return new WorkoutTemplateResource($template);
    }

    public function destroy(WorkoutTemplate $template)
    {
        $this->authorize('delete', $template);
        $this->service->delete($template);
        return response()->json(null, 204);
    }
}
