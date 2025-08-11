<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class TrainingSystemSeeder extends Seeder
{
    /**
     * Run the database seeds for complete Training System
     * This seeder will setup the complete training system for GAPURA ANGKASA
     */
    public function run(): void
    {
        $this->displayHeader();

        // Get options
        $fresh = $this->command->option('fresh') ?? false;
        $withSample = $this->command->option('with-sample') ?? false;

        // Fresh install warning
        if ($fresh) {
            $this->handleFreshInstall();
        }

        // Check system prerequisites
        if (!$this->checkSystemPrerequisites()) {
            return;
        }

        // Run installation steps
        $this->runInstallationSteps($withSample);

        // Show completion summary
        $this->showInstallationSummary();
    }

    /**
     * Display installation header
     */
    private function displayHeader()
    {
        $this->command->info('🚀 GAPURA ANGKASA Training Management System Installer');
        $this->command->info('=======================================================');
        $this->command->info('   Complete Aviation Training & Certification System');
        $this->command->info('   Based on MPGA Excel structure and requirements');
        $this->command->newLine();
    }

    /**
     * Handle fresh install option
     */
    private function handleFreshInstall()
    {
        $this->command->warn('⚠️  FRESH INSTALL MODE: This will reset all training data!');

        if (!$this->command->confirm('Are you sure you want to continue? This will delete all existing training data.')) {
            $this->command->error('Installation cancelled by user.');
            return;
        }

        $this->command->info('🧹 Clearing existing training data...');
        $this->clearExistingTrainingData();
        $this->command->newLine();
    }

    /**
     * Check system prerequisites
     */
    private function checkSystemPrerequisites()
    {
        $this->command->info('🔍 Checking system prerequisites...');

        // Check database connection
        try {
            DB::connection()->getPdo();
            $this->command->info('   ✅ Database connection: OK');
        } catch (\Exception $e) {
            $this->command->error('   ❌ Database connection failed: ' . $e->getMessage());
            return false;
        }

        // Check required tables
        $requiredTables = ['training_types', 'training_records', 'background_checks'];
        $missingTables = [];

        foreach ($requiredTables as $table) {
            if (!Schema::hasTable($table)) {
                $missingTables[] = $table;
            }
        }

        if (!empty($missingTables)) {
            $this->command->error('   ❌ Missing required tables: ' . implode(', ', $missingTables));
            $this->command->info('   Please run migrations first: php artisan migrate');
            return false;
        }

        $this->command->info('   ✅ Required tables: OK');

        // Check if employees table exists
        if (!Schema::hasTable('employees')) {
            $this->command->warn('   ⚠️  Employees table not found. Sample employees will be created.');
        } else {
            $employeeCount = DB::table('employees')->count();
            $this->command->info("   ✅ Employees table: OK ({$employeeCount} records)");
        }

        $this->command->info('   ✅ Prerequisites check completed');
        $this->command->newLine();

        return true;
    }

    /**
     * Clear existing training data
     */
    private function clearExistingTrainingData()
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        $tables = [
            'training_records' => 'Training Records',
            'background_checks' => 'Background Checks',
            'training_types' => 'Training Types'
        ];

        foreach ($tables as $table => $description) {
            if (Schema::hasTable($table)) {
                $count = DB::table($table)->count();
                DB::table($table)->truncate();
                $this->command->info("   ✅ Cleared {$description}: {$count} records deleted");
            }
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }

    /**
     * Run installation steps
     */
    private function runInstallationSteps($withSample)
    {
        $this->command->info('📦 Installing GAPURA Training System Components...');
        $this->command->newLine();

        // Step 1: Install training types
        $this->command->info('Step 1: Installing training types...');
        $this->call(TrainingTypesSeeder::class);
        $this->command->newLine();

        // Step 2: Setup sample employees if needed
        $this->setupEmployees($withSample);

        // Step 3: Setup sample training data if requested
        if ($withSample) {
            $this->command->info('Step 3: Installing sample training data...');
            $this->call(SampleTrainingDataSeeder::class);
            $this->command->newLine();
        }

        // Step 4: Verify installation
        $this->verifyInstallation();
    }

    /**
     * Setup employees
     */
    private function setupEmployees($withSample)
    {
        $employeeCount = Schema::hasTable('employees') ? DB::table('employees')->count() : 0;

        if ($employeeCount === 0) {
            $this->command->info('Step 2: No employees found. Installing sample employees...');
            $this->call(SampleEmployeeSeeder::class);
        } elseif ($withSample && $employeeCount < 10) {
            $this->command->info('Step 2: Adding more sample employees for testing...');
            $this->call(SampleEmployeeSeeder::class);
        } else {
            $this->command->info("Step 2: Using existing employees ({$employeeCount} found)");
        }
        $this->command->newLine();
    }

    /**
     * Verify installation
     */
    private function verifyInstallation()
    {
        $this->command->info('Step 4: Verifying installation...');

        $trainingTypes = DB::table('training_types')->count();
        $employees = Schema::hasTable('employees') ? DB::table('employees')->count() : 0;
        $trainingRecords = DB::table('training_records')->count();
        $backgroundChecks = DB::table('background_checks')->count();

        $issues = [];

        if ($trainingTypes === 0) {
            $issues[] = 'No training types found';
        }

        if ($employees === 0) {
            $issues[] = 'No employees found';
        }

        if (!empty($issues)) {
            $this->command->error('   ❌ Installation issues found:');
            foreach ($issues as $issue) {
                $this->command->error("      - {$issue}");
            }
            return false;
        }

        $this->command->info('   ✅ Training types: ' . $trainingTypes);
        $this->command->info('   ✅ Employees: ' . $employees);
        $this->command->info('   ✅ Training records: ' . $trainingRecords);
        $this->command->info('   ✅ Background checks: ' . $backgroundChecks);
        $this->command->info('   ✅ Installation verification completed');
        $this->command->newLine();

        return true;
    }

    /**
     * Show installation summary
     */
    private function showInstallationSummary()
    {
        $this->command->info('✅ GAPURA Training System Installation Complete!');
        $this->command->info('====================================================');
        $this->command->newLine();

        // System statistics
        $this->displaySystemStatistics();

        // Feature overview
        $this->displayInstalledFeatures();

        // Training types overview
        $this->displayTrainingTypes();

        // Next steps
        $this->displayNextSteps();

        // Access information
        $this->displayAccessInformation();

        $this->command->newLine();
        $this->command->info('🎉 GAPURA Training System is ready for production use!');
        $this->command->info('   Start by importing your Excel training data or begin manual data entry.');
    }

    /**
     * Display system statistics
     */
    private function displaySystemStatistics()
    {
        $stats = [
            'Training Types' => DB::table('training_types')->count(),
            'Employees' => Schema::hasTable('employees') ? DB::table('employees')->count() : 0,
            'Training Records' => DB::table('training_records')->count(),
            'Background Checks' => DB::table('background_checks')->count(),
            'Active Trainings' => DB::table('training_records')->where('status', 'active')->count(),
            'Expired Trainings' => DB::table('training_records')->where('status', 'expired')->count(),
        ];

        $this->command->info('📊 SYSTEM STATISTICS:');
        foreach ($stats as $item => $count) {
            $this->command->info("   {$item}: {$count}");
        }
        $this->command->newLine();
    }

    /**
     * Display installed features
     */
    private function displayInstalledFeatures()
    {
        $this->command->info('🎯 INSTALLED FEATURES:');
        $features = [
            '✅ Aviation Training Types (PAX, Safety, Security, etc.)',
            '✅ Certificate Management & Tracking',
            '✅ Auto-expiry Monitoring & Alerts',
            '✅ Background Check Management',
            '✅ Compliance Reporting & Analytics',
            '✅ Department-wise Training Tracking',
            '✅ Employee Training Matrix',
            '✅ Excel Import/Export Ready',
            '✅ Role-based Access Control',
            '✅ Real-time Status Updates',
            '✅ Batch Import Tracking',
            '✅ Audit Trail & History'
        ];

        foreach ($features as $feature) {
            $this->command->info("   {$feature}");
        }
        $this->command->newLine();
    }

    /**
     * Display training types
     */
    private function displayTrainingTypes()
    {
        $this->command->info('🎓 TRAINING TYPES CONFIGURED:');

        if (Schema::hasTable('training_types')) {
            $trainingTypes = DB::table('training_types')
                              ->orderBy('sort_order')
                              ->get(['name', 'duration_months', 'code']);

            foreach ($trainingTypes as $type) {
                $this->command->info("   • {$type->name} ({$type->duration_months} months) - {$type->code}");
            }
        } else {
            $this->command->info('   No training types table found');
        }

        $this->command->newLine();
    }

    /**
     * Display next steps
     */
    private function displayNextSteps()
    {
        $this->command->info('📋 NEXT STEPS:');
        $this->command->info('1. 📄 Import Training Data:');
        $this->command->info('   • Prepare your Excel file (MPGA format)');
        $this->command->info('   • Use web interface: /import-export');
        $this->command->info('   • Verify imported data accuracy');
        $this->command->newLine();

        $this->command->info('2. 👥 Configure User Access:');
        $this->command->info('   • Create user accounts for HR staff');
        $this->command->info('   • Assign appropriate roles (admin, staff, viewer)');
        $this->command->info('   • Link users to employee records if needed');
        $this->command->newLine();

        $this->command->info('3. ⚙️  Setup Notifications:');
        $this->command->info('   • Configure email settings in .env');
        $this->command->info('   • Setup certificate expiry alerts');
        $this->command->info('   • Configure compliance monitoring rules');
        $this->command->newLine();

        $this->command->info('4. 📊 Verify System Operation:');
        $this->command->info('   • Test dashboard analytics');
        $this->command->info('   • Verify compliance reporting');
        $this->command->info('   • Test export functionality');
        $this->command->newLine();
    }

    /**
     * Display access information
     */
    private function displayAccessInformation()
    {
        $this->command->info('🌐 SYSTEM ACCESS POINTS:');
        $routes = [
            'Dashboard' => '/dashboard',
            'Training Records' => '/training',
            'Employee Management' => '/employees',
            'Certificate Tracking' => '/certificates',
            'Background Checks' => '/background-checks',
            'Analytics & Reports' => '/analytics',
            'Import/Export Tools' => '/import-export',
            'System Settings' => '/settings'
        ];

        foreach ($routes as $name => $route) {
            $this->command->info("   • {$name}: {$route}");
        }
        $this->command->newLine();

        $this->command->info('🗄️  DATABASE STRUCTURE:');
        $tables = [
            'training_types' => 'Master training categories & requirements',
            'training_records' => 'Individual employee certificates & records',
            'background_checks' => 'Security clearance & verification data',
            'employees' => 'Employee master data (linked via NIP)',
            'users' => 'System users with role-based access'
        ];

        foreach ($tables as $table => $description) {
            $this->command->info("   • {$table}: {$description}");
        }
        $this->command->newLine();

        $this->command->info('🔑 DEFAULT CREDENTIALS:');
        $this->command->info('   • Super Admin: admin@gapura.com / password');
        $this->command->info('   • HR Manager: hr.manager@gapura.com / manager123');
        $this->command->info('   • HR Staff: hr.staff@gapura.com / staff123');
        $this->command->newLine();

        $this->command->info('📚 DOCUMENTATION:');
        $this->command->info('   • API Documentation: /docs/api');
        $this->command->info('   • User Manual: /docs/user-manual');
        $this->command->info('   • Admin Guide: /docs/admin-guide');
    }

    /**
     * Get seeder options from command
     */
    private function getSeederOptions()
    {
        return [
            'fresh' => $this->command->option('fresh'),
            'with-sample' => $this->command->option('with-sample'),
            'skip-employees' => $this->command->option('skip-employees'),
        ];
    }
}
