<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreWorkoutRequest;
use App\Http\Requests\UpdateWorkoutRequest;
use App\Http\Resources\WorkoutResource;
use App\Models\Workout;
use App\Models\WorkoutTemplate;
use App\Services\WorkoutService;
use Illuminate\Http\Request;

class WorkoutController extends Controller
{
    public function __construct(protected WorkoutService $service) {}

    public function index(Request $request)
    {
        $this->authorize('viewAny', Workout::class);
        $filters = $request->validate([
            'from' => 'nullable|date',
            'to' => 'nullable|date',
            'exercise_id' => 'nullable|integer',
            'category_id' => 'nullable|integer',
            'finished' => 'nullable|boolean',
            'include' => 'nullable|in:exercises',
        ]);
        $filters['include_exercises'] = ($filters['include'] ?? null) === 'exercises';
        $workouts=$this->service->getAll(auth()->id(), $filters);
        return WorkoutResource::collection($workouts);
    }

    public function store(StoreWorkoutRequest $request)
    {
        $this->authorize('create', Workout::class);
        $data = $request->validated();
        $data['user_id'] = $request->user()->id;
        $template = isset($data['template_id']) ? WorkoutTemplate::find($data['template_id']) : null;
        $workout=$this->service->create($data, $template);
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

    public function complete(Workout $workout)
    {
        $this->authorize('complete', $workout);
        return new WorkoutResource($this->service->complete($workout));
    }

    public function resume(Workout $workout)
    {
        $this->authorize('resume', $workout);
        return new WorkoutResource($this->service->resume($workout));
    }

    public function destroy(Workout $workout)
    {
        $this->authorize('delete', $workout);
        $this->service->delete($workout);
        return response()->json(null, 204);
    }
}
