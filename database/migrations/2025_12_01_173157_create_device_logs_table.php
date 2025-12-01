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
        Schema::create('device_logs', function (Blueprint $table) {
            $table->id();

            // Device Relation
            $table->foreignId('device_id')
                ->constrained()
                ->cascadeOnDelete();

            // Log Level
            $table->enum('level', ['debug', 'info', 'warning', 'error'])
                ->default('info')
                ->index();

            // Message
            $table->text('message');

            // Context (JSON für zusätzliche Daten: uptime, memory, etc.)
            $table->json('context')->nullable();

            $table->timestamps();

            // Composite Index für Filtering
            $table->index(['device_id', 'level', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('device_logs');
    }
};
