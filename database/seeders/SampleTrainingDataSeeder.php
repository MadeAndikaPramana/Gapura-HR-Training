<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Employee;
use App\Models\TrainingType;
use App\Models\TrainingRecord;
use App\Models\BackgroundCheck;
use Carbon\Carbon;

class SampleTrainingDataSeeder extends Seeder
{
    /**
     * Seed sample training data for testing
     * This creates realistic sample data based on MPGA Excel structure
     */
    public function run(): void
    {
        $this->command->info('🧪 Creating sample training data for testing...');

        // Get training types
        $trainingTypes = TrainingType::active()->get();
        if ($trainingTypes->isEmpty()) {
            $this->command->error('❌ No training types found! Run TrainingTypesSeeder first.');
            return;
        }

        // Get sample employees (first 10 or create sample ones)
        $employees = Employee::limit(10)->get();
        if ($employees->isEmpty()) {
            $this->command->warn('⚠️  No employees found. Creating sample employees...');
            $employees = $this->createSampleEmployees();
        }

        $this->command->info("   📊 Working with {$employees->count()} employees");
        $this->command->info("   📊 Working with {$trainingTypes->count()} training types");

        // Create training records
        $totalRecords = 0;
        $backgroundChecks = 0;

        foreach ($employees as $employee) {
            // Create background check for each employee
            $this->createBackgroundCheck($employee);
            $backgroundChecks++;

            // Create 3-4 training records per employee (random)
            $trainingCount = rand(3, min(4, $trainingTypes->count()));
            $selectedTrainings = $trainingTypes->random($trainingCount);

            foreach ($selectedTrainings as $trainingType) {
                $this->createTrainingRecord($employee, $trainingType);
                $totalRecords++;
            }
        }

        $this->command->info("   ✅ Created {$totalRecords} training records");
        $this->command->info("   ✅ Created {$backgroundChecks} background checks");

        // Show summary
        $this->showSampleDataSummary();
    }

    /**
     * Create sample employees if none exist
     */
    private function createSampleEmployees()
    {
        $sampleEmployees = [
            [
                'nip' => '2160001',
                'nama_lengkap' => 'I KETUT SAMPLE',
                'unit_organisasi' => 'DEDICATED',
                'jenis_kelamin' => 'L',
            ],
            [
                'nip' => '2160002',
                'nama_lengkap' => 'NI MADE TESTING',
                'unit_organisasi' => 'LOADING',
                'jenis_kelamin' => 'P',
            ],
            [
                'nip' => '2160003',
                'nama_lengkap' => 'I WAYAN DEMO',
                'unit_organisasi' => 'RAMP',
                'jenis_kelamin' => 'L',
            ],
            [
                'nip' => '2160004',
                'nama_lengkap' => 'NI KADEK EXAMPLE',
                'unit_organisasi' => 'CARGO',
                'jenis_kelamin' => 'P',
            ],
            [
                'nip' => '2160005',
                'nama_lengkap' => 'I KOMANG PROTOTYPE',
                'unit_organisasi' => 'AVSEC',
                'jenis_kelamin' => 'L',
            ],
        ];

        $employees = collect();
        foreach ($sampleEmployees as $empData) {
            $employee = Employee::create(array_merge($empData, [
                'status_pegawai' => 'PEGAWAI TETAP',
                'status_kerja' => 'Aktif',
                'tempat_lahir' => 'Denpasar',
                'tanggal_lahir' => '1990-01-01',
                'usia' => 34,
                'created_at' => now(),
                'updated_at' => now(),
            ]));
            $employees->push($employee);
        }

        return $employees;
    }

    /**
     * Create background check for employee
     */
    private function createBackgroundCheck($employee)
    {
        $checkDate = Carbon::now()->subDays(rand(30, 365));
        $validUntil = $checkDate->copy()->addYears(3); // 3 years validity

        BackgroundCheck::create([
            'employee_nip' => $employee->nip,
            'check_date' => $checkDate,
            'check_type' => 'security_clearance',
            'status' => 'passed',
            'valid_until' => $validUntil,
            'conducted_by' => 'Security Department',
            'reference_number' => 'BGC-' . $employee->nip . '-' . $checkDate->format('Y'),
            'notes' => 'Sample background check - automatically generated',
        ]);
    }

    /**
     * Create training record for employee
     */
    private function createTrainingRecord($employee, $trainingType)
    {
        // Generate realistic dates
        $validFrom = Carbon::now()->subDays(rand(60, 730)); // 2 months to 2 years ago
        $validUntil = $validFrom->copy()->addMonths($trainingType->duration_months);

        // Determine status based on expiry
        $status = 'active';
        if ($validUntil->isPast()) {
            $status = 'expired';
        } elseif ($validUntil->diffInDays(now()) <= 30) {
            $status = 'expiring_soon';
        }

        // Generate certificate number based on format
        $certNumber = $this->generateCertificateNumber($trainingType, $validFrom);

        TrainingRecord::create([
            'employee_nip' => $employee->nip,
            'training_type_id' => $trainingType->id,
            'certificate_number' => $certNumber,
            'issued_date' => $validFrom,
            'valid_from' => $validFrom,
            'valid_until' => $validUntil,
            'status' => $status,
            'issuing_authority' => 'GLC Training Center',
            'training_location' => 'Ngurah Rai Airport',
            'notes' => 'Sample training record - automatically generated',
            'import_batch_id' => 'SAMPLE_' . now()->format('Ymd'),
            'imported_at' => now(),
        ]);
    }

    /**
     * Generate realistic certificate number
     */
    private function generateCertificateNumber($trainingType, $validFrom)
    {
        $number = str_pad(rand(1000, 9999), 6, '0', STR_PAD_LEFT);
        $month = strtoupper($validFrom->format('M'));
        $year = $validFrom->format('Y');

        if ($trainingType->code === 'AVIATION_SECURITY_AWARENESS') {
            return "GLC/GM/OPR-{$number}/{$month}/{$year}";
        } else {
            return "GLC/OPR-{$number}/{$month}/{$year}";
        }
    }

    /**
     * Show summary of created sample data
     */
    private function showSampleDataSummary()
    {
        $this->command->newLine();
        $this->command->info('📊 SAMPLE DATA SUMMARY:');

        // Training records by status
        $activeRecords = TrainingRecord::where('status', 'active')->count();
        $expiredRecords = TrainingRecord::where('status', 'expired')->count();
        $expiringRecords = TrainingRecord::where('status', 'expiring_soon')->count();

        $this->command->info("   Active Records: {$activeRecords}");
        $this->command->info("   Expired Records: {$expiredRecords}");
        $this->command->info("   Expiring Soon: {$expiringRecords}");

        // Background checks
        $validBgChecks = BackgroundCheck::where('status', 'passed')->count();
        $this->command->info("   Valid Background Checks: {$validBgChecks}");

        // Training types coverage
        $this->command->newLine();
        $this->command->info('📋 TRAINING TYPE COVERAGE:');
        foreach (TrainingType::withCount('trainingRecords')->get() as $type) {
            $this->command->info("   {$type->name}: {$type->training_records_count} records");
        }

        $this->command->newLine();
        $this->command->info('🎯 SAMPLE DATA FEATURES:');
        $this->command->info('   ✅ Realistic date ranges');
        $this->command->info('   ✅ Mixed status records (active, expired, expiring)');
        $this->command->info('   ✅ Proper certificate number formats');
        $this->command->info('   ✅ Valid background checks');
        $this->command->info('   ✅ Department distribution');

        $this->command->newLine();
        $this->command->info('🚀 Sample data ready for testing the training system!');
    }
}
