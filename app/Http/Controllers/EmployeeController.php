<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;

class EmployeeController extends Controller
{
    /**
     * Display employee listing
     */
    public function index(Request $request)
    {
        // Sample employee data (seperti di repo Firman)
        $employees = collect([
            (object)[
                'id' => 1,
                'nik' => '3671041234567890',
                'nip' => 'GA20240001',
                'nama_lengkap' => 'Ahmad Bagus Prasetyo',
                'jenis_kelamin' => 'L',
                'tempat_lahir' => 'Denpasar',
                'tanggal_lahir' => '1985-03-15',
                'usia' => 39,
                'alamat' => 'Jl. Sunset Road No. 123, Denpasar, Bali',
                'handphone' => '081234567890',
                'email' => 'ahmad.bagus@gapura.com',
                'unit_organisasi' => 'AIRSIDE',
                'jabatan' => 'Supervisor Ground Handling',
                'status_pegawai' => 'PEGAWAI TETAP',
                'status_kerja' => 'Aktif',
                'kelompok_jabatan' => 'Staff'
            ],
            (object)[
                'id' => 2,
                'nik' => '3671041234567891',
                'nip' => 'GA20240002',
                'nama_lengkap' => 'Ni Made Sari Dewi',
                'jenis_kelamin' => 'P',
                'tempat_lahir' => 'Gianyar',
                'tanggal_lahir' => '1990-07-22',
                'usia' => 34,
                'alamat' => 'Jl. Raya Ubud No. 456, Gianyar, Bali',
                'handphone' => '081234567891',
                'email' => 'made.sari@gapura.com',
                'unit_organisasi' => 'LANDSIDE',
                'jabatan' => 'Check-in Counter Staff',
                'status_pegawai' => 'PEGAWAI TETAP',
                'status_kerja' => 'Aktif',
                'kelompok_jabatan' => 'Staff'
            ],
            (object)[
                'id' => 3,
                'nik' => '3671041234567892',
                'nip' => 'GA20240003',
                'nama_lengkap' => 'I Wayan Karta',
                'jenis_kelamin' => 'L',
                'tempat_lahir' => 'Tabanan',
                'tanggal_lahir' => '1988-11-10',
                'usia' => 36,
                'alamat' => 'Jl. Puputan No. 789, Tabanan, Bali',
                'handphone' => '081234567892',
                'email' => 'wayan.karta@gapura.com',
                'unit_organisasi' => 'SSQC',
                'jabatan' => 'Security Officer',
                'status_pegawai' => 'PKWT',
                'status_kerja' => 'Aktif',
                'kelompok_jabatan' => 'Staff'
            ],
            (object)[
                'id' => 4,
                'nik' => '3671041234567893',
                'nip' => 'GA20240004',
                'nama_lengkap' => 'Putu Eka Wijaya',
                'jenis_kelamin' => 'L',
                'tempat_lahir' => 'Badung',
                'tanggal_lahir' => '1992-05-18',
                'usia' => 32,
                'alamat' => 'Jl. Bypass Ngurah Rai No. 321, Badung, Bali',
                'handphone' => '081234567893',
                'email' => 'putu.eka@gapura.com',
                'unit_organisasi' => 'BACK OFFICE',
                'jabatan' => 'HR Specialist',
                'status_pegawai' => 'PEGAWAI TETAP',
                'status_kerja' => 'Aktif',
                'kelompok_jabatan' => 'Staff'
            ],
            (object)[
                'id' => 5,
                'nik' => '3671041234567894',
                'nip' => 'GA20240005',
                'nama_lengkap' => 'Kadek Rina Sari',
                'jenis_kelamin' => 'P',
                'tempat_lahir' => 'Klungkung',
                'tanggal_lahir' => '1987-12-03',
                'usia' => 37,
                'alamat' => 'Jl. Gajah Mada No. 654, Klungkung, Bali',
                'handphone' => '081234567894',
                'email' => 'kadek.rina@gapura.com',
                'unit_organisasi' => 'ANCILLARY',
                'jabatan' => 'Commercial Executive',
                'status_pegawai' => 'PEGAWAI TETAP',
                'status_kerja' => 'Aktif',
                'kelompok_jabatan' => 'Staff'
            ]
        ]);

        // Apply search filter
        $search = $request->get('search', '');
        if ($search) {
            $employees = $employees->filter(function($employee) use ($search) {
                return stripos($employee->nama_lengkap, $search) !== false ||
                       stripos($employee->nip, $search) !== false ||
                       stripos($employee->nik, $search) !== false;
            });
        }

        // Apply unit filter
        $unit = $request->get('unit_organisasi', 'all');
        if ($unit !== 'all') {
            $employees = $employees->filter(function($employee) use ($unit) {
                return $employee->unit_organisasi === $unit;
            });
        }

        // Apply status filter
        $status = $request->get('status_pegawai', 'all');
        if ($status !== 'all') {
            $employees = $employees->filter(function($employee) use ($status) {
                return $employee->status_pegawai === $status;
            });
        }

        // Mock pagination
        $pagination = (object)[
            'current_page' => 1,
            'last_page' => 1,
            'per_page' => 20,
            'total' => $employees->count(),
            'from' => 1,
            'to' => $employees->count(),
            'data' => $employees->values(),
            'first_page_url' => url()->current() . '?page=1',
            'last_page_url' => url()->current() . '?page=1',
            'next_page_url' => null,
            'prev_page_url' => null
        ];

        return Inertia::render('Employees/Index', [
            'employees' => $pagination,
            'filterOptions' => [
                'unitOrganisasi' => [
                    'AIRSIDE' => 'Airside Operations',
                    'LANDSIDE' => 'Landside Operations',
                    'SSQC' => 'Security, Safety, Quality & Compliance',
                    'BACK OFFICE' => 'Back Office',
                    'ANCILLARY' => 'Ancillary Services'
                ],
                'statusPegawai' => [
                    'PEGAWAI TETAP' => 'Pegawai Tetap',
                    'PKWT' => 'PKWT',
                    'TAD PAKET SDM' => 'TAD Paket SDM',
                    'TAD PAKET PEKERJAAN' => 'TAD Paket Pekerjaan'
                ]
            ],
            'statistics' => [
                'totalEmployees' => $employees->count(),
                'activeEmployees' => $employees->where('status_kerja', 'Aktif')->count(),
                'newEmployees' => 2,
                'totalUnits' => 5
            ],
            'filters' => [
                'search' => $search,
                'unit_organisasi' => $unit,
                'status_pegawai' => $status
            ],
            'title' => 'Management Karyawan',
            'subtitle' => 'Kelola data karyawan PT Gapura Angkasa - Bandar Udara Ngurah Rai'
        ]);
    }

