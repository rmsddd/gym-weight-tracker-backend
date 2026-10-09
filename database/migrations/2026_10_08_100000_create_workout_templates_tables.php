<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('workout_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id');
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('workout_template_exercises', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workout_template_id');
            $table->foreignId('exercise_id');
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workout_template_exercises');
        Schema::dropIfExists('workout_templates');
    }
};
