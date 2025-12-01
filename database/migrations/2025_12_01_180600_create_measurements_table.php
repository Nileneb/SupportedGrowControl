<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('measurements', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('device_id');
            $table->string('sensor_key');
            $table->decimal('value', 12, 4);
            $table->string('unit')->nullable();
            $table->json('raw')->nullable();
            $table->timestamp('measured_at')->index();
            $table->timestamps();

            $table->foreign('device_id')->references('id')->on('devices')->onDelete('cascade');
            $table->index(['device_id', 'sensor_key', 'measured_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('measurements');
    }
};
