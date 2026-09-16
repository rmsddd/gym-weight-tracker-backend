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
        $workouts=$this->service->getAll(auth()->id());
        return WorkoutResource::collection($workouts);
    }
    public function store(StoreWorkoutRequest $request)
    {
        $data = $request->validated();
        $data['user_id'] = $request->user()->id;
        $workout=$this->service->create($data);
        return new WorkoutResource($workout);
    }

    public function show(Workout $workout)
    {
        abort_if($workout->user_id !== auth()->id(), 403);
        return new WorkoutResource($workout);
    }
    public function update(UpdateWorkoutRequest $request, Workout $workout){
        abort_if($workout->user_id !== auth()->id(), 403);
        $updated=$this->service->update($workout,$request->validated());
        return new WorkoutResource($updated);
    }
    public function destroy(Workout $workout)
    {
        abort_if($workout->user_id !== auth()->id(), 403);
        $this->service->delete($workout);
        return response()->json(null, 204);
    }
}
