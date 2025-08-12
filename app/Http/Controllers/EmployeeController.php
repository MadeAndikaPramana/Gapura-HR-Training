<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class EmployeeController extends Controller
{
    /**
     * Display a listing of employees
     */
    public function index(Request $request)
    {
        try {
            $search = $request->get('search');
            $perPage = $request->get('per_page', 20);

            $query = Employee::query();

            // Search functionality - hanya untuk nama, NIP, NIK
            if (!empty($search)) {
                $query->where(function($q) use ($search) {
                    $q->where('nama_lengkap', 'like', "%{$search}%")
                      ->orWhere('nip', 'like', "%{$search}%")
                      ->orWhere('nik', 'like', "%{$search}%");
                });
            }

            // Order by nama_lengkap
            $query->orderBy('nama_lengkap', 'asc');

            // Get paginated results - hanya ambil field yang diperlukan
            $employees = $query->select(['id', 'nama_lengkap', 'nip', 'nik'])
                              ->paginate($perPage);

            return Inertia::render('Employee', [
                'employees' => $employees,
                'pagination' => [
                    'current_page' => $employees->currentPage(),
                    'last_page' => $employees->lastPage(),
                    'per_page' => $employees->perPage(),
                    'total' => $employees->total(),
                    'from' => $employees->firstItem(),
                    'to' => $employees->lastItem(),
                    'prev_page_url' => $employees->previousPageUrl(),
                    'next_page_url' => $employees->nextPageUrl(),
                ],
                'success' => session('success'),
                'error' => session('error'),
            ]);

        } catch (\Exception $e) {
            Log::error('Employee Index Error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return Inertia::render('Employee', [
                'employees' => ['data' => []],
                'pagination' => [],
                'error' => 'Error loading employees: ' . $e->getMessage(),
            ]);
        }
    }

    /**
     * Store a newly created employee
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nama_lengkap' => 'required|string|max:255',
            'nip' => 'required|string|max:50|unique:employees,nip',
            'nik' => 'required|string|max:20|unique:employees,nik',
        ], [
            'nama_lengkap.required' => 'Nama lengkap wajib diisi',
            'nip.required' => 'NIP wajib diisi',
            'nip.unique' => 'NIP sudah digunakan',
            'nik.required' => 'NIK wajib diisi',
            'nik.unique' => 'NIK sudah digunakan',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        try {
            DB::beginTransaction();

            $employee = Employee::create([
                'nama_lengkap' => $request->nama_lengkap,
                'nip' => $request->nip,
                'nik' => $request->nik,
                // Set default values for other required fields
                'status_kerja' => 'Aktif',
                'unit_organisasi' => 'GAPURA ANGKASA',
                'jabatan' => 'Staff',
                'tanggal_masuk' => now(),
            ]);

            DB::commit();

            Log::info('Employee created successfully', [
                'employee_id' => $employee->id,
                'nama_lengkap' => $request->nama_lengkap,
                'nip' => $request->nip
            ]);

            return redirect()->route('employees.index')
                           ->with('success', 'Karyawan berhasil ditambahkan!');

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Employee Store Error', [
                'error' => $e->getMessage(),
                'request_data' => $request->only(['nama_lengkap', 'nip', 'nik'])
            ]);

            return back()->withErrors(['error' => 'Gagal menambahkan karyawan: ' . $e->getMessage()])->withInput();
        }
    }

    /**
     * Update the specified employee
     */
    public function update(Request $request, Employee $employee)
    {
        $validator = Validator::make($request->all(), [
            'nama_lengkap' => 'required|string|max:255',
            'nip' => 'required|string|max:50|unique:employees,nip,' . $employee->id,
            'nik' => 'required|string|max:20|unique:employees,nik,' . $employee->id,
        ], [
            'nama_lengkap.required' => 'Nama lengkap wajib diisi',
            'nip.required' => 'NIP wajib diisi',
            'nip.unique' => 'NIP sudah digunakan',
            'nik.required' => 'NIK wajib diisi',
            'nik.unique' => 'NIK sudah digunakan',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        try {
            DB::beginTransaction();

            $employee->update([
                'nama_lengkap' => $request->nama_lengkap,
                'nip' => $request->nip,
                'nik' => $request->nik,
            ]);

            DB::commit();

            Log::info('Employee updated successfully', [
                'employee_id' => $employee->id,
                'nama_lengkap' => $request->nama_lengkap,
                'nip' => $request->nip
            ]);

            return redirect()->route('employees.index')
                           ->with('success', 'Data karyawan berhasil diperbarui!');

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Employee Update Error', [
                'employee_id' => $employee->id,
                'error' => $e->getMessage()
            ]);

            return back()->withErrors(['error' => 'Gagal memperbarui data karyawan: ' . $e->getMessage()])->withInput();
        }
    }

    /**
     * Remove the specified employee
     */
    public function destroy(Employee $employee)
    {
        try {
            // Check if employee has training records
            if ($employee->trainingRecords()->exists()) {
                return back()->withErrors(['error' => 'Tidak dapat menghapus karyawan yang memiliki data training.']);
            }

            $employeeName = $employee->nama_lengkap;
            $employee->delete();

            Log::info('Employee deleted successfully', [
                'employee_id' => $employee->id,
                'nama_lengkap' => $employeeName
            ]);

            return redirect()->route('employees.index')
                           ->with('success', "Karyawan {$employeeName} berhasil dihapus!");

        } catch (\Exception $e) {
            Log::error('Employee Delete Error', [
                'employee_id' => $employee->id,
                'error' => $e->getMessage()
            ]);

            return back()->withErrors(['error' => 'Gagal menghapus karyawan: ' . $e->getMessage()]);
        }
    }

    /**
     * Show employee details (if needed)
     */
    public function show(Employee $employee)
    {
        try {
            return Inertia::render('Employee/Show', [
                'employee' => $employee->only(['id', 'nama_lengkap', 'nip', 'nik']),
                'title' => 'Detail Karyawan',
                'subtitle' => 'Informasi karyawan ' . $employee->nama_lengkap
            ]);

        } catch (\Exception $e) {
            Log::error('Employee Show Error', [
                'employee_id' => $employee->id,
                'error' => $e->getMessage()
            ]);

            return redirect()->route('employees.index')
                           ->with('error', 'Error loading employee details.');
        }
    }

    /**
     * Get employee stats for dashboard
     */
    public function getStats()
    {
        try {
            $total = Employee::count();
            $active = Employee::where('status_kerja', 'Aktif')->count();
            $withTraining = Employee::has('trainingRecords')->count();

            return response()->json([
                'total' => $total,
                'active' => $active,
                'with_training' => $withTraining,
                'without_training' => $total - $withTraining,
            ]);

        } catch (\Exception $e) {
            Log::error('Employee Stats Error', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'total' => 0,
                'active' => 0,
                'with_training' => 0,
                'without_training' => 0,
            ], 500);
        }
    }

    /**
     * Export employees (simple CSV)
     */
    public function export()
    {
        try {
            $employees = Employee::select(['nama_lengkap', 'nip', 'nik'])
                               ->orderBy('nama_lengkap')
                               ->get();

            $csvContent = "Nama Lengkap,NIP,NIK\n";

            foreach ($employees as $employee) {
                $csvContent .= sprintf(
                    '"%s","%s","%s"' . "\n",
                    $employee->nama_lengkap ?: '',
                    $employee->nip ?: '',
                    $employee->nik ?: ''
                );
            }

            $fileName = 'employees_' . date('Y-m-d_H-i-s') . '.csv';

            return response($csvContent, 200, [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
            ]);

        } catch (\Exception $e) {
            Log::error('Employee Export Error', [
                'error' => $e->getMessage()
            ]);

            return back()->withErrors(['error' => 'Gagal export data: ' . $e->getMessage()]);
        }
    }
}
