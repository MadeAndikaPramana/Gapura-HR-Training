<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Employee;
use Carbon\Carbon;

class EmployeeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🧑‍💼 Seeding Employee Data...');

        // Sample GAPURA employees
        $employees = [
            [
                'nama_lengkap' => 'Ahmad Budi Santoso',
                'nip' => 'GAP001',
                'nik' => '3271012501850001',
            ],
            [
                'nama_lengkap' => 'Siti Nurhaliza',
                'nip' => 'GAP002',
                'nik' => '3271012501850002',
            ],
            [
                'nama_lengkap' => 'Dedi Kurniawan',
                'nip' => 'GAP003',
                'nik' => '3271012501850003',
            ],
            [
                'nama_lengkap' => 'Maya Sari Dewi',
                'nip' => 'GAP004',
                'nik' => '3271012501850004',
            ],
            [
                'nama_lengkap' => 'Rizki Pratama',
                'nip' => 'GAP005',
                'nik' => '3271012501850005',
            ],
            [
                'nama_lengkap' => 'Indira Putri',
                'nip' => 'GAP006',
                'nik' => '3271012501850006',
            ],
            [
                'nama_lengkap' => 'Bayu Setiawan',
                'nip' => 'GAP007',
                'nik' => '3271012501850007',
            ],
            [
                'nama_lengkap' => 'Rina Kartika',
                'nip' => 'GAP008',
                'nik' => '3271012501850008',
            ],
            [
                'nama_lengkap' => 'Arief Rahman',
                'nip' => 'GAP009',
                'nik' => '3271012501850009',
            ],
            [
                'nama_lengkap' => 'Lestari Wulandari',
                'nip' => 'GAP010',
                'nik' => '3271012501850010',
            ],
            [
                'nama_lengkap' => 'Fajar Nugroho',
                'nip' => 'GAP011',
                'nik' => '3271012501850011',
            ],
            [
                'nama_lengkap' => 'Diana Permata',
                'nip' => 'GAP012',
                'nik' => '3271012501850012',
            ],
            [
                'nama_lengkap' => 'Hendra Wijaya',
                'nip' => 'GAP013',
                'nik' => '3271012501850013',
            ],
            [
                'nama_lengkap' => 'Eka Fitria',
                'nip' => 'GAP014',
                'nik' => '3271012501850014',
            ],
            [
                'nama_lengkap' => 'Bambang Sutrisno',
                'nip' => 'GAP015',
                'nik' => '3271012501850015',
            ],
        ];

        foreach ($employees as $index => $employeeData) {
            Employee::create([
                'nama_lengkap' => $employeeData['nama_lengkap'],
                'nip' => $employeeData['nip'],
                'nik' => $employeeData['nik'],
                'unit_organisasi' => 'GAPURA ANGKASA',
                'jabatan' => 'Staff',
                'status_kerja' => 'Aktif',
                'tanggal_masuk' => Carbon::now()->subMonths(rand(1, 60)), // Random join date in last 5 years
            ]);

            $this->command->info("   ✅ Created: {$employeeData['nama_lengkap']} ({$employeeData['nip']})");
        }

        $this->command->newLine();
        $this->command->info('✅ Employee seeding completed!');
        $this->command->info('📊 Total employees created: ' . count($employees));
    }
}
