<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // kg = weight and reps per set, min = only how long the set lasted (see App\Enums\ExerciseUnit)
        Schema::table('exercises', function (Blueprint $table) {
            $table->string('unit', 5)->default('kg')->after('name');
        });

        // The only catalog exercise so far that is held for time instead of lifted
        DB::table('exercises')->whereNull('user_id')->where('name', 'Plank')->update(['unit' => 'min']);
    }

    public function down(): void
    {
        Schema::table('exercises', function (Blueprint $table) {
            $table->dropColumn('unit');
        });
    }
};
