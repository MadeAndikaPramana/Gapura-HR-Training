<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('🚀 Starting GAPURA Training System Database Setup...');

        // Create users only (skip training for Phase 1)
        $this->createSystemUsers();

        $this->command->info('✅ Phase 1 Database Setup Complete!');
        $this->showLoginCredentials();
    }

    private function createSystemUsers()
    {
        $this->command->info('👤 Creating System Users...');

        // Create admin user
        User::firstOrCreate(
            ['email' => 'admin@gapura.com'],
            [
                'name' => 'GAPURA Admin',
                'email' => 'admin@gapura.com',
                'email_verified_at' => now(),
                'password' => Hash::make('password'),
                'role' => 'super_admin',
                'is_active' => true,
            ]
        );

        // Create HR Manager
        User::firstOrCreate(
            ['email' => 'hr.manager@gapura.com'],
            [
                'name' => 'HR Manager',
                'email' => 'hr.manager@gapura.com',
                'email_verified_at' => now(),
                'password' => Hash::make('manager123'),
                'role' => 'admin',
                'is_active' => true,
            ]
        );

        // Create HR Staff
        User::firstOrCreate(
            ['email' => 'hr.staff@gapura.com'],
            [
                'name' => 'HR Staff',
                'email' => 'hr.staff@gapura.com',
                'email_verified_at' => now(),
                'password' => Hash::make('staff123'),
                'role' => 'staff',
                'is_active' => true,
            ]
        );

        $this->command->info('   ✅ Super Admin: admin@gapura.com / password');
        $this->command->info('   ✅ HR Manager: hr.manager@gapura.com / manager123');
        $this->command->info('   ✅ HR Staff: hr.staff@gapura.com / staff123');
    }

    private function showLoginCredentials()
    {
        $this->command->info('🔐 LOGIN CREDENTIALS:');
        $this->command->info('================================');
        $this->command->info('Super Admin:');
        $this->command->info('  Email: admin@gapura.com');
        $this->command->info('  Password: password');
        $this->command->newLine();
        $this->command->info('🌐 Access: http://localhost:8000');
    }
}
