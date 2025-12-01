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
        Schema::create('commands', function (Blueprint $table) {
            $table->id();

            // Device Relation
            $table->foreignId('device_id')
                ->constrained()
                ->cascadeOnDelete();

            // User Relation (wer hat Command erstellt)
            $table->foreignId('created_by_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            // Command Type (spray, fill, custom)
            $table->string('type', 50);

            // Parameters (JSON: seconds, level, liters, etc.)
            $table->json('params')->nullable();

            // Result Message vom Agent
            $table->text('result_message')->nullable();

            // Status
            $table->enum('status', ['pending', 'executing', 'completed', 'failed'])
                ->default('pending')
                ->index();

            // Abschluss-Zeitpunkt
            $table->timestamp('completed_at')->nullable();

            $table->timestamps();

            // Composite Index für pending Command Query
            $table->index(['device_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('commands');
    }
};
