<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('workout_sets', function (Blueprint $table) {
            $table->id();
            $table->decimal('weight');
            $table->integer('reps');
            $table->foreignId('workout_exercise_id');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workout_sets');
    }
};
