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
        $this->command->info('   Simplified Role System: Admin & Super Admin Only');
        $this->command->newLine();

        // Create users with simplified roles
        $this->createSystemUsers();

        $this->command->info('✅ Database Setup Complete!');
        $this->showLoginCredentials();
    }

    private function createSystemUsers()
    {
        $this->command->info('👤 Creating System Users...');

        // Create Super Admin
        $superAdmin = User::firstOrCreate(
            ['email' => 'admin@gapura.com'],
            [
                'name' => 'GAPURA Super Admin',
                'email' => 'admin@gapura.com',
                'email_verified_at' => now(),
                'password' => Hash::make('password'),
                'role' => User::ROLE_SUPER_ADMIN,
                'is_active' => true,
            ]
        );

        // Create Regular Admin
        $admin = User::firstOrCreate(
            ['email' => 'hr.admin@gapura.com'],
            [
                'name' => 'HR Administrator',
                'email' => 'hr.admin@gapura.com',
                'email_verified_at' => now(),
                'password' => Hash::make('admin123'),
                'role' => User::ROLE_ADMIN,
                'is_active' => true,
            ]
        );

        // Create another Admin for testing
        $admin2 = User::firstOrCreate(
            ['email' => 'training.admin@gapura.com'],
            [
                'name' => 'Training Administrator',
                'email' => 'training.admin@gapura.com',
                'email_verified_at' => now(),
                'password' => Hash::make('admin123'),
                'role' => User::ROLE_ADMIN,
                'is_active' => true,
            ]
        );

        $this->command->info('   ✅ Super Admin: admin@gapura.com / password');
        $this->command->info('   ✅ HR Admin: hr.admin@gapura.com / admin123');
        $this->command->info('   ✅ Training Admin: training.admin@gapura.com / admin123');
        $this->command->newLine();
    }

    private function showLoginCredentials()
    {
        $this->command->info('🔐 LOGIN CREDENTIALS:');
        $this->command->info('================================');

        $this->command->info('🔑 SUPER ADMIN (Full Access):');
        $this->command->info('  Email: admin@gapura.com');
        $this->command->info('  Password: password');
        $this->command->info('  Permissions: All features + Master Data Management');
        $this->command->newLine();

        $this->command->info('👤 ADMIN (Operational Access):');
        $this->command->info('  Email: hr.admin@gapura.com');
        $this->command->info('  Password: admin123');
        $this->command->info('  Permissions: CRUD Employees, Training Records, Reports');
        $this->command->newLine();

        $this->command->info('📚 PERMISSION SUMMARY:');
        $this->command->info('================================');
        $this->command->info('👑 SUPER ADMIN can:');
        $this->command->info('   • All Admin features');
        $this->command->info('   • Create new departments');
        $this->command->info('   • Create new training types');
        $this->command->info('   • Create new certificate templates');
        $this->command->info('   • Manage system settings');
        $this->command->info('   • Manage users');
        $this->command->newLine();

        $this->command->info('👤 ADMIN can:');
        $this->command->info('   • CRUD Employee data');
        $this->command->info('   • CRUD Training records');
        $this->command->info('   • View reports & analytics');
        $this->command->info('   • Export/Import data');
        $this->command->info('   • Generate certificates');
        $this->command->newLine();

        $this->command->info('🌐 Access: http://localhost:8000');
    }
}
