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
        Schema::table('commands', function (Blueprint $table) {
            // User der den Command erstellt hat
            $table->foreignId('created_by_user_id')
                ->nullable()
                ->after('device_id')
                ->constrained('users')
                ->nullOnDelete();

            // Result Message vom Agent
            $table->text('result_message')
                ->nullable()
                ->after('params');

            // Abschluss-Zeitpunkt
            $table->timestamp('completed_at')
                ->nullable()
                ->after('result_message');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('commands', function (Blueprint $table) {
            $table->dropForeign(['created_by_user_id']);
            $table->dropColumn(['created_by_user_id', 'result_message', 'completed_at']);
        });
    }
};
