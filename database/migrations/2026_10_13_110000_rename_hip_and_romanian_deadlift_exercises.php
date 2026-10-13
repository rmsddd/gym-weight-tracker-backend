<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const RENAMES = [
        'Leg Adduction' => 'Hip Adduction',
        'Leg Abduction' => 'Hip Abduction',
        'Romanian Deadlift' => 'Romanian Deadlift (RDLs)',
    ];

    public function up(): void
    {
        $this->rename(self::RENAMES);
    }

    public function down(): void
    {
        $this->rename(array_flip(self::RENAMES));
    }

    /** Renamed in place, so the sets already logged for these exercises stay attached to them. */
    private function rename(array $names): void
    {
        foreach ($names as $old => $new) {
            DB::table('exercises')->whereNull('user_id')->where('name', $old)->update(['name' => $new]);
        }
    }
};
