<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// The linking columns were created with foreignId() but without constrained(), so they had no
// index: every lookup by workout, exercise or category scanned the whole table.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('workout_exercises', function (Blueprint $table) {
            $table->index('workout_id');
            $table->index('exercise_id');
        });

        Schema::table('workout_sets', function (Blueprint $table) {
            $table->index('workout_exercise_id');
        });

        Schema::table('exercises', function (Blueprint $table) {
            $table->index('category_id');
        });

        // A user's workouts are always listed or filtered by date
        Schema::table('workouts', function (Blueprint $table) {
            $table->index(['user_id', 'date']);
        });

        Schema::table('workout_templates', function (Blueprint $table) {
            $table->index('user_id');
        });

        Schema::table('workout_template_exercises', function (Blueprint $table) {
            $table->index('workout_template_id');
            $table->index('exercise_id');
        });
    }

    public function down(): void
    {
        Schema::table('workout_template_exercises', function (Blueprint $table) {
            $table->dropIndex(['workout_template_id']);
            $table->dropIndex(['exercise_id']);
        });

        Schema::table('workout_templates', function (Blueprint $table) {
            $table->dropIndex(['user_id']);
        });

        // MySQL lets the composite index stand in for the user_id foreign key's own index, so
        // that single-column index has to exist again before the composite one can be dropped
        Schema::table('workouts', function (Blueprint $table) {
            $table->index('user_id');
        });
        Schema::table('workouts', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'date']);
        });

        Schema::table('exercises', function (Blueprint $table) {
            $table->dropIndex(['category_id']);
        });

        Schema::table('workout_sets', function (Blueprint $table) {
            $table->dropIndex(['workout_exercise_id']);
        });

        Schema::table('workout_exercises', function (Blueprint $table) {
            $table->dropIndex(['workout_id']);
            $table->dropIndex(['exercise_id']);
        });
    }
};
