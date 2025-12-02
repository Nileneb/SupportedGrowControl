<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $adminEmail = 'bene.linn@yahoo.de';

        $user = User::where('email', $adminEmail)->first();

        if ($user) {
            $user->update(['is_admin' => true]);
            $this->command->info("User {$adminEmail} is now an admin.");
        } else {
            $this->command->warn("User {$adminEmail} not found. Creating admin user...");
            
            User::create([
                'name' => 'Bene Linn',
                'email' => $adminEmail,
                'password' => bcrypt('password'), // Change this in production
                'is_admin' => true,
                'email_verified_at' => now(),
            ]);
            
            $this->command->info("Admin user {$adminEmail} created successfully.");
        }
    }
}
