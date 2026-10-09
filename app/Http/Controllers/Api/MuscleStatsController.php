<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\MuscleStatResource;
use App\Models\Workout;
use App\Services\MuscleStatsService;

class MuscleStatsController extends Controller
{
    public function __construct(protected MuscleStatsService $service) {}

    public function index()
    {
        // Stats are derived from the caller's own workouts only
        $this->authorize('viewAny', Workout::class);
        return MuscleStatResource::collection($this->service->getForUser(auth()->id()));
    }
}
