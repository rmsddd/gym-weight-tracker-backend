<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            'Chest',
            'Back',
            'Shoulders',
            'Traps',
            'Biceps',
            'Triceps',
            'Forearms',
            'Abs',
            'Legs',
        ];

        foreach ($categories as $category) {
            Category::firstOrCreate(['name' => $category]);
        }
    }
}
