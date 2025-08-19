<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Carbon\Carbon;

class EmployeeController extends Controller
{
    /**
     * Display a listing of employees
     */
    public function index(Request $request)
    {
        // Simple permission check - redirect if not admin
        if (!auth()->check() || !auth()->user()->isAdmin()) {
            abort(403, 'Unauthorized access - Diperlukan akses Administrator.');
        }

        $query = Employee::query();

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('nama_lengkap', 'like', "%{$search}%")
                  ->orWhere('nip', 'like', "%{$search}%")
                  ->orWhere('nik', 'like', "%{$search}%")
                  ->orWhere('unit_organisasi', 'like', "%{$search}%")
                  ->orWhere('jabatan', 'like', "%{$search}%");
            });
        }

        // Filter by department
        if ($request->filled('department')) {
            $query->where('unit_organisasi', $request->get('department'));
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status_kerja', $request->get('status'));
        }

        $employees = $query->orderBy('nama_lengkap')
                          ->paginate(15)
                          ->withQueryString();

        // Get filter options
        $departments = Employee::select('unit_organisasi')
                              ->distinct()
                              ->whereNotNull('unit_organisasi')
                              ->orderBy('unit_organisasi')
                              ->pluck('unit_organisasi');

        return Inertia::render('Employees/Index', [
            'employees' => $employees,
            'departments' => $departments,
            'filters' => $request->only(['search', 'department', 'status']),
            'statistics' => $this->getEmployeeStatistics(),
        ]);
    }

    /**
     * Show single employee
     */
    public function show(Employee $employee)
    {
        // Simple permission check
        if (!auth()->check() || !auth()->user()->isAdmin()) {
            abort(403, 'Unauthorized access');
        }

        return Inertia::render('Employees/Show', [
            'employee' => $employee,
        ]);
    }

    /**
     * Show create employee form
     */
    public function create()
    {
        // Simple permission check
        if (!auth()->check() || !auth()->user()->isAdmin()) {
            abort(403, 'Unauthorized access');
        }

        $departments = Employee::select('unit_organisasi')
                              ->distinct()
                              ->whereNotNull('unit_organisasi')
                              ->orderBy('unit_organisasi')
                              ->pluck('unit_organisasi');

        $jobTitles = Employee::select('jabatan')
                            ->distinct()
                            ->whereNotNull('jabatan')
                            ->orderBy('jabatan')
                            ->pluck('jabatan');

        return Inertia::render('Employees/Create', [
            'departments' => $departments,
            'jobTitles' => $jobTitles,
        ]);
    }

    /**
     * Store new employee
     */
    public function store(Request $request)
    {
        // Simple permission check
        if (!auth()->check() || !auth()->user()->isAdmin()) {
            abort(403, 'Unauthorized access');
        }

        try {
            $validated = $request->validate([
                'nip' => 'required|string|max:20|unique:employees',
                'nik' => 'nullable|string|max:20|unique:employees',
                'nama_lengkap' => 'required|string|max:255',
                'jenis_kelamin' => 'required|in:L,P',
                'tempat_lahir' => 'nullable|string|max:255',
                'tanggal_lahir' => 'nullable|date',
                'unit_organisasi' => 'required|string|max:255',
                'jabatan' => 'required|string|max:255',
                'status_pegawai' => 'required|in:PEGAWAI TETAP,PKWT,TAD PAKET SDM,TAD PAKET PEKERJAAN',
                'status_kerja' => 'required|string|max:50',
                'lokasi_kerja' => 'nullable|string|max:255',
                'cabang' => 'nullable|string|max:50',
                'provider' => 'nullable|string|max:255',
                'handphone' => 'nullable|string|max:20',
                'email' => 'nullable|email|max:255',
                'alamat' => 'nullable|string|max:1000',
            ]);

            // Generate NIK if not provided
            if (empty($validated['nik'])) {
                $validated['nik'] = $this->generateNIK();
            }

            // Calculate age if birth date provided
            if (!empty($validated['tanggal_lahir'])) {
                $validated['usia'] = Carbon::parse($validated['tanggal_lahir'])->age;
            }

            // Set default active status
            $validated['is_active'] = true;

            DB::beginTransaction();

            $employee = Employee::create($validated);

            DB::commit();

            Log::info('Employee Created Successfully', [
                'employee_id' => $employee->id,
                'nip' => $employee->nip,
                'nama_lengkap' => $employee->nama_lengkap,
                'created_by' => auth()->user()->name,
                'created_by_role' => auth()->user()->role,
            ]);

            return redirect()->route('employees.index')
                          ->with('success', "Karyawan {$employee->nama_lengkap} berhasil ditambahkan!");

        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            return redirect()->back()
                          ->withErrors($e->errors())
                          ->withInput()
                          ->with('error', 'Mohon periksa kembali data yang dimasukkan.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Employee Create Error', [
                'error' => $e->getMessage(),
                'input' => $request->all(),
                'user' => auth()->user()->name ?? 'Unknown',
                'user_role' => auth()->user()->role ?? 'Unknown',
            ]);

            return redirect()->back()
                          ->withInput()
                          ->with('error', 'Terjadi kesalahan sistem. Silakan coba lagi.');
        }
    }

    /**
     * Show edit employee form
     */
    public function edit(Employee $employee)
    {
        // Simple permission check
        if (!auth()->check() || !auth()->user()->isAdmin()) {
            abort(403, 'Unauthorized access');
        }

        $departments = Employee::select('unit_organisasi')
                              ->distinct()
                              ->whereNotNull('unit_organisasi')
                              ->orderBy('unit_organisasi')
                              ->pluck('unit_organisasi');

        $jobTitles = Employee::select('jabatan')
                            ->distinct()
                            ->whereNotNull('jabatan')
                            ->orderBy('jabatan')
                            ->pluck('jabatan');

        return Inertia::render('Employees/Edit', [
            'employee' => $employee,
            'departments' => $departments,
            'jobTitles' => $jobTitles,
        ]);
    }

    /**
     * Update employee
     */
    public function update(Request $request, Employee $employee)
    {
        // Simple permission check
        if (!auth()->check() || !auth()->user()->isAdmin()) {
            abort(403, 'Unauthorized access');
        }

        try {
            $validated = $request->validate([
                'nip' => 'required|string|max:20|unique:employees,nip,' . $employee->id,
                'nik' => 'nullable|string|max:20|unique:employees,nik,' . $employee->id,
                'nama_lengkap' => 'required|string|max:255',
                'jenis_kelamin' => 'required|in:L,P',
                'tempat_lahir' => 'nullable|string|max:255',
                'tanggal_lahir' => 'nullable|date',
                'unit_organisasi' => 'required|string|max:255',
                'jabatan' => 'required|string|max:255',
                'status_pegawai' => 'required|in:PEGAWAI TETAP,PKWT,TAD PAKET SDM,TAD PAKET PEKERJAAN',
                'status_kerja' => 'required|string|max:50',
                'lokasi_kerja' => 'nullable|string|max:255',
                'cabang' => 'nullable|string|max:50',
                'provider' => 'nullable|string|max:255',
                'handphone' => 'nullable|string|max:20',
                'email' => 'nullable|email|max:255',
                'alamat' => 'nullable|string|max:1000',
            ]);

            // Recalculate age if birth date changed
            if (!empty($validated['tanggal_lahir'])) {
                $validated['usia'] = Carbon::parse($validated['tanggal_lahir'])->age;
            }

            DB::beginTransaction();

            $employee->update($validated);

            DB::commit();

            Log::info('Employee Updated Successfully', [
                'employee_id' => $employee->id,
                'nip' => $employee->nip,
                'nama_lengkap' => $employee->nama_lengkap,
                'changes' => $employee->getChanges(),
                'updated_by' => auth()->user()->name,
                'updated_by_role' => auth()->user()->role,
            ]);

            return redirect()->route('employees.index')
                          ->with('success', "Data karyawan {$employee->nama_lengkap} berhasil diperbarui!");

        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            return redirect()->back()
                          ->withErrors($e->errors())
                          ->withInput()
                          ->with('error', 'Mohon periksa kembali data yang dimasukkan.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Employee Update Error', [
                'employee_id' => $employee->id,
                'error' => $e->getMessage(),
                'input' => $request->all(),
                'user' => auth()->user()->name ?? 'Unknown',
                'user_role' => auth()->user()->role ?? 'Unknown',
            ]);

            return redirect()->back()
                          ->withInput()
                          ->with('error', 'Terjadi kesalahan sistem. Silakan coba lagi.');
        }
    }

    /**
     * Delete employee (Soft Delete)
     * FIXED: Simple permission check without middleware
     */
    public function destroy(Employee $employee)
    {
        // Simple permission check
        if (!auth()->check() || !auth()->user()->isAdmin()) {
            return redirect()->route('employees.index')
                          ->with('error', 'Anda tidak memiliki izin untuk menghapus karyawan.');
        }

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
                'nama_lengkap' => $employeeName,
                'deleted_by' => auth()->user()->name,
                'deleted_by_role' => auth()->user()->role,
            ]);

            return redirect()->route('employees.index')
                          ->with('success', "Karyawan {$employeeName} berhasil dihapus!");

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Employee Delete Error', [
                'employee_id' => $employee->id,
                'error' => $e->getMessage(),
                'user' => auth()->user()->name ?? 'Unknown',
                'user_role' => auth()->user()->role ?? 'Unknown',
            ]);

            return redirect()->route('employees.index')
                          ->with('error', 'Terjadi kesalahan saat menghapus karyawan. Silakan coba lagi.');
        }
    }

    /**
     * Export employees
     */
    public function export()
    {
        // Simple permission check
        if (!auth()->check() || !auth()->user()->isAdmin()) {
            abort(403, 'Unauthorized access');
        }

        // Implementation for export
        return response()->download('employees.xlsx');
    }

    /**
     * Get employee statistics for dashboard
     */
    public function getStatistics()
    {
        return response()->json($this->getEmployeeStatistics());
    }

    /**
     * Get employee statistics (private helper)
     */
    private function getEmployeeStatistics()
    {
        $total = Employee::where('is_active', true)->count();
        $active = Employee::where('status_kerja', 'Aktif')
                         ->where('is_active', true)
                         ->count();

        // Placeholder untuk future training statistics
        $compliant = 0;
        $needsTraining = 0;
        $expiringSoon = 0;

        return [
            'total' => $total,
            'active' => $active,
            'compliant' => $compliant,
            'needsTraining' => $needsTraining,
            'expiringSoon' => $expiringSoon,
            'complianceRate' => $total > 0 ? round(($compliant / $total) * 100, 1) : 0,
        ];
    }

    /**
     * Generate NIK helper
     */
    private function generateNIK()
    {
        do {
            $nik = '35' . str_pad(rand(1, 99999999999999), 14, '0', STR_PAD_LEFT);
        } while (Employee::where('nik', $nik)->exists());

        return $nik;
    }
}
