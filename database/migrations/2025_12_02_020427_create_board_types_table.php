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
        Schema::create('board_types', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50)->unique(); // e.g., 'arduino_uno', 'esp32'
            $table->string('fqbn')->nullable(); // Fully Qualified Board Name for arduino-cli
            $table->string('vendor', 100)->nullable(); // e.g., 'Arduino', 'Espressif'
            $table->json('meta')->nullable(); // Additional metadata (cores, upload speed, etc.)
            $table->timestamps();

            $table->index('name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('board_types');
    }
};
