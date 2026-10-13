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

    public function run(): void
    {
        $exercises = [
            'Chest' => ['Barbell Bench Press', 'Barbell Incline Bench Press', 'Incline Dumbbell Press', 'Dumbbell Fly', 'Push-Up', 'Machine Chest Press', 'Cable Fly', 'Smith Bench Press', 'Smith Incline Bench Press', 'Smith Decline Bench Press'],
            'Back' => ['Pull-Up', 'Barbell Row', 'Dumbbell Row', 'Lat Pulldown', 'Lat Pullover', 'Seated Cable Row', 'Deadlift', 'Back Extension', 'Good Morning', 'Superman', 'Smith Row', 'Smith Deadlift', 'Smith Good Morning'],
            'Shoulders' => ['Overhead Press', 'Lateral Raise', 'Bent Arm Lateral Raise', 'Front Raise', 'Dumbbell Shoulder Press', 'Reverse Fly', 'Upright Row', 'Smith Overhead Press', 'Smith Upright Row'],
            'Traps' => ['Barbell Shrug', 'Dumbbell Shrug', 'Face Pull', 'Smith Shrug'],
            'Biceps' => ['Barbell Curl', 'Dumbbell Curl', 'Hammer Curl', 'Preacher Curl', 'Seated Preacher Curl', 'Cable Curl', 'Smith Drag Curl'],
            'Triceps' => ['Cable Pushdown', 'Close-Grip Bench Press', 'Overhead Dumbbell Extension', 'Dips', 'Skull Crusher', 'Smith Close-Grip Bench Press'],
            'Forearms' => ['Wrist Curl', 'Wrist Extension', "Farmer's Walk"],
            'Abs' => ['Crunch', 'Hanging Leg Raise', 'Plank', 'Ab Wheel Rollout', 'Russian Twist'],
            'Legs' => ['Barbell Squat', 'Leg Press', 'Lunge', 'Leg Extension', 'Leg Curl', 'Hip Adduction', 'Hip Abduction', 'Romanian Deadlift (RDLs)', 'Hip Thrust', 'Banded Lateral Walk', 'Cable Kickback', 'Bulgarian Split Squat', 'Standing Calf Raise', 'Seated Calf Raise', 'Leg Press Calf Raise', 'Smith Squat', 'Smith Front Squat', 'Smith Lunge', 'Smith Bulgarian Split Squat', 'Smith Romanian Deadlift', 'Smith Hip Thrust', 'Smith Calf Raise'],
            Category::CARDIO => ['Treadmill Run', 'Treadmill Walk', 'Stationary Bike', 'Elliptical', 'Rowing Machine', 'Stair Climber', 'Jump Rope', 'Outdoor Run', 'Outdoor Cycling', 'Swimming'],
        ];

        foreach ($exercises as $categoryName => $names) {
            $category = Category::firstOrCreate(['name' => $categoryName]);

            foreach ($names as $name) {
                $timed = $categoryName === Category::CARDIO || in_array($name, self::TIMED, true);

                // user_id null = the shared catalog; a user's own exercise with the same name is left alone
                Exercise::updateOrCreate(
                    ['name' => $name, 'category_id' => $category->id, 'user_id' => null],
                    ['unit' => $timed ? ExerciseUnit::Minutes : ExerciseUnit::Kilograms],
                );
            }
        }
    }
}
