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
            $table->string('shelly_device_id')->nullable()->after('board_type');
            $table->string('shelly_auth_token')->nullable()->after('shelly_device_id');
            $table->json('shelly_config')->nullable()->after('shelly_auth_token');
            $table->timestamp('shelly_last_webhook_at')->nullable()->after('shelly_config');
            
            $table->index('shelly_device_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('devices', function (Blueprint $table) {
            $table->dropIndex(['shelly_device_id']);
            $table->dropColumn([
                'shelly_device_id',
                'shelly_auth_token',
                'shelly_config',
                'shelly_last_webhook_at',
            ]);
        });
    }
};
