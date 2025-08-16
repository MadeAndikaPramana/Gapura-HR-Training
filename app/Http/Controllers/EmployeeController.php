<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Carbon\Carbon;

class EmployeeController extends Controller
{
    /**
     * PHASE 1: EMPLOYEE CRUD CONTROLLER - FIXED VERSION
     * Konsisten dengan UI Firman HR Gapura
     * Menggunakan Laravel standard ID sebagai primary key
     * Support untuk MPGA Excel structure
     */

    /**
     * Display paginated list of employees with enhanced search and filtering
     */
    public function index(Request $request)
    {
        try {
            // Build query with proper relationships
            $query = Employee::query()->where('is_active', true);

            // Enhanced search functionality
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('nama_lengkap', 'like', "%{$search}%")
                      ->orWhere('nip', 'like', "%{$search}%")
                      ->orWhere('nik', 'like', "%{$search}%")
                      ->orWhere('jabatan', 'like', "%{$search}%")
                      ->orWhere('unit_organisasi', 'like', "%{$search}%");
                });
            }

            // Department filter (dari MPGA sheets)
            if ($request->filled('department')) {
                $query->where('department', $request->department);
            }

            // Unit organisasi filter
            if ($request->filled('unit_organisasi')) {
                $query->where('unit_organisasi', $request->unit_organisasi);
            }

            // Status filter
            if ($request->filled('status_pegawai')) {
                $query->where('status_pegawai', $request->status_pegawai);
            }

            // Sort by name (default)
            $query->orderBy('nama_lengkap', 'asc');

            // Paginate results
            $employees = $query->paginate(15)->withQueryString();

            // Generate statistics for dashboard cards
            $statistics = $this->getEmployeeStatistics();

            // Get filter options
            $filterOptions = $this->getFilterOptions();

            return Inertia::render('Employees/Index', [
                'employees' => $employees,
                'statistics' => $statistics,
                'filters' => $request->only(['search', 'department', 'unit_organisasi', 'status_pegawai']),
                'filterOptions' => $filterOptions,
                'title' => 'Data Karyawan',
                'subtitle' => 'Kelola data kepegawaian sistem training GAPURA',
            ]);

        } catch (\Exception $e) {
            Log::error('Employee Index Error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return Inertia::render('Employees/Index', [
                'employees' => ['data' => [], 'total' => 0],
                'statistics' => $this->getEmptyStatistics(),
                'filters' => [],
                'filterOptions' => $this->getFilterOptions(),
                'error' => 'Terjadi kesalahan saat memuat data karyawan.'
            ]);
        }
    }

    /**
     * Show the form for creating a new employee
     */
    public function create()
    {
        return Inertia::render('Employees/Create', [
            'departments' => $this->getDepartmentOptions(),
            'units' => $this->getUnitOptions(),
            'statusOptions' => $this->getStatusOptions(),
            'title' => 'Tambah Karyawan Baru',
        ]);
    }

    /**
     * Store a newly created employee in storage
     */
    public function store(Request $request)
    {
        try {
            // Comprehensive validation rules
            $validated = $request->validate([
                // Core MPGA fields
                'nip' => 'required|string|max:20|unique:employees,nip',
                'nama_lengkap' => 'required|string|max:255',
                'department' => 'required|string|max:50',
                'unit_organisasi' => 'required|string|max:255',

                // Personal information
                'jenis_kelamin' => 'required|in:L,P',
                'tempat_lahir' => 'nullable|string|max:255',
                'tanggal_lahir' => 'nullable|date|before:today',

                // Work information
                'jabatan' => 'nullable|string|max:255',
                'status_pegawai' => 'required|in:PEGAWAI TETAP,PKWT,TAD PAKET SDM,TAD PAKET PEKERJAAN',
                'lokasi_kerja' => 'nullable|string|max:255',
                'cabang' => 'nullable|string|max:100',
                'provider' => 'nullable|string|max:255',

                // Contact information
                'handphone' => 'nullable|string|max:20|regex:/^[0-9+\-\s()]+$/',
                'email' => 'nullable|email|max:255|unique:employees,email',
                'alamat' => 'nullable|string|max:500',
            ]);

            // Auto-generate NIK if not provided
            if (empty($validated['nik'])) {
                $validated['nik'] = $this->generateNIK();
            }

            // Calculate age if birth date provided
            if (!empty($validated['tanggal_lahir'])) {
                $validated['usia'] = Carbon::parse($validated['tanggal_lahir'])->age;
            }

            // Set system defaults
            $validated['is_active'] = true;
            $validated['status_kerja'] = 'Aktif';

            // Create employee record
            DB::beginTransaction();

            $employee = Employee::create($validated);

            DB::commit();

            Log::info('Employee Created Successfully', [
                'employee_id' => $employee->id,
                'nip' => $employee->nip,
                'nama_lengkap' => $employee->nama_lengkap,
                'department' => $employee->department
            ]);

            return Redirect::route('employees.index')
                          ->with('success', "Karyawan {$employee->nama_lengkap} berhasil ditambahkan!");

        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            return Redirect::back()
                          ->withErrors($e->validator)
                          ->withInput()
                          ->with('error', 'Mohon periksa kembali data yang dimasukkan.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Employee Store Error', [
                'error' => $e->getMessage(),
                'input' => $request->all()
            ]);

            return Redirect::back()
                          ->withInput()
                          ->with('error', 'Terjadi kesalahan sistem. Silakan coba lagi.');
        }
    }

    /**
     * Display the specified employee
     */
    public function show(Employee $employee)
    {
        try {
            // Load related training records (untuk Phase 2+)
            $employee->load([
                // 'trainingRecords.trainingType', // Uncomment when Phase 2 ready
                // 'backgroundChecks'
            ]);

            return Inertia::render('Employees/Show', [
                'employee' => $employee,
                'title' => "Detail Karyawan - {$employee->nama_lengkap}",
            ]);

        } catch (\Exception $e) {
            Log::error('Employee Show Error', [
                'employee_id' => $employee->id,
                'error' => $e->getMessage()
            ]);

            return Redirect::route('employees.index')
                          ->with('error', 'Karyawan tidak ditemukan.');
        }
    }

    /**
     * Show the form for editing the specified employee
     */
    public function edit(Employee $employee)
    {
        try {
            return Inertia::render('Employees/Edit', [
                'employee' => $employee,
                'departments' => $this->getDepartmentOptions(),
                'units' => $this->getUnitOptions(),
                'statusOptions' => $this->getStatusOptions(),
                'title' => "Edit Karyawan - {$employee->nama_lengkap}",
            ]);

        } catch (\Exception $e) {
            Log::error('Employee Edit Error', [
                'employee_id' => $employee->id,
                'error' => $e->getMessage()
            ]);

            return Redirect::route('employees.index')
                          ->with('error', 'Karyawan tidak ditemukan.');
        }
    }

    /**
     * Update the specified employee in storage
     */
    public function update(Request $request, Employee $employee)
    {
        try {
            // Validation rules for update (exclude current employee from unique checks)
            $validated = $request->validate([
                // Core MPGA fields
                'nip' => 'required|string|max:20|unique:employees,nip,' . $employee->id,
                'nama_lengkap' => 'required|string|max:255',
                'department' => 'required|string|max:50',
                'unit_organisasi' => 'required|string|max:255',

                // Personal information
                'jenis_kelamin' => 'required|in:L,P',
                'tempat_lahir' => 'nullable|string|max:255',
                'tanggal_lahir' => 'nullable|date|before:today',

                // Work information
                'jabatan' => 'nullable|string|max:255',
                'status_pegawai' => 'required|in:PEGAWAI TETAP,PKWT,TAD PAKET SDM,TAD PAKET PEKERJAAN',
                'lokasi_kerja' => 'nullable|string|max:255',
                'cabang' => 'nullable|string|max:100',
                'provider' => 'nullable|string|max:255',

                // Contact information
                'handphone' => 'nullable|string|max:20|regex:/^[0-9+\-\s()]+$/',
                'email' => 'nullable|email|max:255|unique:employees,email,' . $employee->id,
                'alamat' => 'nullable|string|max:500',
            ]);

            // Recalculate age if birth date changed
            if (!empty($validated['tanggal_lahir'])) {
                $validated['usia'] = Carbon::parse($validated['tanggal_lahir'])->age;
            }

            // Update employee record
            DB::beginTransaction();

            $employee->update($validated);

            DB::commit();

            Log::info('Employee Updated Successfully', [
                'employee_id' => $employee->id,
                'nip' => $employee->nip,
                'nama_lengkap' => $employee->nama_lengkap,
                'changes' => $employee->getChanges()
            ]);

            return Redirect::route('employees.index')
                          ->with('success', "Data karyawan {$employee->nama_lengkap} berhasil diperbarui!");

        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            return Redirect::back()
                          ->withErrors($e->validator)
                          ->withInput()
                          ->with('error', 'Mohon periksa kembali data yang dimasukkan.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Employee Update Error', [
                'employee_id' => $employee->id,
                'error' => $e->getMessage(),
                'input' => $request->all()
            ]);

            return Redirect::back()
                          ->withInput()
                          ->with('error', 'Terjadi kesalahan sistem. Silakan coba lagi.');
        }
    }

    /**
     * Remove the specified employee from storage (soft delete)
     */
    public function destroy(Employee $employee)
    {
        try {
            $employeeName = $employee->nama_lengkap;
            $employeeNip = $employee->nip;

            DB::beginTransaction();

            // Soft delete by setting is_active to false
            $employee->update([
                'is_active' => false,
                'status_kerja' => 'Tidak Aktif'
            ]);

            DB::commit();

            Log::info('Employee Soft Deleted Successfully', [
                'employee_id' => $employee->id,
                'nip' => $employeeNip,
                'nama_lengkap' => $employeeName
            ]);

            return Redirect::route('employees.index')
                          ->with('success', "Karyawan {$employeeName} berhasil dihapus!");

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Employee Delete Error', [
                'employee_id' => $employee->id,
                'error' => $e->getMessage()
            ]);

            return Redirect::route('employees.index')
                          ->with('error', 'Terjadi kesalahan saat menghapus karyawan.');
        }
    }

    // =====================================================
    // HELPER METHODS
    // =====================================================

    /**
     * Get employee statistics for dashboard cards
     */
    private function getEmployeeStatistics()
    {
        try {
            return [
                'total' => Employee::where('is_active', true)->count(),
                'departments' => Employee::where('is_active', true)
                                       ->distinct('department')
                                       ->count('department'),
                'this_month' => Employee::where('is_active', true)
                                       ->whereMonth('created_at', now()->month)
                                       ->whereYear('created_at', now()->year)
                                       ->count(),
                'units' => Employee::where('is_active', true)
                                  ->distinct('unit_organisasi')
                                  ->count('unit_organisasi'),
                'by_department' => Employee::where('is_active', true)
                                          ->select('department', DB::raw('count(*) as total'))
                                          ->groupBy('department')
                                          ->orderBy('total', 'desc')
                                          ->get(),
                'by_status' => Employee::where('is_active', true)
                                      ->select('status_pegawai', DB::raw('count(*) as total'))
                                      ->groupBy('status_pegawai')
                                      ->get(),
            ];
        } catch (\Exception $e) {
            return $this->getEmptyStatistics();
        }
    }

    /**
     * Get empty statistics (fallback)
     */
    private function getEmptyStatistics()
    {
        return [
            'total' => 0,
            'departments' => 0,
            'this_month' => 0,
            'units' => 0,
            'by_department' => [],
            'by_status' => [],
        ];
    }

    /**
     * Get filter options for dropdowns
     */
    private function getFilterOptions()
    {
        try {
            return [
                'departments' => $this->getDepartmentOptions(),
                'units' => Employee::where('is_active', true)
                                  ->select('unit_organisasi')
                                  ->distinct()
                                  ->whereNotNull('unit_organisasi')
                                  ->orderBy('unit_organisasi')
                                  ->pluck('unit_organisasi'),
                'status_options' => $this->getStatusOptions(),
            ];
        } catch (\Exception $e) {
            return [
                'departments' => [],
                'units' => [],
                'status_options' => [],
            ];
        }
    }

    /**
     * Get department options based on MPGA structure
     */
    private function getDepartmentOptions()
    {
        return [
            'DEDICATED',
            'LOADING',
            'RAMP',
            'LOCO',
            'ULD',
            'LOST & FOUND',
            'CARGO',
            'ARRIVAL',
            'GSE OPERATOR',
            'FLOP',
            'AVSEC',
            'PORTER'
        ];
    }

    /**
     * Get unit organization options from database
     */
    private function getUnitOptions()
    {
        return Employee::select('unit_organisasi')
                      ->distinct()
                      ->whereNotNull('unit_organisasi')
                      ->where('unit_organisasi', '!=', '')
                      ->orderBy('unit_organisasi')
                      ->pluck('unit_organisasi');
    }

    /**
     * Get employee status options
     */
    private function getStatusOptions()
    {
        return [
            'PEGAWAI TETAP',
            'PKWT',
            'TAD PAKET SDM',
            'TAD PAKET PEKERJAAN'
        ];
    }

    /**
     * Generate unique NIK
     */
    private function generateNIK()
    {
        do {
            // Format: YYMM + 6 random digits
            $nik = date('ym') . str_pad(mt_rand(1, 999999), 6, '0', STR_PAD_LEFT);
        } while (Employee::where('nik', $nik)->exists());

        return $nik;
    }

    // =====================================================
    // ADDITIONAL METHODS FOR FUTURE PHASES
    // =====================================================

    /**
     * Export employees to Excel (Phase 5)
     */
    public function export(Request $request)
    {
        // TODO: Implement Excel export
        return response()->json(['message' => 'Export functionality coming in Phase 5']);
    }

    /**
     * Import employees from Excel (Phase 5)
     */
    public function import(Request $request)
    {
        // TODO: Implement Excel import
        return response()->json(['message' => 'Import functionality coming in Phase 5']);
    }

    /**
     * Get statistics API endpoint for AJAX calls
     */
    public function getStatistics()
    {
        return response()->json($this->getEmployeeStatistics());
    }

    /**
     * Public profile verification (for certificate verification)
     */
    public function publicProfile($nip)
    {
        $employee = Employee::where('nip', $nip)
                           ->where('is_active', true)
                           ->first();

        if (!$employee) {
            abort(404, 'Karyawan tidak ditemukan');
        }

        return Inertia::render('Public/EmployeeProfile', [
            'employee' => $employee->only(['nip', 'nama_lengkap', 'department', 'unit_organisasi'])
        ]);
    }
}
