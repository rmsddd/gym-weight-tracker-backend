<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Workout;
use App\Services\PreviousSetsService;

class PreviousSetsController extends Controller
{
    public function __construct(protected PreviousSetsService $service) {}

    public function __invoke(Workout $workout)
    {
        $this->authorize('view', $workout);
        return response()->json(['data' => (object) $this->service->forWorkout($workout)]);
    }
}
