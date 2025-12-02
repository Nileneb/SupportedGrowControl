<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Drop foreign keys first (silently fail if not exists)
        if (Schema::hasTable('device_sensors')) {
            try {
                Schema::table('device_sensors', function (Blueprint $table) {
                    $table->dropForeign(['sensor_type_id']);
                });
            } catch (\Exception $e) {
                // Foreign key might not exist, continue
            }
        }
        if (Schema::hasTable('device_actuators')) {
            try {
                Schema::table('device_actuators', function (Blueprint $table) {
                    $table->dropForeign(['actuator_type_id']);
                });
            } catch (\Exception $e) {
                // Foreign key might not exist, continue
            }
        }
        
        // Drop and recreate sensor_types with proper schema
        Schema::dropIfExists('sensor_types');
        Schema::create('sensor_types', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('name');
            $table->string('category')->nullable();
            $table->string('unit')->nullable();
            $table->string('value_type')->default('float');
            $table->float('min_value')->nullable();
            $table->float('max_value')->nullable();
            $table->string('arduino_read_command')->nullable();
            $table->string('response_pattern')->nullable();
            $table->integer('read_interval_seconds')->default(60);
            $table->text('description')->nullable();
            $table->json('metadata')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Drop and recreate actuator_types with proper schema
        Schema::dropIfExists('actuator_types');
        Schema::create('actuator_types', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('name');
            $table->string('category')->nullable();
            $table->enum('command_type', ['duration', 'toggle', 'value'])->default('toggle');
            $table->string('arduino_command_on')->nullable();
            $table->string('arduino_command_off')->nullable();
            $table->string('arduino_command_duration')->nullable();
            $table->string('arduino_command_value')->nullable();
            $table->string('duration_unit')->nullable();
            $table->string('duration_label')->nullable();
            $table->integer('min_duration')->nullable();
            $table->integer('max_duration')->nullable();
            $table->integer('default_duration')->nullable();
            $table->string('duration_help')->nullable();
            $table->string('amount_unit')->nullable();
            $table->string('amount_label')->nullable();
            $table->float('min_amount')->nullable();
            $table->float('max_amount')->nullable();
            $table->float('default_amount')->nullable();
            $table->text('description')->nullable();
            $table->json('metadata')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Ensure board_templates exists
        if (!Schema::hasTable('board_templates')) {
            Schema::create('board_templates', function (Blueprint $table) {
                $table->id();
                $table->string('key')->unique();
                $table->string('name');
                $table->string('vendor')->nullable();
                $table->string('mcu')->nullable();
                $table->string('architecture')->nullable();
                $table->integer('digital_pins')->nullable();
                $table->integer('analog_pins')->nullable();
                $table->integer('pwm_pins')->nullable();
                $table->json('available_pins')->nullable();
                $table->json('reserved_pins')->nullable();
                $table->text('description')->nullable();
                $table->json('metadata')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        // Update device_sensors if needed
        if (Schema::hasTable('device_sensors')) {
            Schema::table('device_sensors', function (Blueprint $table) {
                if (!Schema::hasColumn('device_sensors', 'sensor_type_id')) {
                    $table->foreignId('sensor_type_id')->nullable()->after('device_id')->constrained()->nullOnDelete();
                }
                if (!Schema::hasColumn('device_sensors', 'pin')) {
                    $table->string('pin')->nullable();
                }
                if (!Schema::hasColumn('device_sensors', 'custom_name')) {
                    $table->string('custom_name')->nullable();
                }
                if (!Schema::hasColumn('device_sensors', 'is_enabled')) {
                    $table->boolean('is_enabled')->default(true);
                }
                if (!Schema::hasColumn('device_sensors', 'read_interval_override')) {
                    $table->integer('read_interval_override')->nullable();
                }
                if (!Schema::hasColumn('device_sensors', 'calibration')) {
                    $table->json('calibration')->nullable();
                }
                if (!Schema::hasColumn('device_sensors', 'metadata')) {
                    $table->json('metadata')->nullable();
                }
            });
        }

        // Update device_actuators if needed
        if (Schema::hasTable('device_actuators')) {
            Schema::table('device_actuators', function (Blueprint $table) {
                if (!Schema::hasColumn('device_actuators', 'actuator_type_id')) {
                    $table->foreignId('actuator_type_id')->nullable()->after('device_id')->constrained()->nullOnDelete();
                }
                if (!Schema::hasColumn('device_actuators', 'pin')) {
                    $table->string('pin')->nullable();
                }
                if (!Schema::hasColumn('device_actuators', 'custom_name')) {
                    $table->string('custom_name')->nullable();
                }
                if (!Schema::hasColumn('device_actuators', 'is_enabled')) {
                    $table->boolean('is_enabled')->default(true);
                }
                if (!Schema::hasColumn('device_actuators', 'duration_override')) {
                    $table->integer('duration_override')->nullable();
                }
                if (!Schema::hasColumn('device_actuators', 'amount_override')) {
                    $table->float('amount_override')->nullable();
                }
                if (!Schema::hasColumn('device_actuators', 'metadata')) {
                    $table->json('metadata')->nullable();
                }
            });
        }
    }

    public function down(): void
    {
        // Intentionally empty - too complex to reverse
    }
};
