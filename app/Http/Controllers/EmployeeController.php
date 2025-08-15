<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Http\Requests\EmployeeRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class EmployeeController extends Controller
{
    /**
     * Display a listing of employees with MPGA-specific filters
     * UPDATED for MPGA: Enhanced search, department filter, unit filter
     */
    public function index(Request $request)
{
    try {
        // Build query
        $query = Employee::query();
        $query->where('status', 'active');

        // Apply search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('nama_lengkap', 'like', "%{$search}%")
                  ->orWhere('nip', 'like', "%{$search}%")
                  ->orWhere('nik', 'like', "%{$search}%");
            });
        }

        // Apply filters
        if ($request->filled('department')) {
            $query->where('department', $request->department);
        }

        if ($request->filled('unit_organisasi')) {
            $query->where('unit_organisasi', $request->unit_organisasi);
        }

        if ($request->filled('status_pegawai')) {
            $query->where('status_pegawai', $request->status_pegawai);
        }

        // Get results
        $employees = $query->orderBy('nama_lengkap')
                          ->paginate(20)
                          ->withQueryString();

        // Get filter options
        $filterOptions = [
            'departments' => Employee::distinct()->pluck('department')->filter(),
            'units' => Employee::distinct()->pluck('unit_organisasi')->filter(),
            'statusPegawai' => Employee::STATUS_PEGAWAI_OPTIONS ?? [
                'PEGAWAI TETAP', 'PKWT', 'TAD PAKET SDM', 'TAD PAKET PEKERJAAN'
            ],
        ];

        // Get statistics
        $statistics = [
            'total_employees' => Employee::where('status', 'active')->count(),
            'total_departments' => Employee::distinct('department')->count(),
        ];

        return Inertia::render('Employees/Index', [
            'employees' => $employees,
            'filters' => $request->only(['search', 'department', 'unit_organisasi', 'status_pegawai']), // FIX: Always send filters
            'filterOptions' => $filterOptions,
            'statistics' => $statistics,
            'title' => 'Management Karyawan',
            'subtitle' => 'Kelola data karyawan PT Gapura Angkasa',
        ]);

    } catch (\Exception $e) {
        \Log::error('Employee Index Error: ' . $e->getMessage());

        return Inertia::render('Employees/Index', [
            'employees' => ['data' => [], 'total' => 0],
            'filters' => [], // FIX: Always send empty filters if error
            'filterOptions' => [],
            'statistics' => [],
            'error' => 'Terjadi kesalahan saat memuat data karyawan.'
        ]);
    }
}

    /**
     * Store a newly created employee with MPGA validation
     * UPDATED for MPGA: Use EmployeeRequest, auto-generate NIK
     */
    public function store(EmployeeRequest $request)
    {
        try {
            DB::beginTransaction();

            $validated = $request->validated();

            // Auto-generate NIK if not provided
            if (empty($validated['nik'])) {
                $validated['nik'] = Employee::generateNik(
                    $validated['nip'],
                    $validated['department']
                );
            }

            // Calculate age if birth date provided
            if (!empty($validated['tanggal_lahir']) && empty($validated['usia'])) {
                $validated['usia'] = \Carbon\Carbon::parse($validated['tanggal_lahir'])->age;
            }

            // Create employee
            $employee = Employee::create($validated);

            DB::commit();

            Log::info('Employee Created Successfully', [
                'employee_id' => $employee->id,
                'nip' => $employee->nip,
                'nama_lengkap' => $employee->nama_lengkap,
                'department' => $employee->department
            ]);

            return redirect()->route('employees.index')
                ->with([
                    'success' => "Karyawan {$employee->nama_lengkap} berhasil ditambahkan!",
                    'notification' => [
                        'type' => 'success',
                        'title' => 'Karyawan Ditambahkan',
                        'message' => "Karyawan {$employee->nama_lengkap} (NIP: {$employee->nip}) berhasil ditambahkan ke departemen {$employee->department}."
                    ]
                ]);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Employee Store Error', [
                'error' => $e->getMessage(),
                'data' => $request->all()
            ]);

            return redirect()->back()
                ->withInput()
                ->with([
                    'error' => 'Gagal menambahkan karyawan. ' . $e->getMessage(),
                    'notification' => [
                        'type' => 'error',
                        'title' => 'Gagal Menambahkan',
                        'message' => 'Terjadi kesalahan saat menambahkan karyawan. Silakan coba lagi.'
                    ]
                ]);
        }
    }

    /**
     * Update the specified employee with MPGA validation
     * UPDATED for MPGA: Handle department changes, NIK regeneration
     */
    public function update(EmployeeRequest $request, Employee $employee)
    {
        try {
            DB::beginTransaction();

            $validated = $request->validated();
            $originalData = $employee->toArray();

            // Handle NIK regeneration if NIP or department changed
            if (($employee->nip !== $validated['nip'] || $employee->department !== $validated['department'])
                && empty($validated['nik'])) {
                $validated['nik'] = Employee::generateNik(
                    $validated['nip'],
                    $validated['department']
                );
            }

            // Recalculate age if birth date changed
            if (!empty($validated['tanggal_lahir']) &&
                $employee->tanggal_lahir !== $validated['tanggal_lahir']) {
                $validated['usia'] = \Carbon\Carbon::parse($validated['tanggal_lahir'])->age;
            }

            // Update employee
            $employee->update($validated);

            DB::commit();

            Log::info('Employee Updated Successfully', [
                'employee_id' => $employee->id,
                'nip' => $employee->nip,
                'nama_lengkap' => $employee->nama_lengkap,
                'changes' => array_diff_assoc($validated, $originalData)
            ]);

            return redirect()->route('employees.index')
                ->with([
                    'success' => "Data karyawan {$employee->nama_lengkap} berhasil diperbarui!",
                    'notification' => [
                        'type' => 'success',
                        'title' => 'Data Diperbarui',
                        'message' => "Data karyawan {$employee->nama_lengkap} berhasil diperbarui."
                    ]
                ]);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Employee Update Error', [
                'employee_id' => $employee->id,
                'error' => $e->getMessage(),
                'data' => $request->all()
            ]);

            return redirect()->back()
                ->withInput()
                ->with([
                    'error' => 'Gagal memperbarui data karyawan. ' . $e->getMessage(),
                    'notification' => [
                        'type' => 'error',
                        'title' => 'Gagal Memperbarui',
                        'message' => 'Terjadi kesalahan saat memperbarui data karyawan.'
                    ]
                ]);
        }
    }

    /**
     * Get filter options for MPGA data
     */
    private function getFilterOptions(): array
    {
        return [
            'departments' => Employee::select('department')
                ->distinct()
                ->whereNotNull('department')
                ->orderBy('department')
                ->pluck('department'),

            'units' => Employee::select('unit_organisasi')
                ->distinct()
                ->whereNotNull('unit_organisasi')
                ->orderBy('unit_organisasi')
                ->pluck('unit_organisasi'),

            'statusPegawai' => Employee::STATUS_PEGAWAI_OPTIONS,

            'statusKerja' => Employee::select('status_kerja')
                ->distinct()
                ->whereNotNull('status_kerja')
                ->orderBy('status_kerja')
                ->pluck('status_kerja')
        ];
    }

    /**
     * Get MPGA-specific statistics
     */
    private function getMPGAStatistics(): array
    {
        $totalEmployees = Employee::where('status', 'active')->count();

        $departmentStats = Employee::select('department', DB::raw('COUNT(*) as total'))
            ->where('status', 'active')
            ->whereIn('department', Employee::MPGA_DEPARTMENTS)
            ->groupBy('department')
            ->orderBy('total', 'desc')
            ->get();

        $statusStats = Employee::select('status_pegawai', DB::raw('COUNT(*) as total'))
            ->where('status', 'active')
            ->groupBy('status_pegawai')
            ->get();

        return [
            'total_employees' => $totalEmployees,
            'department_breakdown' => $departmentStats,
            'status_breakdown' => $statusStats,
            'mpga_departments_count' => $departmentStats->count(),
            'largest_department' => $departmentStats->first()?->department ?? 'N/A',
            'largest_department_count' => $departmentStats->first()?->total ?? 0
        ];
    }

    /**
     * Show create form with MPGA options
     */
    public function create()
    {
        $filterOptions = $this->getFilterOptions();

        return Inertia::render('Employees/Create', [
            'departments' => Employee::MPGA_DEPARTMENTS,
            'statusPegawaiOptions' => Employee::STATUS_PEGAWAI_OPTIONS,
            'unitOptions' => $filterOptions['units'],
            'title' => 'Tambah Karyawan MPGA',
            'subtitle' => 'Tambah data karyawan baru ke sistem MPGA'
        ]);
    }

    /**
     * Show edit form with MPGA context
     */
    public function edit(Employee $employee)
    {
        $filterOptions = $this->getFilterOptions();

        return Inertia::render('Employees/Edit', [
            'employee' => $employee,
            'departments' => Employee::MPGA_DEPARTMENTS,
            'statusPegawaiOptions' => Employee::STATUS_PEGAWAI_OPTIONS,
            'unitOptions' => $filterOptions['units'],
            'title' => 'Edit Karyawan MPGA',
            'subtitle' => "Edit data karyawan {$employee->nama_lengkap}"
        ]);
    }

    /**
     * Soft delete employee (set status to inactive)
     */
    public function destroy(Employee $employee)
    {
        try {
            DB::beginTransaction();

            $employeeName = $employee->nama_lengkap;
            $employeeNip = $employee->nip;

            // Soft delete by setting status to inactive
            $employee->update([
                'status' => 'inactive',
                'status_kerja' => 'Non-Aktif'
            ]);

            DB::commit();

            Log::info('Employee Soft Deleted', [
                'employee_id' => $employee->id,
                'nip' => $employeeNip,
                'nama_lengkap' => $employeeName
            ]);

            return redirect()->route('employees.index')
                ->with([
                    'success' => "Karyawan {$employeeName} berhasil dinonaktifkan!",
                    'notification' => [
                        'type' => 'warning',
                        'title' => 'Karyawan Dinonaktifkan',
                        'message' => "Karyawan {$employeeName} (NIP: {$employeeNip}) berhasil dinonaktifkan."
                    ]
                ]);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Employee Delete Error', [
                'employee_id' => $employee->id,
                'error' => $e->getMessage()
            ]);

            return redirect()->route('employees.index')
                ->with([
                    'error' => 'Gagal menonaktifkan karyawan.',
                    'notification' => [
                        'type' => 'error',
                        'title' => 'Gagal Menonaktifkan',
                        'message' => 'Terjadi kesalahan saat menonaktifkan karyawan.'
                    ]
                ]);
        }
    }
}
