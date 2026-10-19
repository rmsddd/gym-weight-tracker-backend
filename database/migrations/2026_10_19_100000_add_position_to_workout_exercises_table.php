<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The order of the exercises in a workout, which the user can change while it is going on.
     */
    public function up(): void
    {
        Schema::table('workout_exercises', function (Blueprint $table) {
            $table->unsignedInteger('position')->default(0);
        });

        // Existing workouts keep the order the exercises were added in
        DB::table('workout_exercises')->update(['position' => DB::raw('id')]);
    }

    public function down(): void
    {
        Schema::table('workout_exercises', function (Blueprint $table) {
            $table->dropColumn('position');
        });
    }
};
