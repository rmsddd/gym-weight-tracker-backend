<?php

use App\Models\Category;
use App\Models\Exercise;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /** Old Romanian category => English category (Lombar/Fesieri/Gambe are merged into Back/Legs). */
    private const CATEGORIES = [
        'Piept' => 'Chest',
        'Spate' => 'Back',
        'Umeri' => 'Shoulders',
        'Trapez' => 'Traps',
        'Biceps' => 'Biceps',
        'Triceps' => 'Triceps',
        'Antebrațe' => 'Forearms',
        'Abdomen' => 'Abs',
        'Picioare' => 'Legs',
        'Lombar' => 'Back',
        'Fesieri' => 'Legs',
        'Gambe' => 'Legs',
    ];

    private const EXERCISES = [
        'Împins cu bara la bancă plană' => 'Barbell Bench Press',
        'Împins cu gantere la bancă înclinată' => 'Incline Dumbbell Press',
        'Fluturări cu gantere' => 'Dumbbell Fly',
        'Flotări' => 'Push-Up',
        'Împins la aparat' => 'Machine Chest Press',
        'Fluturări la cablu' => 'Cable Fly',
        'Tracțiuni' => 'Pull-Up',
        'Ramat cu bara' => 'Barbell Row',
        'Ramat cu ganteră' => 'Dumbbell Row',
        'Tracțiuni la helcometru' => 'Lat Pulldown',
        'Ramat la cablu' => 'Seated Cable Row',
        'Îndreptări' => 'Deadlift',
        'Presă militară' => 'Overhead Press',
        'Ridicări laterale cu gantere' => 'Lateral Raise',
        'Ridicări frontale' => 'Front Raise',
        'Împins cu gantere pentru umeri' => 'Dumbbell Shoulder Press',
        'Fluturări inverse' => 'Reverse Fly',
        'Ridicări de umeri cu bara' => 'Barbell Shrug',
        'Ridicări de umeri cu gantere' => 'Dumbbell Shrug',
        'Face pull' => 'Face Pull',
        'Flexii cu bara' => 'Barbell Curl',
        'Flexii cu gantere' => 'Dumbbell Curl',
        'Flexii ciocan' => 'Hammer Curl',
        'Flexii la banca Scott' => 'Preacher Curl',
        'Flexii la cablu' => 'Cable Curl',
        'Extensii la cablu' => 'Cable Pushdown',
        'Împins cu priză îngustă' => 'Close-Grip Bench Press',
        'Extensii peste cap cu ganteră' => 'Overhead Dumbbell Extension',
        'Flotări la paralele' => 'Dips',
        'Skull crushers' => 'Skull Crusher',
        'Flexii de încheietură' => 'Wrist Curl',
        'Extensii de încheietură' => 'Wrist Extension',
        'Plimbarea fermierului' => "Farmer's Walk",
        'Ridicări de picioare' => 'Hanging Leg Raise',
        'Roata abdominală' => 'Ab Wheel Rollout',
        'Russian twist' => 'Russian Twist',
        'Hiperextensii' => 'Back Extension',
        'Good morning' => 'Good Morning',
        'Genuflexiuni cu bara' => 'Barbell Squat',
        'Presă picioare' => 'Leg Press',
        'Fandări' => 'Lunge',
        'Extensii pentru cvadriceps' => 'Leg Extension',
        'Flexii pentru femurali' => 'Leg Curl',
        'Îndreptări românești' => 'Romanian Deadlift',
        'Hip thrust' => 'Hip Thrust',
        'Pas lateral cu bandă' => 'Banded Lateral Walk',
        'Kickback la cablu' => 'Cable Kickback',
        'Genuflexiuni bulgărești' => 'Bulgarian Split Squat',
        'Ridicări pe vârfuri în picioare' => 'Standing Calf Raise',
        'Ridicări pe vârfuri șezând' => 'Seated Calf Raise',
        'Ridicări pe vârfuri la presă' => 'Leg Press Calf Raise',
    ];

    public function up(): void
    {
        foreach (self::CATEGORIES as $old => $new) {
            $oldCategory = Category::where('name', $old)->first();
            if (! $oldCategory || $old === $new) {
                continue;
            }

            $target = Category::where('name', $new)->first();

            if ($target) {
                Exercise::where('category_id', $oldCategory->id)->update(['category_id' => $target->id]);
                $oldCategory->delete();
            } else {
                $oldCategory->update(['name' => $new]);
            }
        }

        foreach (self::EXERCISES as $old => $new) {
            Exercise::where('name', $old)->update(['name' => $new]);
        }
    }

    public function down(): void
    {
        // Not reversible: merged categories can't be split back.
    }
};
