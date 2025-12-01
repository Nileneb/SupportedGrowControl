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
        Schema::create('pair_codes', function (Blueprint $table) {
            $table->id();
            
            // User Relation
            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            // 6-stelliger Code (ABC123)
            $table->string('code', 6)->unique();

            // Device Name (vom User beim Erstellen angegeben)
            $table->string('device_name');

            // Ablaufzeit (z.B. 10 Minuten nach Erstellung)
            $table->timestamp('expires_at');

            // Verwendungs-Zeitpunkt (Single-Use)
            $table->timestamp('used_at')->nullable();

            // Device (nach Verwendung gesetzt)
            $table->foreignId('device_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->timestamps();

            $table->index(['user_id', 'expires_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pair_codes');
    }
};
