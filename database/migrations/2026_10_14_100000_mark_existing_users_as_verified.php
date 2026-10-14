<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Confirming the email became required after these accounts were made; they keep working
     * without having to confirm. Only accounts created from now on must open the link.
     */
    public function up(): void
    {
        DB::table('users')->whereNull('email_verified_at')->update(['email_verified_at' => now()]);
    }

    public function down(): void
    {
        // Nothing to undo: which accounts were unconfirmed before is not recorded
    }
};
