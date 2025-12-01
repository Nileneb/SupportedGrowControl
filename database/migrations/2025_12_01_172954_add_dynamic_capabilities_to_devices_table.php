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
            // Board-Typ (ESP32, Raspberry, Custom)
            $table->string('board_type', 50)
                ->nullable()
                ->after('paired_at');

            // Device Status
            $table->enum('status', ['paired', 'online', 'offline', 'error'])
                ->default('offline')
                ->after('board_type');

            // Capabilities JSON (Sensoren, Actuatoren, Firmware)
            $table->json('capabilities')
                ->nullable()
                ->after('status');

            // Last State JSON (aktuellste Sensor-Werte für schnelles Dashboard)
            $table->json('last_state')
                ->nullable()
                ->after('capabilities');

            // Zuletzt gesehen
            $table->timestamp('last_seen_at')
                ->nullable()
                ->after('last_state');

            $table->index('status');
            $table->index('last_seen_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('devices', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropIndex(['last_seen_at']);
            $table->dropColumn([
                'board_type',
                'status',
                'capabilities',
                'last_state',
                'last_seen_at'
            ]);
        });
    }
};
