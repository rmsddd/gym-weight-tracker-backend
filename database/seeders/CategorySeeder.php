<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            'Piept',
            'Spate',
            'Umeri',
            'Trapez',
            'Biceps',
            'Triceps',
            'Antebrațe',
            'Abdomen',
            'Lombar',
            'Picioare',
            'Fesieri',
            'Gambe',
        ];

        foreach ($categories as $category) {
            Category::create(['name' => $category]);
        }
    }
}
