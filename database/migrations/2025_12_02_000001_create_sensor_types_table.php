<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('sensor_types', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique(); // z.B. 'temperature', 'tds', 'ph'
            $table->string('display_name');
            $table->string('unit')->nullable();
            $table->string('category')->nullable();
            $table->json('default_config')->nullable(); // z.B. min/max, decimals
            $table->string('command_template')->nullable(); // z.B. GET_TEMP
            $table->timestamps();
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('sensor_types');
    }
};
