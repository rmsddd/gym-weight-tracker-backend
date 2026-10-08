<?php

use App\Enums\Avatar;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // The avatar set was redrawn: codes from the first set (cat, fox, ...) fall back to the default
        DB::table('users')
            ->whereNotIn('avatar', array_column(Avatar::cases(), 'value'))
            ->update(['avatar' => Avatar::Dino->value]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Nothing to restore: the previous codes cannot be recovered
    }
};
