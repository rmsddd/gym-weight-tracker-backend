<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // The other muscle groups a compound exercise works, besides its own category
        Schema::create('exercise_secondary_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exercise_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->unique(['exercise_id', 'category_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exercise_secondary_categories');
    }
};
