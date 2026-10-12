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
            'Chest' => ['Barbell Bench Press', 'Incline Dumbbell Press', 'Dumbbell Fly', 'Push-Up', 'Machine Chest Press', 'Cable Fly'],
            'Back' => ['Pull-Up', 'Barbell Row', 'Dumbbell Row', 'Lat Pulldown', 'Seated Cable Row', 'Deadlift', 'Back Extension', 'Good Morning', 'Superman'],
            'Shoulders' => ['Overhead Press', 'Lateral Raise', 'Front Raise', 'Dumbbell Shoulder Press', 'Reverse Fly'],
            'Traps' => ['Barbell Shrug', 'Dumbbell Shrug', 'Face Pull'],
            'Biceps' => ['Barbell Curl', 'Dumbbell Curl', 'Hammer Curl', 'Preacher Curl', 'Cable Curl'],
            'Triceps' => ['Cable Pushdown', 'Close-Grip Bench Press', 'Overhead Dumbbell Extension', 'Dips', 'Skull Crusher'],
            'Forearms' => ['Wrist Curl', 'Wrist Extension', "Farmer's Walk"],
            'Abs' => ['Crunch', 'Hanging Leg Raise', 'Plank', 'Ab Wheel Rollout', 'Russian Twist'],
            'Legs' => ['Barbell Squat', 'Leg Press', 'Lunge', 'Leg Extension', 'Leg Curl', 'Romanian Deadlift', 'Hip Thrust', 'Banded Lateral Walk', 'Cable Kickback', 'Bulgarian Split Squat', 'Standing Calf Raise', 'Seated Calf Raise', 'Leg Press Calf Raise'],
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
