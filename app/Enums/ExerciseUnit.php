<?php

namespace App\Enums;

// What a set of the exercise records: weight and reps, or only how long it lasted (plank, cardio).
enum ExerciseUnit: string
{
    case Kilograms = 'kg';
    case Minutes = 'min';
}
