<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** When a stopped workout was last started again, so friends see the time since then. */
    public function up(): void
    {
        Schema::table('workouts', function (Blueprint $table) {
            $table->timestamp('resumed_at')->nullable()->after('started_at');
        });
    }

    public function down(): void
    {
        Schema::table('workouts', function (Blueprint $table) {
            $table->dropColumn('resumed_at');
        });
    }
};
