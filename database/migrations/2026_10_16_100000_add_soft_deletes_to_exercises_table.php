<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A deleted custom exercise is only hidden, so the workouts that logged it keep their history.
     */
    public function up(): void
    {
        Schema::table('exercises', function (Blueprint $table) {
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('exercises', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};
