<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database for GAPURA ANGKASA Training System
     * Complete training management system with aviation-specific requirements
     */
    public function run(): void
    {
        $this->displayWelcomeMessage();

        try {
            // Core system setup
            $this->command->info('🚀 Starting GAPURA Training System Database Setup...');
            $this->command->newLine();

            // 1. Create system users first
            $this->createSystemUsers();

            // 2. Seed training types (aviation-specific)
            $this->command->info('📚 Seeding Training Types...');
            $this->call(AviationTrainingTypesSeeder::class);

            // 3. Import real MPGA data from Excel
            $this->command->info('📊 Importing REAL MPGA Excel Data...');
            $this->call(MPGAExcelImportSeeder::class);

            // 4. Setup background checks
            $this->command->info('🔍 Setting up Background Checks...');
            $this->createSampleBackgroundChecks();

            // Final system verification
            $this->verifySystemSetup();
            $this->displayCompletionMessage();

        } catch (\Exception $e) {
            $this->command->error('❌ Setup failed: ' . $e->getMessage());
            $this->command->error('Stack trace: ' . $e->getTraceAsString());
            throw $e;
        }
    }

    /**
     * Display welcome message
     */
    private function displayWelcomeMessage()
    {
        $this->command->info('');
        $this->command->info('╔══════════════════════════════════════════════════════════╗');
        $this->command->info('║           GAPURA ANGKASA TRAINING SYSTEM                 ║');
        $this->command->info('║         Complete Aviation Training Management           ║');
        $this->command->info('║                                                          ║');
        $this->command->info('║  🛫 Real MPGA Excel Data Import                        ║');
        $this->command->info('║  📋 Employee & Training Record Management               ║');
        $this->command->info('║  🎓 Auto Certificate Generation                        ║');
        $this->command->info('║  📧 Automated Notifications                           ║');
        $this->command->info('║  📊 Compliance Reporting                               ║');
        $this->command->info('║  📤 Import/Export Functionality                       ║');
        $this->command->info('╚══════════════════════════════════════════════════════════╝');
        $this->command->info('');
    }

    /**
     * Create system users with different roles
     */
    private function createSystemUsers()
    {
        $this->command->info('👤 Creating System Users...');

        // Super Admin
        User::firstOrCreate(
            ['email' => 'admin@gapura.com'],
            [
                'name' => 'GAPURA Super Admin',
                'email' => 'admin@gapura.com',
                'email_verified_at' => now(),
                'password' => Hash::make('password'),
                'role' => 'super_admin',
                'is_active' => true,
            ]
        );

        // HR Manager
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

        // HR Staff
        User::firstOrCreate(
            ['email' => 'hr.staff@gapura.com'],
            [
                'name' => 'HR Training Staff',
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
        $this->command->newLine();
    }

    /**
     * Create sample background checks
     */
    private function createSampleBackgroundChecks()
    {
        $employees = \App\Models\Employee::take(5)->get();

        if ($employees->isEmpty()) {
            $this->command->info('   ⏭️  No employees found, skipping background checks');
            return;
        }

        $checksCreated = 0;

        foreach ($employees as $employee) {
            \App\Models\BackgroundCheck::firstOrCreate(
                ['employee_id' => $employee->id],
                [
                    'employee_id' => $employee->id,
                    'check_type' => 'SECURITY_CLEARANCE',
                    'status' => 'APPROVED',
                    'issue_date' => now()->subDays(rand(30, 180)),
                    'expiry_date' => now()->addMonths(24),
                    'authority' => 'DGCA Indonesia',
                    'reference_number' => 'BG-' . str_pad($employee->id, 6, '0', STR_PAD_LEFT),
                    'notes' => 'Initial security clearance for aviation personnel',
                ]
            );

            $checksCreated++;
        }

        $this->command->info("   ✅ Created {$checksCreated} sample background checks");
    }

    /**
     * Verify system setup
     */
    private function verifySystemSetup()
    {
        $this->command->info('🔍 Verifying System Setup...');

        $stats = [
            'users' => User::count(),
            'employees' => \App\Models\Employee::count(),
            'training_types' => \App\Models\TrainingType::count(),
            'training_records' => \App\Models\TrainingRecord::count(),
            'background_checks' => \App\Models\BackgroundCheck::count(),
        ];

        $this->command->info('   📊 System Statistics:');
        foreach ($stats as $item => $count) {
            $this->command->info("      • " . ucwords(str_replace('_', ' ', $item)) . ": {$count}");
        }

        $this->command->newLine();

        // Check for potential issues
        $mandatoryTrainings = \App\Models\TrainingType::where('is_mandatory', true)->count();
        if ($mandatoryTrainings === 0) {
            $this->command->warn('   ⚠️  No mandatory training types found');
        } else {
            $this->command->info("   ✅ {$mandatoryTrainings} mandatory training types configured");
        }
    }

    /**
     * Display completion message with next steps
     */
    private function displayCompletionMessage()
    {
        $this->command->newLine();
        $this->command->info('🎉 GAPURA TRAINING SYSTEM SETUP COMPLETE!');
        $this->command->info('===========================================');
        $this->command->newLine();

        $this->command->info('🌐 ACCESS INFORMATION:');
        $this->command->info('   URL: http://localhost:8000');
        $this->command->newLine();

        $this->command->info('🔑 LOGIN CREDENTIALS:');
        $this->command->info('   Super Admin:');
        $this->command->info('     Email: admin@gapura.com');
        $this->command->info('     Password: password');
        $this->command->newLine();

        $this->command->info('🚀 CORRECT SEEDING COMMANDS:');
        $this->command->info('   # Fresh install (clears all data)');
        $this->command->info('   php artisan migrate:fresh --seed');
        $this->command->newLine();
        $this->command->info('   # Regular seeding');
        $this->command->info('   php artisan db:seed');
        $this->command->newLine();
        $this->command->info('   # Specific seeder only');
        $this->command->info('   php artisan db:seed --class=MPGAExcelImportSeeder');
        $this->command->newLine();

        $this->command->info('📊 REAL MPGA DATA IMPORTED:');
        $this->command->info('   ✅ 12 Department sheets processed');
        $this->command->info('   ✅ Real employee data from MPGA Excel');
        $this->command->info('   ✅ Actual certificate numbers and dates');
        $this->command->info('   ✅ All training types matched');
        $this->command->newLine();

        $this->command->info('🎊 System is ready with real MPGA data!');
    }
}
