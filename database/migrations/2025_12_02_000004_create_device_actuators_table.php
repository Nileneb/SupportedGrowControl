<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('device_actuators', function (Blueprint $table) {
            $table->id();
            $table->foreignId('device_id')->constrained()->onDelete('cascade');
            $table->foreignId('actuator_type_id')->constrained('actuator_types');
            $table->string('channel')->nullable();
            $table->string('pin')->nullable();
            $table->json('config')->nullable();
            $table->timestamps();
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('device_actuators');
    }
};
