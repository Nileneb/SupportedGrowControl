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
        // Check if device_id column exists before adding it
        if (!Schema::hasColumn('shelly_devices', 'device_id')) {
            Schema::table('shelly_devices', function (Blueprint $table) {
                $table->foreignId('device_id')->nullable()->after('user_id')->constrained('devices')->onDelete('cascade');
                $table->index('device_id');
            });
        }

        // Create Device entries for existing ShellyDevices that don't have a device_id
        $shellys = DB::table('shelly_devices')->whereNull('device_id')->get();
        
        foreach ($shellys as $shelly) {
            // Create Device entry
            $deviceId = DB::table('devices')->insertGetId([
                'user_id' => $shelly->user_id,
                'public_id' => \Illuminate\Support\Str::uuid(),
                'name' => $shelly->name,
                'slug' => \Illuminate\Support\Str::slug($shelly->name) . '-' . substr(md5($shelly->id), 0, 6),
                'status' => 'offline', // Will be updated by ping/webhook
                'device_type' => 'shelly',
                'ip_address' => $shelly->ip_address,
                'device_info' => json_encode([
                    'platform' => 'shelly',
                    'model' => $shelly->model ?? 'Unknown',
                    'shelly_device_id' => $shelly->shelly_device_id,
                ]),
                'capabilities' => json_encode([
                    'actuators' => [
                        [
                            'id' => 'relay',
                            'type' => 'relay',
                            'display_name' => 'Relay',
                            'command_type' => 'toggle',
                            'current_state' => null,
                        ]
                    ],
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Link ShellyDevice to Device
            DB::table('shelly_devices')
                ->where('id', $shelly->id)
                ->update(['device_id' => $deviceId]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Delete Device entries created for Shellys
        $shellyDeviceIds = DB::table('shelly_devices')
            ->whereNotNull('device_id')
            ->pluck('device_id');
        
        DB::table('devices')->whereIn('id', $shellyDeviceIds)->delete();

        Schema::table('shelly_devices', function (Blueprint $table) {
            $table->dropForeign(['device_id']);
            $table->dropColumn('device_id');
        });
    }
};
