<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\MonthlyStatResource;
use App\Models\Workout;
use App\Services\MonthlyStatsService;

class MonthlyStatsController extends Controller
{
    public function __construct(protected MonthlyStatsService $service) {}

    public function index()
    {
        // Stats are derived from the caller's own workouts only
        $this->authorize('viewAny', Workout::class);
        return MonthlyStatResource::collection($this->service->getForUser(auth()->id()));
    }
}
