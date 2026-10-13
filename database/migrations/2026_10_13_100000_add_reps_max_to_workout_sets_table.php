<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // A set logged as "9-10": 9 clean reps (reps) and a last one that was a struggle (reps_max)
        Schema::table('workout_sets', function (Blueprint $table) {
            $table->integer('reps_max')->nullable()->after('reps');
        });
    }

    public function down(): void
    {
        Schema::table('workout_sets', function (Blueprint $table) {
            $table->dropColumn('reps_max');
        });
    }
};
