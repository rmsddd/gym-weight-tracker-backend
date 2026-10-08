<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\ExerciseController;
use App\Http\Controllers\Api\PersonalRecordController;
use App\Http\Controllers\Api\UserAvatarController;
use App\Http\Controllers\Api\WorkoutController;
use App\Http\Controllers\Api\WorkoutExerciseController;
use App\Http\Controllers\Api\WorkoutSetController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);


Route::post('/refresh', [AuthController::class, 'refresh']);

Route::middleware('auth:sanctum')->group(function () {

    Route::post('/logout', [AuthController::class, 'logout']);

    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    Route::patch('/user/avatar', [UserAvatarController::class, 'update']);

    Route::apiResource('exercises', ExerciseController::class);
    Route::apiResource('categories', CategoryController::class);
    Route::get('personal-records', [PersonalRecordController::class, 'index']);
    Route::post('workouts/{workout}/finish', [WorkoutController::class, 'finish']);
    Route::apiResource('workouts', WorkoutController::class);

    Route::apiResource('workouts.exercises', WorkoutExerciseController::class
    )->parameters([
        'exercises' => 'workoutExercise'
    ])->scoped();

    Route::apiResource('workouts.exercises.sets', WorkoutSetController::class
    )->parameters([
        'exercises' => 'workoutExercise',
        'sets' => 'workoutSet'
    ])->scoped();
});
