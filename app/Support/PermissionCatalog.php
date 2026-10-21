<?php

namespace App\Support;

/**
 * Every permission the app checks, grouped by area for the root page. This is the only list:
 * `permissions:sync` makes the database match it, and a permission only exists if code checks it.
 */
class PermissionCatalog
{
    /** The user's own account: passes every permission check, and is the only one on the admin page. */
    public const ROOT = 'root';

    /** Every account gets it on sign up; it starts with every permission. */
    public const DEFAULT_ROLE = 'sportiv';

    /** Roles the admin page cannot delete or rename. */
    public const PROTECTED_ROLES = [self::ROOT, self::DEFAULT_ROLE];

    public const GROUPS = [
        'workouts' => [
            'label' => 'Workouts',
            'permissions' => [
                'workouts.view' => 'See workouts',
                'workouts.create' => 'Start workouts',
                'workouts.update' => 'Edit workouts and reorder their exercises',
                'workouts.finish' => 'Finish workouts',
                'workouts.resume' => 'Resume workouts',
                'workouts.complete' => 'Complete workouts',
                'workouts.delete' => 'Delete workouts',
            ],
        ],
        'workout-exercises' => [
            'label' => 'Exercises in a workout',
            'permissions' => [
                'workout-exercises.view' => 'See the exercises of a workout',
                'workout-exercises.create' => 'Add exercises to a workout',
                'workout-exercises.update' => 'Edit the exercises of a workout',
                'workout-exercises.delete' => 'Remove exercises from a workout',
            ],
        ],
        'workout-sets' => [
            'label' => 'Sets',
            'permissions' => [
                'workout-sets.view' => 'See sets',
                'workout-sets.create' => 'Add sets',
                'workout-sets.update' => 'Edit sets',
                'workout-sets.delete' => 'Delete sets',
            ],
        ],
        'exercises' => [
            'label' => 'Exercise catalog',
            'permissions' => [
                'exercises.view' => 'See exercises',
                'exercises.create' => 'Create custom exercises',
                'exercises.update' => 'Edit custom exercises',
                'exercises.delete' => 'Delete custom exercises',
            ],
        ],
        'categories' => [
            'label' => 'Categories',
            'permissions' => [
                'categories.view' => 'See categories',
            ],
        ],
        'templates' => [
            'label' => 'Templates',
            'permissions' => [
                'templates.view' => 'See templates',
                'templates.create' => 'Create templates',
                'templates.delete' => 'Delete templates',
            ],
        ],
        'stats' => [
            'label' => 'Stats',
            'permissions' => [
                'stats.view' => 'See stats and personal records',
            ],
        ],
        'friends' => [
            'label' => 'Friends',
            'permissions' => [
                'friends.search' => 'Search for people',
                'friends.view' => 'See friends and their workouts',
                'friends.requests' => 'See friend requests',
                'friends.send-request' => 'Send friend requests',
                'friends.accept-request' => 'Accept friend requests',
                'friends.remove' => 'Remove friends and decline or cancel requests',
            ],
        ],
        'profile' => [
            'label' => 'Profile',
            'permissions' => [
                'profile.update-name' => 'Change their name',
                'profile.update-avatar' => 'Change their avatar',
            ],
        ],
    ];

    /** @return list<string> */
    public static function names(): array
    {
        return array_merge(...array_map(
            fn (array $group) => array_keys($group['permissions']),
            array_values(self::GROUPS),
        ));
    }

    public static function has(string $ability): bool
    {
        return in_array($ability, self::names(), true);
    }
}
