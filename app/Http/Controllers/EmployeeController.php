<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Employee;
use App\Models\TrainingType;
use App\Models\TrainingRecord;
use App\Models\BackgroundCheck;
use Illuminate\Support\Facades\Redirect;

class EmployeeController extends Controller
{
    /**
     * Display employees list
     */
    public function index(Request $request)
    {
        $query = Employee::query();

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->where(function($q) use ($search) {
                $q->where('nama_lengkap', 'like', "%{$search}%")
                  ->orWhere('nip', 'like', "%{$search}%")
                  ->orWhere('unit_organisasi', 'like', "%{$search}%")
                  ->orWhere('jabatan', 'like', "%{$search}%");
            });
        }

        // Department filter
        if ($request->filled('department')) {
            $query->where('unit_organisasi', $request->get('department'));
        }

        // Status filter
        if ($request->filled('status')) {
            $query->where('status_kerja', $request->get('status'));
        }

        // Compliance filter
        if ($request->filled('compliance')) {
            $compliance = $request->get('compliance');
            if ($compliance === 'compliant') {
                $query->compliantEmployees();
            } elseif ($compliance === 'non_compliant') {
                $query->needsTraining();
            } elseif ($compliance === 'expiring_soon') {
                $query->trainingExpiringSoon();
            }
        }

        $employees = $query->withTrainingCompliance()
                          ->paginate(20)
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
            'filters' => $request->only(['search', 'department', 'status', 'compliance']),
            'statistics' => $this->getEmployeeStatistics(),
        ]);
    }

    /**
     * Show employee details
     */
    public function show(Employee $employee)
    {
        $employee->load([
            'trainingRecords.trainingType',
            'backgroundChecks' => function($query) {
                $query->latest('check_date');
            }
        ]);

        // Get training matrix
        $trainingMatrix = $employee->getTrainingMatrix();

        // Get training gaps
        $trainingGaps = $employee->getTrainingGaps();

        return Inertia::render('Employees/Show', [
            'employee' => $employee,
            'trainingMatrix' => $trainingMatrix,
            'trainingGaps' => $trainingGaps,
            'complianceStatus' => [
                'training' => $employee->training_compliance_status,
                'background_check' => $employee->background_check_status,
                'percentage' => $employee->training_compliance_percentage,
            ],
        ]);
    }

    /**
     * Show create employee form
     */
    public function create()
    {
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
        $validated = $request->validate([
            'nip' => 'required|string|max:20|unique:employees',
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
            'alamat' => 'nullable|string',
        ]);

        Employee::create($validated);

        return Redirect::route('employees.index')
                      ->with('success', 'Employee created successfully.');
    }

    /**
     * Show edit employee form
     */
    public function edit(Employee $employee)
    {
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
        $validated = $request->validate([
            'nip' => 'required|string|max:20|unique:employees,nip,' . $employee->id,
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
            'alamat' => 'nullable|string',
        ]);

        $employee->update($validated);

        return Redirect::route('employees.show', $employee)
                      ->with('success', 'Employee updated successfully.');
    }

    /**
     * Delete employee
     */
    public function destroy(Employee $employee)
    {
        // Check if employee has training records
        if ($employee->trainingRecords()->count() > 0) {
            return Redirect::back()
                          ->with('error', 'Cannot delete employee that has training records.');
        }

        $employee->delete();

        return Redirect::route('employees.index')
                      ->with('success', 'Employee deleted successfully.');
    }

    /**
     * Get employee statistics
     */
    private function getEmployeeStatistics()
    {
        $total = Employee::count();
        $active = Employee::where('status_kerja', 'Aktif')->count();
        $compliant = Employee::compliantEmployees()->count();
        $needsTraining = Employee::needsTraining()->count();
        $expiringSoon = Employee::trainingExpiringSoon()->count();

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
     * Export employees data
     */
    public function export(Request $request)
    {
        // This would implement Excel/CSV export functionality
        // For now, return JSON for API consumption

        $query = Employee::withTrainingCompliance();

        // Apply same filters as index
        if ($request->filled('department')) {
            $query->where('unit_organisasi', $request->get('department'));
        }

        if ($request->filled('status')) {
            $query->where('status_kerja', $request->get('status'));
        }

        $employees = $query->get()->map(function($employee) {
            return [
                'nip' => $employee->nip,
                'nama_lengkap' => $employee->nama_lengkap,
                'unit_organisasi' => $employee->unit_organisasi,
                'jabatan' => $employee->jabatan,
                'status_pegawai' => $employee->status_pegawai,
                'status_kerja' => $employee->status_kerja,
                'training_compliance' => $employee->training_compliance_status,
                'compliance_percentage' => $employee->training_compliance_percentage,
                'background_check_status' => $employee->background_check_status,
                'active_trainings' => $employee->activeTrainingRecords()->count(),
                'expired_trainings' => $employee->expiredTrainingRecords()->count(),
            ];
        });

        return response()->json([
            'data' => $employees,
            'total' => $employees->count(),
            'exported_at' => now(),
        ]);
    }

    /**
     * Get employee training compliance report
     */
    public function complianceReport(Employee $employee)
    {
        $trainingTypes = TrainingType::active()->ordered()->get();
        $report = [];

        foreach ($trainingTypes as $trainingType) {
            $record = $employee->getTrainingRecord($trainingType->code);

            $report[] = [
                'training_type' => [
                    'id' => $trainingType->id,
                    'name' => $trainingType->name,
                    'code' => $trainingType->code,
                    'duration_months' => $trainingType->duration_months,
                ],
                'record' => $record ? [
                    'id' => $record->id,
                    'certificate_number' => $record->certificate_number,
                    'valid_from' => $record->valid_from,
                    'valid_until' => $record->valid_until,
                    'status' => $record->status,
                    'days_until_expiry' => $record->days_until_expiry,
                ] : null,
                'status' => $record ? $record->status_text : 'Not Completed',
                'is_compliant' => $record && $record->status === 'active',
            ];
        }

        return response()->json([
            'employee' => [
                'id' => $employee->id,
                'nip' => $employee->nip,
                'nama_lengkap' => $employee->nama_lengkap,
                'unit_organisasi' => $employee->unit_organisasi,
            ],
            'compliance_report' => $report,
            'overall_compliance' => [
                'percentage' => $employee->training_compliance_percentage,
                'status' => $employee->training_compliance_status,
                'background_check' => $employee->background_check_status,
            ],
            'generated_at' => now(),
        ]);
    }
}
