<?php

namespace Database\Seeders;

use App\Models\Device;
use App\Models\User;
use Illuminate\Database\Seeder;

class DeviceSeeder extends Seeder
{
    /**
     * Seed the devices table with initial Growdash devices.
     */
    public function run(): void
    {
        // Create a default user if none exists
        $user = User::firstOrCreate(
            ['email' => 'admin@growdash.local'],
            [
                'name' => 'Admin User',
                'password' => bcrypt('password'),
            ]
        );

        $devices = [
            [
                'user_id' => $user->id,
                'name' => 'Growdash Primary',
                'slug' => 'growdash-1',
                'ip_address' => '192.168.178.12',
                'serial_port' => '/dev/ttyUSB0',
            ],
            // Add more devices as needed
            // [
            //     'user_id' => $user->id,
            //     'name' => 'Growdash Secondary',
            //     'slug' => 'growdash-2',
            //     'ip_address' => '192.168.178.13',
            //     'serial_port' => '/dev/ttyUSB1',
            // ],
        ];

        foreach ($devices as $deviceData) {
            Device::updateOrCreate(
                ['slug' => $deviceData['slug']],
                $deviceData
            );
        }

        $this->command->info('Devices seeded successfully!');
        $this->command->info('Default user: admin@growdash.local / password');
    }
}
