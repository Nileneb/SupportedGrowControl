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
        Schema::create('telemetry_readings', function (Blueprint $table) {
            $table->id();

            // Device Relation
            $table->foreignId('device_id')
                ->constrained()
                ->cascadeOnDelete();

            // Sensor Identifier (water_level, tds, temp, custom_sensor_1)
            $table->string('sensor_key', 50)->index();

            // Value (float for maximum flexibility)
            $table->decimal('value', 12, 4);

            // Unit (%, ppm, °C, V, etc.)
            $table->string('unit', 20)->nullable();

            // Raw data (für komplexe Messwerte wie GPS, JSON-Objekte)
            $table->json('raw')->nullable();

            // Messzeitpunkt
            $table->timestamp('measured_at');

            $table->timestamps();

            // Composite Index für effiziente Zeitreihen-Abfragen
            $table->index(['device_id', 'sensor_key', 'measured_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('telemetry_readings');
    }
};
