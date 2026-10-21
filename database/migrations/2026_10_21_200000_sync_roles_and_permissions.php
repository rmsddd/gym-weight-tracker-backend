<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Artisan;

return new class extends Migration
{
    // Existing accounts get the default role as soon as the tables exist, before anyone logs in
    public function up(): void
    {
        Artisan::call('permissions:sync');
    }

    public function down(): void
    {
        //
    }
};
