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
        Schema::create('log_patterns', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // e.g. "Arduino Response", "Status Update"
            $table->text('regex_pattern'); // e.g. "/Arduino Antwort:\s*(.+)/i"
            $table->string('icon')->nullable(); // e.g. "📊", "ℹ️", "❌"
            $table->string('color')->nullable(); // e.g. "text-green-300", "text-blue-300"
            $table->json('parser_config')->nullable(); // JSON config for complex parsing
            $table->integer('priority')->default(100); // Lower = higher priority (checked first)
            $table->boolean('enabled')->default(true);
            $table->text('description')->nullable();
            $table->timestamps();
            
            $table->index('priority');
            $table->index('enabled');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('log_patterns');
    }
};
