<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // Migrate water_levels -> measurements
        if (DB::getSchemaBuilder()->hasTable('water_levels')) {
            $rows = DB::table('water_levels')->get();
            foreach ($rows as $row) {
                DB::table('measurements')->insert([
                    'device_id'   => $row->device_id,
                    'sensor_key'  => 'water_level',
                    'value'       => is_numeric($row->value ?? null) ? $row->value : 0,
                    'unit'        => '%',
                    'raw'         => null,
                    'measured_at' => $row->measured_at ?? (property_exists($row, 'created_at') ? $row->created_at : now()),
                    'created_at'  => now(),
                    'updated_at'  => now(),
                ]);
            }
        }

        // Migrate tds_readings -> measurements
        if (DB::getSchemaBuilder()->hasTable('tds_readings')) {
            $rows = DB::table('tds_readings')->get();
            foreach ($rows as $row) {
                DB::table('measurements')->insert([
                    'device_id'   => $row->device_id,
                    'sensor_key'  => 'tds',
                    'value'       => is_numeric($row->value ?? null) ? $row->value : 0,
                    'unit'        => 'ppm',
                    'raw'         => null,
                    'measured_at' => $row->measured_at ?? (property_exists($row, 'created_at') ? $row->created_at : now()),
                    'created_at'  => now(),
                    'updated_at'  => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        // No-op: don't restore legacy tables
    }
};
