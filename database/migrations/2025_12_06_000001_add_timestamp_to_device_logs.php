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
        Schema::table('device_logs', function (Blueprint $table) {
            // Agent-Timestamp (ISO8601 vom Agent)
            $table->timestamp('agent_timestamp')->nullable()->after('context');
            
            // Index für zeitbasierte Queries
            $table->index('agent_timestamp');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('device_logs', function (Blueprint $table) {
            $table->dropIndex(['agent_timestamp']);
            $table->dropColumn('agent_timestamp');
        });
    }
};
