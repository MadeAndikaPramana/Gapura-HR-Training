<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Models\User;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     * Main seeder for GAPURA Training Management System
     */
    public function run(): void
    {
        $this->displayWelcomeHeader();

        // Get seeding options
        $options = $this->getSeederOptions();

        // Step 1: Create system users
        $this->createSystemUsers();

        // Step 2: Install training system
        $this->installTrainingSystem($options);

        // Step 3: Show completion summary
        $this->showCompletionSummary();
    }

    /**
     * Display welcome header
     */
    private function displayWelcomeHeader()
    {
        $this->command->info('🚀 GAPURA ANGKASA Training Management System');
        $this->command->info('===========================================');
        $this->command->info('   Complete Database Setup & Initialization');
        $this->command->newLine();
    }

    /**
     * Get seeder options from user input
     */
    private function getSeederOptions()
    {
        $options = [];

        // Ask for sample data
        if ($this->command->confirm('Install sample data for testing and demonstration?', true)) {
            $options['with-sample'] = true;

            // Ask about fresh install
            if ($this->command->confirm('Fresh install? (This will clear existing training data)', false)) {
                $options['fresh'] = true;
            }
        }

        return $options;
    }

    /**
     * Install training system with options
     */
    private function installTrainingSystem($options)
    {
        $this->command->info('📦 Installing GAPURA Training System...');

        // Pass options to TrainingSystemSeeder
        $seederOptions = [];
        if (isset($options['fresh'])) {
            $seederOptions['--fresh'] = true;
        }
        if (isset($options['with-sample'])) {
            $seederOptions['--with-sample'] = true;
        }

        $this->call(TrainingSystemSeeder::class, $seederOptions);
        $this->command->newLine();
    }

    /**
     * Show completion summary
     */
    private function showCompletionSummary()
    {
        $this->command->info('🎉 GAPURA Training System Setup Complete!');
        $this->command->info('========================================');
        $this->command->newLine();

        $this->showSystemStatistics();
        $this->showLoginCredentials();
        $this->showQuickStart();
    }

    /**
     * Show system statistics
     */
    private function showSystemStatistics()
    {
        $this->command->info('📊 SYSTEM OVERVIEW:');

        try {
            $stats = [
                'Users' => \App\Models\User::count(),
                'Training Types' => DB::table('training_types')->count(),
                'Employees' => Schema::hasTable('employees') ? DB::table('employees')->count() : 0,
                'Training Records' => DB::table('training_records')->count(),
                'Background Checks' => DB::table('background_checks')->count(),
            ];

            foreach ($stats as $item => $count) {
                $this->command->info("   {$item}: {$count}");
            }
        } catch (\Exception $e) {
            $this->command->info('   System ready for use');
        }

        $this->command->newLine();
    }

    /**
     * Show quick start guide
     */
    private function showQuickStart()
    {
        $this->command->info('🚀 QUICK START:');
        $this->command->info('1. Start the application:');
        $this->command->info('   php artisan serve');
        $this->command->info('   npm run dev');
        $this->command->newLine();
        $this->command->info('2. Access the system:');
        $this->command->info('   http://localhost:8000');
        $this->command->newLine();
        $this->command->info('3. Login with credentials above');
        $this->command->info('4. Import Excel training data: /import-export');
        $this->command->info('5. View dashboard analytics: /dashboard');
    }

    /**
     * Create system users
     */
    private function createSystemUsers()
    {
        $this->command->info('👥 Creating system users...');

        // Create admin user
        $admin = User::firstOrCreate(
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
        $hrManager = User::firstOrCreate(
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
        $hrStaff = User::firstOrCreate(
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

        $this->command->info('   ✅ Admin user created/updated');
        $this->command->info('   ✅ HR Manager created/updated');
        $this->command->info('   ✅ HR Staff created/updated');
        $this->command->newLine();
    }

    /**
     * Show login credentials
     */
    private function showLoginCredentials()
    {
        $this->command->info('🔐 LOGIN CREDENTIALS:');
        $this->command->info('================================');
        $this->command->info('Super Admin:');
        $this->command->info('  Email: admin@gapura.com');
        $this->command->info('  Password: password');
        $this->command->newLine();
        $this->command->info('HR Manager:');
        $this->command->info('  Email: hr.manager@gapura.com');
        $this->command->info('  Password: manager123');
        $this->command->newLine();
        $this->command->info('HR Staff:');
        $this->command->info('  Email: hr.staff@gapura.com');
        $this->command->info('  Password: staff123');
        $this->command->newLine();
        $this->command->info('🌐 Access: http://localhost:8000');
    }
}
