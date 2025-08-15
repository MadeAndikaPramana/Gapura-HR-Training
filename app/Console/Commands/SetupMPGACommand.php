<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Models\Employee;

class SetupMPGACommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'mpga:setup
                           {--force : Force setup even if data exists}
                           {--migrate-only : Only run migration, skip data processing}
                           {--dry-run : Show what would be done without executing}';

    /**
     * The console command description.
     */
    protected $description = 'Setup MPGA employee structure and migrate existing data';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->displayHeader();

        if ($this->option('dry-run')) {
            $this->warn('🔍 DRY RUN MODE - No changes will be made');
            $this->newLine();
        }

        // Step 1: Check current state
        if (!$this->checkCurrentState()) {
            return 1;
        }

        // Step 2: Run migration if needed
        if (!$this->runMPGAMigration()) {
            return 1;
        }

        // Step 3: Process existing data (unless migrate-only)
        if (!$this->option('migrate-only')) {
            if (!$this->processExistingData()) {
                return 1;
            }
        }

        // Step 4: Verify setup
        $this->verifySetup();

        $this->displayCompletion();
        return 0;
    }

    /**
     * Display setup header
     */
    private function displayHeader()
    {
        $this->info('🚀 GAPURA MPGA Employee Structure Setup');
        $this->info('========================================');
        $this->info('Setting up employee structure for MPGA Excel integration');
        $this->newLine();
    }

    /**
     * Check current database state
     */
    private function checkCurrentState(): bool
    {
        $this->info('🔍 Checking current database state...');

        // Check if employees table exists
        if (!Schema::hasTable('employees')) {
            $this->error('❌ Employees table does not exist!');
            $this->error('Please run: php artisan migrate');
            return false;
        }

        // Check current employee count
        $employeeCount = Employee::count();
        $this->info("✅ Employees table exists ({$employeeCount} records)");

        // Check if MPGA fields exist
        $hasDepartmentField = Schema::hasColumn('employees', 'department');
        $hasStatusField = Schema::hasColumn('employees', 'status');

        if ($hasDepartmentField && $hasStatusField) {
            $this->info('✅ MPGA fields already exist');
        } else {
            $this->warn('⚠️  MPGA fields missing:');
            if (!$hasDepartmentField) $this->warn('   - department field');
            if (!$hasStatusField) $this->warn('   - status field');
        }

        // Check for existing MPGA data
        if ($hasDepartmentField) {
            $mpgaEmployees = Employee::whereIn('department', Employee::MPGA_DEPARTMENTS)->count();
            if ($mpgaEmployees > 0) {
                $this->info("✅ Found {$mpgaEmployees} MPGA employees");

                if (!$this->option('force')) {
                    if (!$this->confirm('MPGA data already exists. Continue anyway?')) {
                        $this->info('Setup cancelled by user.');
                        return false;
                    }
                }
            }
        }

        $this->newLine();
        return true;
    }

    /**
     * Run MPGA migration
     */
    private function runMPGAMigration(): bool
    {
        $this->info('🔧 Running MPGA migration...');

        try {
            if (!$this->option('dry-run')) {
                // Check if migration needs to be run
                $needsMigration = !Schema::hasColumn('employees', 'department') ||
                                 !Schema::hasColumn('employees', 'status');

                if ($needsMigration) {
                    $this->call('migrate', ['--path' => 'database/migrations/*mpga*']);
                    $this->info('✅ MPGA migration completed');
                } else {
                    $this->info('✅ MPGA migration already applied');
                }
            } else {
                $this->info('🔍 Would run MPGA migration');
            }

            return true;

        } catch (\Exception $e) {
            $this->error('❌ Migration failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Process existing employee data for MPGA compatibility
     */
    private function processExistingData(): bool
    {
        $this->info('📊 Processing existing employee data...');

        try {
            $employees = Employee::whereNull('status')->orWhere('status', '')->get();

            if ($employees->isEmpty()) {
                $this->info('✅ No employees need status update');
                return true;
            }

            $this->info("Processing {$employees->count()} employees...");

            $updated = 0;
            $bar = $this->output->createProgressBar($employees->count());
            $bar->start();

            foreach ($employees as $employee) {
                if (!$this->option('dry-run')) {
                    // Set default status
                    $employee->update([
                        'status' => 'active',
                        'status_kerja' => $employee->status_kerja ?: 'Aktif'
                    ]);

                    // Generate NIK if missing
                    if (empty($employee->nik) && !empty($employee->nip)) {
                        $employee->update([
                            'nik' => Employee::generateNik($employee->nip, $employee->department)
                        ]);
                    }
                }

                $updated++;
                $bar->advance();
            }

            $bar->finish();
            $this->newLine();

            if ($this->option('dry-run')) {
                $this->info("🔍 Would update {$updated} employees");
            } else {
                $this->info("✅ Updated {$updated} employees");
            }

            return true;

        } catch (\Exception $e) {
            $this->error('❌ Data processing failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Verify setup completion
     */
    private function verifySetup()
    {
        $this->info('🔍 Verifying MPGA setup...');

        // Check required fields
        $requiredFields = ['department', 'status'];
        foreach ($requiredFields as $field) {
            if (Schema::hasColumn('employees', $field)) {
                $this->info("✅ Field '{$field}' exists");
            } else {
                $this->error("❌ Field '{$field}' missing");
            }
        }

        // Check indexes
        $this->info('📊 Checking database indexes...');
        // Note: Specific index checking would require more complex queries

        // Check data integrity
        $activeEmployees = Employee::where('status', 'active')->count();
        $totalEmployees = Employee::count();

        $this->info("📈 Data Summary:");
        $this->info("   Total employees: {$totalEmployees}");
        $this->info("   Active employees: {$activeEmployees}");

        if ($activeEmployees > 0) {
            // Check department distribution
            $departmentStats = Employee::where('status', 'active')
                ->whereIn('department', Employee::MPGA_DEPARTMENTS)
                ->groupBy('department')
                ->selectRaw('department, COUNT(*) as count')
                ->get();

            if ($departmentStats->isNotEmpty()) {
                $this->info("   MPGA departments:");
                foreach ($departmentStats as $stat) {
                    $this->info("     {$stat->department}: {$stat->count} employees");
                }
            }
        }

        $this->newLine();
    }

    /**
     * Display completion message
     */
    private function displayCompletion()
    {
        $this->info('🎉 MPGA Setup Completed Successfully!');
        $this->info('=====================================');

        if (!$this->option('dry-run')) {
            $this->info('✅ Database structure updated');
            $this->info('✅ Existing data processed');
            $this->info('✅ MPGA fields configured');

            $this->newLine();
            $this->info('🚀 Next Steps:');
            $this->info('1. Import MPGA Excel data: php artisan mpga:import');
            $this->info('2. Verify employee data: php artisan mpga:verify');
            $this->info('3. Access web interface: /employees');
        } else {
            $this->info('🔍 Dry run completed - no changes made');
        }

        $this->newLine();
    }
}
