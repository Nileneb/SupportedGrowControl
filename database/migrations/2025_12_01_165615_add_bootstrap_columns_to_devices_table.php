<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('devices', function (Blueprint $table) {
            // Bootstrap-ID: Vom Agent generiert, eindeutig
            $table->string('bootstrap_id', 64)
                ->nullable()
                ->after('agent_token')
                ->unique();

            // Bootstrap-Code: 6-stelliger Code für UI-Pairing
            $table->string('bootstrap_code', 6)
                ->nullable()
                ->after('bootstrap_id')
                ->index();

            // Pairing-Timestamp
            $table->timestamp('paired_at')
                ->nullable()
                ->after('bootstrap_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('devices', function (Blueprint $table) {
            $table->dropUnique(['bootstrap_id']);
            $table->dropIndex(['bootstrap_code']);
            $table->dropColumn(['bootstrap_id', 'bootstrap_code', 'paired_at']);
        });
    }
};
