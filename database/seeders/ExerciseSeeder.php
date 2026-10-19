<?php

namespace Database\Seeders;

use App\Enums\ExerciseUnit;
use App\Models\Category;
use App\Models\Exercise;
use Illuminate\Database\Seeder;

class ExerciseSeeder extends Seeder
{
    /** Held for time instead of lifted, so they are logged in minutes like the cardio ones. */
    private const TIMED = ['Plank'];

    /**
     * Compound exercises: the muscle groups they work besides their own category, most involved first.
     * An exercise that is not listed is an isolation one.
     */
    private const SECONDARY = [
        'Barbell Bench Press' => ['Triceps', 'Shoulders'],
        'Barbell Incline Bench Press' => ['Triceps', 'Shoulders'],
        'Incline Dumbbell Press' => ['Triceps', 'Shoulders'],
        'Push-Up' => ['Triceps', 'Shoulders'],
        'Machine Chest Press' => ['Triceps', 'Shoulders'],
        'Smith Bench Press' => ['Triceps', 'Shoulders'],
        'Smith Incline Bench Press' => ['Triceps', 'Shoulders'],
        'Smith Decline Bench Press' => ['Triceps', 'Shoulders'],
        'Pull-Up' => ['Biceps'],
        'Lat Pulldown' => ['Biceps'],
        'Barbell Row' => ['Biceps', 'Traps'],
        'Dumbbell Row' => ['Biceps', 'Traps'],
        'Seated Cable Row' => ['Biceps', 'Traps'],
        'Smith Row' => ['Biceps', 'Traps'],
        'T-Bar Row' => ['Biceps', 'Traps'],
        'Deadlift' => ['Legs', 'Traps', 'Forearms'],
        'Smith Deadlift' => ['Legs', 'Traps', 'Forearms'],
        'Good Morning' => ['Legs'],
        'Smith Good Morning' => ['Legs'],
        'Overhead Press' => ['Triceps'],
        'Dumbbell Shoulder Press' => ['Triceps'],
        'Smith Overhead Press' => ['Triceps'],
        'Upright Row' => ['Traps'],
        'Smith Upright Row' => ['Traps'],
        'Face Pull' => ['Shoulders'],
        'Dips' => ['Chest', 'Shoulders'],
        'Close-Grip Bench Press' => ['Chest', 'Shoulders'],
        'Smith Close-Grip Bench Press' => ['Chest', 'Shoulders'],
        "Farmer's Walk" => ['Traps'],
        'Barbell Squat' => ['Abs'],
        'Leg Press' => ['Abs'],
        'Lunge' => ['Abs'],
        'Bulgarian Split Squat' => ['Abs'],
        'Smith Squat' => ['Abs'],
        'Smith Front Squat' => ['Abs'],
        'Smith Lunge' => ['Abs'],
        'Smith Bulgarian Split Squat' => ['Abs'],
        'Romanian Deadlift (RDLs)' => ['Back'],
        'Smith Romanian Deadlift' => ['Back'],
        'Reverse Preacher Curl' => ['Forearms'],
        'Seated Reverse Preacher Curl' => ['Forearms'],
    ];

    public function run(): void
    {
        $exercises = [
            'Chest' => ['Barbell Bench Press', 'Barbell Incline Bench Press', 'Incline Dumbbell Press', 'Dumbbell Fly', 'Push-Up', 'Machine Chest Press', 'Cable Fly', 'Smith Bench Press', 'Smith Incline Bench Press', 'Smith Decline Bench Press'],
            'Back' => ['Pull-Up', 'Barbell Row', 'Dumbbell Row', 'Lat Pulldown', 'Lat Pullover', 'Seated Cable Row', 'T-Bar Row', 'Deadlift', 'Back Extension', 'Good Morning', 'Superman', 'Smith Row', 'Smith Deadlift', 'Smith Good Morning'],
            'Shoulders' => ['Overhead Press', 'Lateral Raise', 'Bent Arm Lateral Raise', 'Front Raise', 'Dumbbell Shoulder Press', 'Reverse Fly', 'Upright Row', 'Smith Overhead Press', 'Smith Upright Row'],
            'Traps' => ['Barbell Shrug', 'Dumbbell Shrug', 'Face Pull', 'Smith Shrug'],
            'Biceps' => ['Barbell Curl', 'Dumbbell Curl', 'Hammer Curl', 'Preacher Curl', 'Seated Preacher Curl', 'Reverse Preacher Curl', 'Seated Reverse Preacher Curl', 'Cable Curl', 'Smith Drag Curl'],
            'Triceps' => ['Cable Pushdown', 'Close-Grip Bench Press', 'Overhead Dumbbell Extension', 'Dips', 'Skull Crusher', 'Smith Close-Grip Bench Press'],
            'Forearms' => ['Wrist Curl', 'Wrist Extension', "Farmer's Walk"],
            'Abs' => ['Crunch', 'Hanging Leg Raise', 'Plank', 'Ab Wheel Rollout', 'Russian Twist'],
            'Legs' => ['Barbell Squat', 'Leg Press', 'Lunge', 'Leg Extension', 'Leg Curl', 'Hip Adduction', 'Hip Abduction', 'Romanian Deadlift (RDLs)', 'Hip Thrust', 'Banded Lateral Walk', 'Cable Kickback', 'Bulgarian Split Squat', 'Standing Calf Raise', 'Seated Calf Raise', 'Leg Press Calf Raise', 'Smith Squat', 'Smith Front Squat', 'Smith Lunge', 'Smith Bulgarian Split Squat', 'Smith Romanian Deadlift', 'Smith Hip Thrust', 'Smith Calf Raise'],
            Category::CARDIO => ['Treadmill Run', 'Treadmill Walk', 'Stationary Bike', 'Elliptical', 'Rowing Machine', 'Stair Climber', 'Jump Rope', 'Outdoor Run', 'Outdoor Cycling', 'Swimming'],
        ];

        // Every category first: a secondary muscle group can be one that comes later in the list
        $categoryIds = [];
        foreach (array_keys($exercises) as $categoryName) {
            $categoryIds[$categoryName] = Category::firstOrCreate(['name' => $categoryName])->id;
        }

        foreach ($exercises as $categoryName => $names) {
            foreach ($names as $name) {
                $timed = $categoryName === Category::CARDIO || in_array($name, self::TIMED, true);

                // user_id null = the shared catalog; a user's own exercise with the same name is left alone
                $exercise = Exercise::updateOrCreate(
                    ['name' => $name, 'category_id' => $categoryIds[$categoryName], 'user_id' => null],
                    ['unit' => $timed ? ExerciseUnit::Minutes : ExerciseUnit::Kilograms],
                );

                // Synced for every exercise, so one taken off the list above loses its groups too
                $exercise->secondaryCategories()->sync(
                    array_map(fn ($secondary) => $categoryIds[$secondary], self::SECONDARY[$name] ?? [])
                );
            }
        }
    }
}
