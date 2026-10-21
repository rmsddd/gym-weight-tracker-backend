<?php

namespace Tests\Support;

/**
 * Every logged-in API route and the permission that guards it, with a body that passes validation
 * (a form request validates before the controller checks the permission). Placeholders in braces are
 * filled from PermissionEnforcementTest's fixtures. RouteProtectionTest checks this list against the
 * real routes, so a new route cannot slip by without a permission.
 */
class GuardedRoutes
{
    /** Logged-in routes that deliberately check no permission. */
    public const UNGUARDED = [
        'POST api/logout',
        'GET api/user',
        // Choosing a username: an account without one is held on that page, so it is never denied
        'PATCH api/user/username',
        // The shared categories are read-only for everyone: there is nothing to grant
        'POST api/categories',
        'PUT api/categories/{category}',
        'DELETE api/categories/{category}',
    ];

    /** @return array<string, array{0: string, 1: array}> "METHOD uri" => [permission, body] */
    public static function all(): array
    {
        return [
            'GET api/workouts' => ['workouts.view', []],
            'GET api/workouts/{workout}' => ['workouts.view', []],
            'GET api/workouts/{workout}/previous-sets' => ['workouts.view', []],
            'POST api/workouts' => ['workouts.create', ['date' => '2026-10-21']],
            'PUT api/workouts/{workout}' => ['workouts.update', ['name' => 'Pull Day']],
            'PUT api/workouts/{workout}/exercise-order' => ['workouts.update', ['ids' => ['{workoutExercise}']]],
            'POST api/workouts/{workout}/finish' => ['workouts.finish', []],
            'POST api/workouts/{workout}/resume' => ['workouts.resume', []],
            'POST api/workouts/{workout}/complete' => ['workouts.complete', []],
            'DELETE api/workouts/{workout}' => ['workouts.delete', []],

            'GET api/workouts/{workout}/exercises' => ['workout-exercises.view', []],
            'GET api/workouts/{workout}/exercises/{workoutExercise}' => ['workout-exercises.view', []],
            'POST api/workouts/{workout}/exercises' => ['workout-exercises.create', ['exercise_id' => '{exercise}']],
            'PUT api/workouts/{workout}/exercises/{workoutExercise}' => ['workout-exercises.update', ['exercise_id' => '{exercise}']],
            'DELETE api/workouts/{workout}/exercises/{workoutExercise}' => ['workout-exercises.delete', []],

            'GET api/workouts/{workout}/exercises/{workoutExercise}/sets' => ['workout-sets.view', []],
            'GET api/workouts/{workout}/exercises/{workoutExercise}/sets/{workoutSet}' => ['workout-sets.view', []],
            'POST api/workouts/{workout}/exercises/{workoutExercise}/sets' => ['workout-sets.create', ['weight' => 60, 'reps' => 10]],
            'PUT api/workouts/{workout}/exercises/{workoutExercise}/sets/{workoutSet}' => ['workout-sets.update', ['reps' => 5]],
            'DELETE api/workouts/{workout}/exercises/{workoutExercise}/sets/{workoutSet}' => ['workout-sets.delete', []],

            'GET api/exercises' => ['exercises.view', []],
            'GET api/exercises/{exercise}' => ['exercises.view', []],
            'POST api/exercises' => ['exercises.create', ['name' => 'Cable Fly', 'category_id' => '{category}']],
            'PUT api/exercises/{exercise}' => ['exercises.update', ['name' => 'Cable Fly', 'category_id' => '{category}']],
            'DELETE api/exercises/{exercise}' => ['exercises.delete', []],

            'GET api/categories' => ['categories.view', []],
            'GET api/categories/{category}' => ['categories.view', []],

            'GET api/workout-templates' => ['templates.view', []],
            'GET api/workout-templates/{template}' => ['templates.view', []],
            'POST api/workout-templates' => ['templates.create', ['name' => 'Legs', 'exercise_ids' => ['{exercise}']]],
            'POST api/workouts/{workout}/template' => ['templates.create', ['name' => 'Legs']],
            'DELETE api/workout-templates/{template}' => ['templates.delete', []],

            'GET api/personal-records' => ['stats.view', []],
            'GET api/muscle-stats' => ['stats.view', []],
            'GET api/profile-stats' => ['stats.view', []],
            'GET api/monthly-stats' => ['stats.view', []],

            'GET api/users/search' => ['friends.search', ['q' => 'stranger']],
            'GET api/friends' => ['friends.view', []],
            'GET api/friends/{user}' => ['friends.view', []],
            'GET api/friend-requests' => ['friends.requests', []],
            'POST api/friend-requests' => ['friends.send-request', ['user_id' => '{stranger}']],
            'POST api/friend-requests/{friendship}/accept' => ['friends.accept-request', []],
            'DELETE api/friend-requests/{friendship}' => ['friends.remove', []],
            'DELETE api/friends/{user}' => ['friends.remove', []],

            'PATCH api/user/name' => ['profile.update-name', ['name' => 'New Name']],
            'PATCH api/user/avatar' => ['profile.update-avatar', ['avatar' => 'athlete']],
        ];
    }
}
