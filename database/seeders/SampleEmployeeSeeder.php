<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Employee;
use Carbon\Carbon;

class SampleEmployeeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Create sample employees based on MPGA structure
     */
    public function run(): void
    {
        $this->command->info('👥 Creating sample employees...');

        // Clear existing sample employees
        Employee::where('nip', 'like', '9999%')->delete();

        $departments = [
            'DEDICATED', 'LOADING', 'RAMP', 'LOCO', 'ULD',
            'LOST & FOUND', 'CARGO', 'ARRIVAL', 'GSE OPERATOR',
            'FLOP', 'AVSEC', 'PORTER'
        ];

        $sampleEmployees = [
            [
                'nip' => '9999001',
                'nama_lengkap' => 'I KETUT TRAINING DEMO',
                'jenis_kelamin' => 'L',
                'tempat_lahir' => 'Denpasar',
                'tanggal_lahir' => '1990-05-15',
                'unit_organisasi' => 'DEDICATED',
                'status_pegawai' => 'PEGAWAI TETAP',
                'jabatan' => 'CONTROLLER',
            ],
            [
                'nip' => '9999002',
                'nama_lengkap' => 'NI MADE SAMPLE EMPLOYEE',
                'jenis_kelamin' => 'P',
                'tempat_lahir' => 'Gianyar',
                'tanggal_lahir' => '1988-08-22',
                'unit_organisasi' => 'LOADING',
                'status_pegawai' => 'PEGAWAI TETAP',
                'jabatan' => 'SUPERVISOR',
            ],
            [
                'nip' => '9999003',
                'nama_lengkap' => 'I WAYAN TEST SYSTEM',
                'jenis_kelamin' => 'L',
                'tempat_lahir' => 'Ubud',
                'tanggal_lahir' => '1992-03-10',
                'unit_organisasi' => 'RAMP',
                'status_pegawai' => 'PKWT',
                'jabatan' => 'STAFF',
            ],
            [
                'nip' => '9999004',
                'nama_lengkap' => 'NI KADEK PROTOTYPE DATA',
                'jenis_kelamin' => 'P',
                'tempat_lahir' => 'Sanur',
                'tanggal_lahir' => '1985-12-07',
                'unit_organisasi' => 'CARGO',
                'status_pegawai' => 'PEGAWAI TETAP',
                'jabatan' => 'MANAGER',
            ],
            [
                'nip' => '9999005',
                'nama_lengkap' => 'I KOMANG EXAMPLE USER',
                'jenis_kelamin' => 'L',
                'tempat_lahir' => 'Canggu',
                'tanggal_lahir' => '1987-07-19',
                'unit_organisasi' => 'AVSEC',
                'status_pegawai' => 'TAD PAKET SDM',
                'jabatan' => 'STAFF',
            ],
            [
                'nip' => '9999006',
                'nama_lengkap' => 'NI LUH DEMO TRAINING',
                'jenis_kelamin' => 'P',
                'tempat_lahir' => 'Kuta',
                'tanggal_lahir' => '1991-11-03',
                'unit_organisasi' => 'ARRIVAL',
                'status_pegawai' => 'PEGAWAI TETAP',
                'jabatan' => 'CONTROLLER',
            ],
            [
                'nip' => '9999007',
                'nama_lengkap' => 'I GEDE SAMPLE DATA',
                'jenis_kelamin' => 'L',
                'tempat_lahir' => 'Seminyak',
                'tanggal_lahir' => '1989-04-26',
                'unit_organisasi' => 'GSE OPERATOR',
                'status_pegawai' => 'PKWT',
                'jabatan' => 'SUPERVISOR',
            ],
            [
                'nip' => '9999008',
                'nama_lengkap' => 'NI PUTU TEST EMPLOYEE',
                'jenis_kelamin' => 'P',
                'tempat_lahir' => 'Jimbaran',
                'tanggal_lahir' => '1993-09-14',
                'unit_organisasi' => 'ULD',
                'status_pegawai' => 'PEGAWAI TETAP',
                'jabatan' => 'STAFF',
            ],
            [
                'nip' => '9999009',
                'nama_lengkap' => 'I NYOMAN PROTOTYPE SYS',
                'jenis_kelamin' => 'L',
                'tempat_lahir' => 'Nusa Dua',
                'tanggal_lahir' => '1986-01-30',
                'unit_organisasi' => 'FLOP',
                'status_pegawai' => 'TAD PAKET PEKERJAAN',
                'jabatan' => 'STAFF',
            ],
            [
                'nip' => '9999010',
                'nama_lengkap' => 'NI WAYAN EXAMPLE SYS',
                'jenis_kelamin' => 'P',
                'tempat_lahir' => 'Pecatu',
                'tanggal_lahir' => '1994-06-18',
                'unit_organisasi' => 'PORTER',
                'status_pegawai' => 'PEGAWAI TETAP',
                'jabatan' => 'CONTROLLER',
            ],
        ];

        $created = 0;
        foreach ($sampleEmployees as $empData) {
            $birthDate = Carbon::parse($empData['tanggal_lahir']);

            $employee = Employee::create([
                'nip' => $empData['nip'],
                'nama_lengkap' => $empData['nama_lengkap'],
                'jenis_kelamin' => $empData['jenis_kelamin'],
                'tempat_lahir' => $empData['tempat_lahir'],
                'tanggal_lahir' => $birthDate,
                'usia' => $birthDate->age,
                'unit_organisasi' => $empData['unit_organisasi'],
                'status_pegawai' => $empData['status_pegawai'],
                'status_kerja' => 'Aktif',
                'jabatan' => $empData['jabatan'],
                'nama_jabatan' => $empData['jabatan'],
                'lokasi_kerja' => 'Bandar Udara Ngurah Rai',
                'cabang' => 'DPS',
                'provider' => 'PT Gapura Angkasa',
                'alamat' => 'Bali, Indonesia',
                'handphone' => '08' . rand(1000000000, 9999999999),
                'email' => strtolower(str_replace(' ', '.', $empData['nama_lengkap'])) . '@gapura.com',
                'tmt_mulai_kerja' => $birthDate->copy()->addYears(rand(22, 30)),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $created++;
        }

        $this->command->info("   ✅ Created {$created} sample employees");
        $this->command->info('   📊 Departments covered: ' . count($departments));
        $this->command->info('   🎯 Ready for training data assignment');
        $this->command->newLine();

        // Show summary
        $this->showEmployeeSummary();
    }

    /**
     * Show employee summary
     */
    private function showEmployeeSummary()
    {
        $this->command->info('👥 SAMPLE EMPLOYEES SUMMARY:');
        $this->command->info('===============================');

        $employees = Employee::where('nip', 'like', '9999%')->get();

        foreach ($employees as $emp) {
            $this->command->info(sprintf(
                '   %s - %s (%s) - %s',
                $emp->nip,
                $emp->nama_lengkap,
                $emp->unit_organisasi,
                $emp->jabatan
            ));
        }

        $this->command->newLine();
        $this->command->info('📋 EMPLOYEE DISTRIBUTION:');

        $byDepartment = $employees->groupBy('unit_organisasi');
        foreach ($byDepartment as $dept => $emps) {
            $this->command->info("   {$dept}: {$emps->count()} employees");
        }

        $this->command->newLine();
        $this->command->info('🎯 NOTE: Sample employees use NIP prefix 9999 for easy identification');
    }
}
