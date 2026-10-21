<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\EmailVerificationController;
use App\Http\Controllers\Api\ExerciseController;
use App\Http\Controllers\Api\FriendController;
use App\Http\Controllers\Api\MonthlyStatsController;
use App\Http\Controllers\Api\MuscleStatsController;
use App\Http\Controllers\Api\PasswordResetController;
use App\Http\Controllers\Api\PersonalRecordController;
use App\Http\Controllers\Api\PreviousSetsController;
use App\Http\Controllers\Api\ProfileStatsController;
use App\Http\Controllers\Api\UserAvatarController;
use App\Http\Controllers\Api\UserProfileController;
use App\Http\Controllers\Api\WorkoutController;
use App\Http\Controllers\Api\WorkoutExerciseController;
use App\Http\Controllers\Api\WorkoutSetController;
use App\Http\Controllers\Api\WorkoutTemplateController;
use App\Http\Middleware\EnsureUserHasUsername;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware('throttle:auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/refresh', [AuthController::class, 'refresh']);
    Route::post('/forgot-password', [PasswordResetController::class, 'sendLink']);
    Route::post('/reset-password', [PasswordResetController::class, 'reset']);
    // The link from the confirmation email creates the account; its signature is checked in the controller
    Route::get('/email/verify/{id}/{hash}', [EmailVerificationController::class, 'verify'])
        ->name('verification.verify');
});

// Every account here is confirmed: it is only created once the link in the email is opened
Route::middleware(['auth:sanctum', 'throttle:api'])->group(function () {

    Route::post('/logout', [AuthController::class, 'logout']);

    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    Route::patch('/user/avatar', [UserAvatarController::class, 'update']);
    Route::patch('/user/name', [UserProfileController::class, 'updateName']);
    Route::patch('/user/username', [UserProfileController::class, 'setUsername']);

    // Friends find each other by username, so these wait until the account has one
    Route::middleware(EnsureUserHasUsername::class)->group(function () {
        Route::get('users/search', [FriendController::class, 'search']);
        Route::get('friends', [FriendController::class, 'index']);
        Route::get('friends/{user:username}', [FriendController::class, 'show']);
        Route::delete('friends/{user:username}', [FriendController::class, 'destroy']);
        Route::get('friend-requests', [FriendController::class, 'requests']);
        Route::post('friend-requests', [FriendController::class, 'sendRequest']);
        Route::post('friend-requests/{friendship}/accept', [FriendController::class, 'acceptRequest']);
        Route::delete('friend-requests/{friendship}', [FriendController::class, 'deleteRequest']);
    });

    Route::apiResource('exercises', ExerciseController::class);
    Route::apiResource('categories', CategoryController::class);
    Route::get('personal-records', [PersonalRecordController::class, 'index']);
    Route::get('muscle-stats', [MuscleStatsController::class, 'index']);
    Route::get('profile-stats', [ProfileStatsController::class, 'show']);
    Route::get('monthly-stats', [MonthlyStatsController::class, 'index']);
    Route::apiResource('workout-templates', WorkoutTemplateController::class)
        ->only(['index', 'store', 'show', 'destroy'])
        ->parameters(['workout-templates' => 'template']);
    Route::post('workouts/{workout}/template', [WorkoutTemplateController::class, 'fromWorkout']);
    Route::get('workouts/{workout}/previous-sets', PreviousSetsController::class);
    Route::post('workouts/{workout}/finish', [WorkoutController::class, 'finish']);
    Route::post('workouts/{workout}/resume', [WorkoutController::class, 'resume']);
    Route::post('workouts/{workout}/complete', [WorkoutController::class, 'complete']);
    Route::apiResource('workouts', WorkoutController::class);
    Route::put('workouts/{workout}/exercise-order', [WorkoutExerciseController::class, 'reorder']);

    Route::apiResource('workouts.exercises', WorkoutExerciseController::class
    )->parameters([
        'exercises' => 'workoutExercise',
    ])->scoped();

    Route::apiResource('workouts.exercises.sets', WorkoutSetController::class
    )->parameters([
        'exercises' => 'workoutExercise',
        'sets' => 'workoutSet',
    ])->scoped();
});
