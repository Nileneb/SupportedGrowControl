<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('device_sensors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('device_id')->constrained()->cascadeOnDelete();
            $table->string('sensor_type_id');
            $table->foreign('sensor_type_id')->references('id')->on('sensor_types')->cascadeOnDelete();

            // Hardware configuration
            $table->string('pin', 20); // e.g., "7", "A0", "GPIO15"
            $table->string('channel_key', 50)->index(); // e.g., "temp_water", "water_level_cm"

            // Behavior configuration
            $table->integer('min_interval')->nullable(); // seconds between readings
            $table->boolean('critical')->default(false); // alert on failure

            // Additional sensor-specific config (calibration, offsets, etc.)
            $table->json('config')->nullable();

            $table->timestamps();

            // Unique constraint: one sensor per channel per device
            $table->unique(['device_id', 'channel_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('device_sensors');
    }
};
