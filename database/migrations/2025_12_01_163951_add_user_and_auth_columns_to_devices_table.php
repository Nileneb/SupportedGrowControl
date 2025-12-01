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
            // User-Beziehung für Multi-Tenancy
            $table->foreignId('user_id')
                ->nullable() // Nullable für bestehende Devices
                ->after('id')
                ->constrained()
                ->cascadeOnDelete();

            // Öffentliche UUID für externe API-Calls
            $table->uuid('public_id')
                ->nullable()
                ->after('user_id')
                ->unique();

            // Agent-Token für Device-Authentication
            $table->string('agent_token', 64)
                ->nullable()
                ->after('public_id')
                ->unique();

            // Index für schnelle User-Device-Queries
            $table->index('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('devices', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropIndex(['user_id']);
            $table->dropColumn(['user_id', 'public_id', 'agent_token']);
        });
    }
};
