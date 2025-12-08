<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Backfill: if a device has a user_id but no paired_at, set paired_at to created_at (fallback) to satisfy the constraint
        DB::table('devices')
            ->whereNotNull('user_id')
            ->whereNull('paired_at')
            ->update(['paired_at' => DB::raw('COALESCE(created_at, NOW())')]);

        // Enforce: paired devices must have a user_id and paired_at together; unpaired devices keep both null
        DB::statement(<<<SQL
            ALTER TABLE devices
            ADD CONSTRAINT devices_user_pairing_check
            CHECK ((paired_at IS NULL AND user_id IS NULL) OR (paired_at IS NOT NULL AND user_id IS NOT NULL))
        SQL);
    }

    public function down(): void
    {
        // Drop the check constraint (MySQL/MariaDB syntax)
        DB::statement('ALTER TABLE devices DROP CHECK devices_user_pairing_check');
    }
};
