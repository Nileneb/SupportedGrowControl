<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('sensor_types', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('display_name');
            $table->string('category'); // environment, nutrients, irrigation, lighting, system, custom
            $table->string('default_unit')->nullable();
            $table->string('value_type'); // float, int, string, bool
            $table->json('default_range')->nullable(); // [min, max]
            $table->boolean('critical')->default(false);
            $table->json('meta')->nullable(); // icons, visualization hints, descriptions
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sensor_types');
    }
};
