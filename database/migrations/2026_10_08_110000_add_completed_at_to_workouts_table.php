<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('workouts', function (Blueprint $table) {
            $table->timestamp('completed_at')->nullable()->after('finished_at');
        });

        // Workouts stopped before "Finish" existed were final, so they stay locked
        DB::table('workouts')->whereNotNull('finished_at')->update(['completed_at' => DB::raw('finished_at')]);
    }

    public function down(): void
    {
        Schema::table('workouts', function (Blueprint $table) {
            $table->dropColumn('completed_at');
        });
    }
};