    /**
     * Show create form
     */
    public function create()
    {
        return Inertia::render('Employees/Create', [
            'title' => 'Tambah Karyawan Baru',
            'unitOptions' => [
                'AIRSIDE' => 'Airside Operations',
                'LANDSIDE' => 'Landside Operations',
                'SSQC' => 'Security, Safety, Quality & Compliance',
                'BACK OFFICE' => 'Back Office',
                'ANCILLARY' => 'Ancillary Services'
            ],
            'statusOptions' => [
                'PEGAWAI TETAP' => 'Pegawai Tetap',
                'PKWT' => 'PKWT',
                'TAD PAKET SDM' => 'TAD Paket SDM',
                'TAD PAKET PEKERJAAN' => 'TAD Paket Pekerjaan'
            ]
        ]);
    }

    /**
     * Store new employee
     */
    public function store(Request $request)
    {
        // Validation rules
        $request->validate([
            'nik' => 'required|string|max:20',
            'nip' => 'required|string|max:20',
            'nama_lengkap' => 'required|string|max:255',
            'jenis_kelamin' => 'required|in:L,P',
            'unit_organisasi' => 'required|string',
            'status_pegawai' => 'required|string'
        ]);

        // For demo purposes, just redirect with success
        return redirect()->route('employees.index')
            ->with('success', 'Karyawan berhasil ditambahkan');
    }

    /**
     * Show edit form
     */
    public function edit($id)
    {
        // Mock employee data
        $employee = (object)[
            'id' => $id,
            'nik' => '3671041234567890',
            'nip' => 'GA20240001',
            'nama_lengkap' => 'Ahmad Bagus Prasetyo',
            'jenis_kelamin' => 'L',
            'tempat_lahir' => 'Denpasar',
            'tanggal_lahir' => '1985-03-15',
            'alamat' => 'Jl. Sunset Road No. 123, Denpasar, Bali',
            'handphone' => '081234567890',
            'email' => 'ahmad.bagus@gapura.com',
            'unit_organisasi' => 'AIRSIDE',
            'jabatan' => 'Supervisor Ground Handling',
            'status_pegawai' => 'PEGAWAI TETAP'
        ];

        return Inertia::render('Employees/Edit', [
            'employee' => $employee,
            'title' => 'Edit Karyawan',
            'unitOptions' => [
                'AIRSIDE' => 'Airside Operations',
                'LANDSIDE' => 'Landside Operations',
                'SSQC' => 'Security, Safety, Quality & Compliance',
                'BACK OFFICE' => 'Back Office',
                'ANCILLARY' => 'Ancillary Services'
            ],
            'statusOptions' => [
                'PEGAWAI TETAP' => 'Pegawai Tetap',
                'PKWT' => 'PKWT',
                'TAD PAKET SDM' => 'TAD Paket SDM',
                'TAD PAKET PEKERJAAN' => 'TAD Paket Pekerjaan'
            ]
        ]);
    }

    /**
     * Update employee
     */
    public function update(Request $request, $id)
    {
        // Validation
        $request->validate([
            'nik' => 'required|string|max:20',
            'nip' => 'required|string|max:20',
            'nama_lengkap' => 'required|string|max:255',
            'jenis_kelamin' => 'required|in:L,P',
            'unit_organisasi' => 'required|string',
            'status_pegawai' => 'required|string'
        ]);

        return redirect()->route('employees.index')
            ->with('success', 'Data karyawan berhasil diupdate');
    }

    /**
     * Delete employee
     */
    public function destroy($id)
    {
        return redirect()->route('employees.index')
            ->with('success', 'Karyawan berhasil dihapus');
    }

    /**
     * Export employees
     */
    public function export()
    {
        $csvContent = "NIK,NIP,Nama Lengkap,Jenis Kelamin,Unit Organisasi,Jabatan,Status\n";
        $csvContent .= '"3671041234567890","GA20240001","Ahmad Bagus Prasetyo","L","AIRSIDE","Supervisor","PEGAWAI TETAP"' . "\n";
        $csvContent .= '"3671041234567891","GA20240002","Ni Made Sari Dewi","P","LANDSIDE","Staff","PEGAWAI TETAP"' . "\n";

        return response($csvContent, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="employees_export.csv"'
        ]);
    }
}
