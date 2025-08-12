<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\TrainingRecord;
use App\Models\TrainingType;
use App\Services\CertificateGenerationService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class EmployeeController extends Controller
{
    protected $certificateService;

    public function __construct(CertificateGenerationService $certificateService)
    {
        $this->certificateService = $certificateService;
    }

    /**
     * Display a listing of employees with training statistics
     */
    public function index(Request $request)
    {
        $query = Employee::with(['trainingRecords.trainingType']);

        // Search functionality
        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('nik', 'like', "%{$search}%")
                  ->orWhere('nip', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('department', 'like', "%{$search}%")
                  ->orWhere('position', 'like', "%{$search}%");
            });
        }

        // Department filter
        if ($request->has('department') && $request->department !== 'all') {
            $query->where('department', $request->department);
        }

        // Status filter
        if ($request->has('status') && $request->status !== 'all') {
            $query->where('is_active', $request->status === 'active');
        }

        // Training compliance filter
        if ($request->has('compliance') && $request->compliance !== 'all') {
            $query->whereHas('trainingRecords', function($q) use ($request) {
                if ($request->compliance === 'compliant') {
                    $q->where('expiry_date', '>', Carbon::now());
                } elseif ($request->compliance === 'expired') {
                    $q->where('expiry_date', '<=', Carbon::now());
                } elseif ($request->compliance === 'expiring') {
                    $q->whereBetween('expiry_date', [Carbon::now(), Carbon::now()->addDays(30)]);
                }
            });
        }

        // Sorting
        $sortField = $request->get('sort', 'name');
        $sortDirection = $request->get('direction', 'asc');

        if (in_array($sortField, ['name', 'nik', 'nip', 'department', 'position', 'created_at'])) {
            $query->orderBy($sortField, $sortDirection);
        }

        // Pagination
        $perPage = $request->get('per_page', 20);
        $employees = $query->paginate($perPage);

        // Add training statistics to each employee
        $employees->getCollection()->transform(function ($employee) {
            $employee->training_stats = [
                'total_trainings' => $employee->trainingRecords->count(),
                'valid_trainings' => $employee->trainingRecords->filter(function($record) {
                    return $record->expiry_date > Carbon::now();
                })->count(),
                'expired_trainings' => $employee->trainingRecords->filter(function($record) {
                    return $record->expiry_date <= Carbon::now();
                })->count(),
                'expiring_soon' => $employee->trainingRecords->filter(function($record) {
                    return $record->expiry_date <= Carbon::now()->addDays(30) &&
                           $record->expiry_date > Carbon::now();
                })->count(),
            ];
            return $employee;
        });

        // Get filter options
        $departments = Employee::distinct('department')->pluck('department')->filter();

        // Statistics
        $stats = [
            'total_employees' => Employee::count(),
            'active_employees' => Employee::where('is_active', true)->count(),
            'total_trainings' => TrainingRecord::count(),
            'expiring_certificates' => TrainingRecord::where('expiry_date', '<=', Carbon::now()->addDays(30))
                                                   ->where('expiry_date', '>', Carbon::now())
                                                   ->count(),
        ];

        return Inertia::render('Employees/Index', [
            'employees' => $employees,
            'filters' => $request->only(['search', 'department', 'status', 'compliance', 'sort', 'direction']),
            'departments' => $departments,
            'stats' => $stats,
            'title' => 'Employee Management',
            'subtitle' => 'Kelola data karyawan dan pelatihan mereka'
        ]);
    }

    /**
     * Show the form for creating a new employee
     */
    public function create()
    {
        $departments = Employee::distinct('department')->pluck('department')->filter();

        return Inertia::render('Employees/Create', [
            'departments' => $departments,
            'title' => 'Add New Employee',
            'subtitle' => 'Tambah karyawan baru ke sistem'
        ]);
    }

    /**
     * Store a newly created employee
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'nik' => 'required|string|max:16|unique:employees,nik',
            'nip' => 'required|string|max:20|unique:employees,nip',
            'email' => 'required|email|unique:employees,email',
            'phone' => 'nullable|string|max:15',
            'department' => 'required|string|max:100',
            'position' => 'required|string|max:100',
            'hire_date' => 'required|date',
            'birth_date' => 'nullable|date',
            'address' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        try {
            DB::beginTransaction();

            $employee = Employee::create([
                'name' => $request->name,
                'nik' => $request->nik,
                'nip' => $request->nip,
                'email' => $request->email,
                'phone' => $request->phone,
                'department' => $request->department,
                'position' => $request->position,
                'hire_date' => $request->hire_date,
                'birth_date' => $request->birth_date,
                'address' => $request->address,
                'is_active' => $request->get('is_active', true),
            ]);

            DB::commit();

            return redirect()->route('employees.index')
                           ->with('success', 'Employee created successfully!');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Failed to create employee: ' . $e->getMessage()])->withInput();
        }
    }

    /**
     * Display the specified employee with training details
     */
    public function show(Employee $employee)
    {
        $employee->load([
            'trainingRecords' => function($query) {
                $query->with('trainingType')->orderBy('expiry_date', 'desc');
            }
        ]);

        // Calculate training statistics
        $trainingStats = [
            'total_trainings' => $employee->trainingRecords->count(),
            'valid_trainings' => $employee->trainingRecords->filter(function($record) {
                return $record->expiry_date > Carbon::now();
            })->count(),
            'expired_trainings' => $employee->trainingRecords->filter(function($record) {
                return $record->expiry_date <= Carbon::now();
            })->count(),
            'expiring_soon' => $employee->trainingRecords->filter(function($record) {
                return $record->expiry_date <= Carbon::now()->addDays(30) &&
                       $record->expiry_date > Carbon::now();
            })->count(),
        ];

        // Get available training types for adding new training
        $availableTrainingTypes = TrainingType::where('is_active', true)->get();

        // Group trainings by status
        $groupedTrainings = [
            'valid' => $employee->trainingRecords->filter(function($record) {
                return $record->expiry_date > Carbon::now();
            }),
            'expiring' => $employee->trainingRecords->filter(function($record) {
                return $record->expiry_date <= Carbon::now()->addDays(30) &&
                       $record->expiry_date > Carbon::now();
            }),
            'expired' => $employee->trainingRecords->filter(function($record) {
                return $record->expiry_date <= Carbon::now();
            }),
        ];

        return Inertia::render('Employees/Show', [
            'employee' => $employee,
            'trainingStats' => $trainingStats,
            'groupedTrainings' => $groupedTrainings,
            'availableTrainingTypes' => $availableTrainingTypes,
            'title' => 'Employee Details',
            'subtitle' => 'Detail karyawan dan riwayat pelatihan'
        ]);
    }

    /**
     * Show the form for editing the specified employee
     */
    public function edit(Employee $employee)
    {
        $departments = Employee::distinct('department')->pluck('department')->filter();

        return Inertia::render('Employees/Edit', [
            'employee' => $employee,
            'departments' => $departments,
            'title' => 'Edit Employee',
            'subtitle' => 'Edit data karyawan'
        ]);
    }

    /**
     * Update the specified employee
     */
    public function update(Request $request, Employee $employee)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'nik' => 'required|string|max:16|unique:employees,nik,' . $employee->id,
            'nip' => 'required|string|max:20|unique:employees,nip,' . $employee->id,
            'email' => 'required|email|unique:employees,email,' . $employee->id,
            'phone' => 'nullable|string|max:15',
            'department' => 'required|string|max:100',
            'position' => 'required|string|max:100',
            'hire_date' => 'required|date',
            'birth_date' => 'nullable|date',
            'address' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        try {
            DB::beginTransaction();

            $employee->update([
                'name' => $request->name,
                'nik' => $request->nik,
                'nip' => $request->nip,
                'email' => $request->email,
                'phone' => $request->phone,
                'department' => $request->department,
                'position' => $request->position,
                'hire_date' => $request->hire_date,
                'birth_date' => $request->birth_date,
                'address' => $request->address,
                'is_active' => $request->get('is_active', true),
            ]);

            DB::commit();

            return redirect()->route('employees.show', $employee)
                           ->with('success', 'Employee updated successfully!');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Failed to update employee: ' . $e->getMessage()])->withInput();
        }
    }

    /**
     * Remove the specified employee (soft delete)
     */
    public function destroy(Employee $employee)
    {
        try {
            DB::beginTransaction();

            // Check if employee has active trainings
            $activeTrainings = $employee->trainingRecords()
                                      ->where('expiry_date', '>', Carbon::now())
                                      ->count();

            if ($activeTrainings > 0) {
                return back()->withErrors(['error' => 'Cannot delete employee with active training records. Please expire or remove training records first.']);
            }

            // Soft delete employee
            $employee->update(['is_active' => false]);
            $employee->delete();

            DB::commit();

            return redirect()->route('employees.index')
                           ->with('success', 'Employee deleted successfully!');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Failed to delete employee: ' . $e->getMessage()]);
        }
    }

    /**
     * Add training to employee
     */
    public function addTraining(Request $request, Employee $employee)
    {
        $validator = Validator::make($request->all(), [
            'training_type_id' => 'required|exists:training_types,id',
            'issue_date' => 'required|date',
            'expiry_date' => 'required|date|after:issue_date',
            'training_provider' => 'nullable|string|max:255',
            'cost' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        try {
            DB::beginTransaction();

            $trainingType = TrainingType::findOrFail($request->training_type_id);

            $trainingRecord = TrainingRecord::create([
                'employee_id' => $employee->id,
                'training_type_id' => $request->training_type_id,
                'issue_date' => $request->issue_date,
                'expiry_date' => $request->expiry_date,
                'completion_status' => 'COMPLETED',
                'training_provider' => $request->training_provider,
                'cost' => $request->cost,
                'notes' => $request->notes,
            ]);

            // Generate certificate automatically
            $certificateResult = $this->certificateService->generateCertificate($trainingRecord);

            DB::commit();

            return back()->with('success', 'Training added successfully! Certificate generated: ' . $certificateResult['certificate_number']);

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Failed to add training: ' . $e->getMessage()]);
        }
    }

    /**
     * Remove training from employee
     */
    public function removeTraining(Employee $employee, TrainingRecord $trainingRecord)
    {
        try {
            if ($trainingRecord->employee_id !== $employee->id) {
                return back()->withErrors(['error' => 'Training record does not belong to this employee.']);
            }

            $trainingRecord->delete();

            return back()->with('success', 'Training record removed successfully!');

        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Failed to remove training: ' . $e->getMessage()]);
        }
    }

    /**
     * Export employee data
     */
    public function export(Request $request)
    {
        // Implementation for export functionality
        // This would integrate with Excel export service

        return response()->json(['message' => 'Export functionality to be implemented']);
    }

    /**
     * Bulk operations
     */
    public function bulkUpdate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'employee_ids' => 'required|array',
            'employee_ids.*' => 'exists:employees,id',
            'action' => 'required|in:activate,deactivate,update_department',
            'department' => 'required_if:action,update_department|string|max:100',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator);
        }

        try {
            DB::beginTransaction();

            $employees = Employee::whereIn('id', $request->employee_ids);
            $count = $employees->count();

            switch ($request->action) {
                case 'activate':
                    $employees->update(['is_active' => true]);
                    $message = "$count employees activated successfully!";
                    break;

                case 'deactivate':
                    $employees->update(['is_active' => false]);
                    $message = "$count employees deactivated successfully!";
                    break;

                case 'update_department':
                    $employees->update(['department' => $request->department]);
                    $message = "$count employees moved to {$request->department} department!";
                    break;
            }

            DB::commit();

            return back()->with('success', $message);

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Bulk operation failed: ' . $e->getMessage()]);
        }
    }
}
