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
        Schema::create('fill_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('device_id')->constrained()->cascadeOnDelete();
            $table->timestamp('start_time');
            $table->timestamp('end_time')->nullable();
            $table->integer('duration_seconds')->nullable();
            $table->float('target_level')->nullable();
            $table->float('target_liters')->nullable();
            $table->float('actual_liters')->nullable();
            $table->boolean('manual')->default(true);
            $table->timestamps();

            $table->index(['device_id', 'start_time']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fill_events');
    }
};
