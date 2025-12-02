<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('actuator_types', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique(); // z.B. 'spray_pump', 'fill_valve', 'fan'
            $table->string('display_name');
            $table->string('category')->nullable();
            $table->json('default_config')->nullable(); // z.B. duration, min/max
            $table->string('command_template')->nullable(); // z.B. EXECUTE_PUMP {duration}
            $table->timestamps();
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('actuator_types');
    }
};
