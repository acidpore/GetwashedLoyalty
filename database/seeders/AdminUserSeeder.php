<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create default admin user
        User::updateOrCreate(
            ['email' => 'admin@getwashed.com'],
            [
                'name' => 'Admin Getwashed',
                'email' => 'admin@getwashed.com',
                'password' => Hash::make('password'),
                'role' => 'admin',
                'phone' => null,
                'email_verified_at' => now(),
            ]
        );

        $this->command->info('✅ Admin user created successfully!');
        $this->command->info('📧 Email: admin@getwashed.com');
        $this->command->info('🔑 Password: password');
    }
}
