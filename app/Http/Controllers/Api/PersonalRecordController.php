<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\PersonalRecordResource;
use App\Models\Workout;
use App\Services\PersonalRecordService;

class PersonalRecordController extends Controller
{
    public function __construct(protected PersonalRecordService $service) {}

    public function index()
    {
        // Records are derived from the caller's own workouts only
        $this->authorize('viewAny', Workout::class);
        return PersonalRecordResource::collection($this->service->getForUser(auth()->id()));
    }
}
