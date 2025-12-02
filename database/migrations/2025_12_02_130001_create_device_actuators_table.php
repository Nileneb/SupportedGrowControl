<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('device_actuators', function (Blueprint $table) {
            $table->id();
            $table->foreignId('device_id')->constrained()->cascadeOnDelete();
            $table->string('actuator_type_id');
            $table->foreign('actuator_type_id')->references('id')->on('actuator_types')->cascadeOnDelete();
            
            // Hardware configuration
            $table->string('pin', 20); // e.g., "6", "GPIO12"
            $table->string('channel_key', 50)->index(); // e.g., "main_pump", "sprayer"
            
            // Behavior configuration
            $table->integer('min_interval')->nullable(); // min seconds between activations
            
            // Additional actuator-specific config (invert logic, PWM frequency, etc.)
            $table->json('config')->nullable();
            
            $table->timestamps();
            
            // Unique constraint: one actuator per channel per device
            $table->unique(['device_id', 'channel_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('device_actuators');
    }
};
