<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProfileStatsResource;
use App\Models\Workout;
use App\Services\ProfileStatsService;

class ProfileStatsController extends Controller
{
    public function __construct(protected ProfileStatsService $service) {}

    public function show()
    {
        // Totals are derived from the caller's own workouts only
        $this->authorize('viewAny', Workout::class);
        return new ProfileStatsResource($this->service->getForUser(auth()->id()));
    }
}
