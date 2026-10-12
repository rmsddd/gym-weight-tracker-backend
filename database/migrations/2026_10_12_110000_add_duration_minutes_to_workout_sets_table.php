<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // A set of a timed exercise has a duration and no weight or reps
        Schema::table('workout_sets', function (Blueprint $table) {
            $table->decimal('weight')->nullable()->change();
            $table->integer('reps')->nullable()->change();
            $table->decimal('duration_minutes', 6, 2)->nullable()->after('reps');
        });
    }

    public function down(): void
    {
        // Timed sets cannot be expressed as weight and reps
        DB::table('workout_sets')->whereNull('weight')->orWhereNull('reps')->delete();

        Schema::table('workout_sets', function (Blueprint $table) {
            $table->dropColumn('duration_minutes');
            $table->decimal('weight')->nullable(false)->change();
            $table->integer('reps')->nullable(false)->change();
        });
    }
};
