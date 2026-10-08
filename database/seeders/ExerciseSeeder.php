<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Exercise;
use Illuminate\Database\Seeder;

class ExerciseSeeder extends Seeder
{
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
        ];

        foreach ($exercises as $categoryName => $names) {
            $category = Category::firstOrCreate(['name' => $categoryName]);

            foreach ($names as $name) {
                Exercise::firstOrCreate(['name' => $name, 'category_id' => $category->id]);
            }
        }
    }
}
