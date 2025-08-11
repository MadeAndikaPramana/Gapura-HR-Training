<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\TrainingRecord;
use App\Models\TrainingType;
use App\Models\Employee;
use Illuminate\Support\Facades\Redirect;
use Carbon\Carbon;

class TrainingRecordController extends Controller
{
    /**
     * Display training records list
     */
    public function index(Request $request)
    {
        $query = TrainingRecord::with(['employee', 'trainingType']);

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->where(function($q) use ($search) {
                $q->where('certificate_number', 'like', "%{$search}%")
                  ->orWhereHas('employee', function($empQ) use ($search) {
                      $empQ->where('nama_lengkap', 'like', "%{$search}%")
                           ->orWhere('nip', 'like', "%{$search}%");
                  })
                  ->orWhereHas('trainingType', function($typeQ) use ($search) {
                      $typeQ->where('name', 'like', "%{$search}%");
                  });
            });
        }

        // Department filter
        if ($request->filled('department')) {
            $query->whereHas('employee', function($q) use ($request) {
                $q->where('unit_organisasi', $request->get('department'));
            });
        }

        // Training type filter
        if ($request->filled('training_type')) {
            $query->where('training_type_id', $request->get('training_type'));
        }

        // Status filter
        if ($request->filled('status')) {
            $query->where('status', $request->get('status'));
        }

        // Date range filter
        if ($request->filled('date_from')) {
            $query->where('valid_from', '>=', $request->get('date_from'));
        }
        if ($request->filled('date_to')) {
            $query->where('valid_until', '<=', $request->get('date_to'));
        }

        // Expiry filter
        if ($request->filled('expiry')) {
            $expiry = $request->get('expiry');
            if ($expiry === 'expired') {
                $query->expired();
            } elseif ($expiry === 'expiring_soon') {
                $query->expiringSoon(30);
            } elseif ($expiry === 'active') {
                $query->where('status', 'active');
            }
        }

        $records = $query->latest('valid_until')->paginate(20)->withQueryString();

        // Get filter options
        $departments = Employee::select('unit_organisasi')
                              ->distinct()
                              ->whereNotNull('unit_organisasi')
                              ->orderBy('unit_organisasi')
                              ->pluck('unit_organisasi');

        $trainingTypes = TrainingType::active()->ordered()->get(['id', 'name']);

        return Inertia::render('TrainingRecords/Index', [
            'records' => $records,
            'departments' => $departments,
            'trainingTypes' => $trainingTypes,
            'filters' => $request->only([
                'search', 'department', 'training_type', 'status',
                'date_from', 'date_to', 'expiry'
            ]),
            'statistics' => $this->getRecordStatistics(),
        ]);
    }

    /**
     * Show training record details
     */
    public function show(TrainingRecord $trainingRecord)
    {
        $trainingRecord->load(['employee', 'trainingType']);

        // Get related records for the same employee
        $relatedRecords = TrainingRecord::where('employee_nip', $trainingRecord->employee_nip)
                                       ->where('id', '!=', $trainingRecord->id)
                                       ->with('trainingType')
                                       ->latest('valid_until')
                                       ->get();

        return Inertia::render('TrainingRecords/Show', [
            'record' => $trainingRecord,
            'relatedRecords' => $relatedRecords,
            'complianceInfo' => $this->getComplianceInfo($trainingRecord),
        ]);
    }

    /**
     * Show create training record form
     */
    public function create(Request $request)
    {
        $employees = Employee::select(['id', 'nip', 'nama_lengkap', 'unit_organisasi'])
                            ->orderBy('nama_lengkap')
                            ->get();

        $trainingTypes = TrainingType::active()->ordered()->get();

        // Pre-fill employee if provided
        $selectedEmployee = null;
        if ($request->filled('employee_nip')) {
            $selectedEmployee = Employee::where('nip', $request->get('employee_nip'))->first();
        }

        return Inertia::render('TrainingRecords/Create', [
            'employees' => $employees,
            'trainingTypes' => $trainingTypes,
            'selectedEmployee' => $selectedEmployee,
        ]);
    }

    /**
     * Store new training record
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'employee_nip' => 'required|exists:employees,nip',
            'training_type_id' => 'required|exists:training_types,id',
            'certificate_number' => 'required|string|max:255|unique:training_records',
            'issued_date' => 'nullable|date',
            'valid_from' => 'required|date',
            'valid_until' => 'required|date|after:valid_from',
            'issuing_authority' => 'nullable|string|max:255',
            'training_location' => 'nullable|string|max:255',
            'instructor' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        // Set issued_date to valid_from if not provided
        if (!isset($validated['issued_date'])) {
            $validated['issued_date'] = $validated['valid_from'];
        }

        // Auto-determine status based on dates
        $validUntil = Carbon::parse($validated['valid_until']);
        if ($validUntil->isPast()) {
            $validated['status'] = 'expired';
        } elseif ($validUntil->diffInDays(now()) <= 30) {
            $validated['status'] = 'expiring_soon';
        } else {
            $validated['status'] = 'active';
        }

        $record = TrainingRecord::create($validated);

        return Redirect::route('training-records.show', $record)
                      ->with('success', 'Training record created successfully.');
    }

    /**
     * Show edit training record form
     */
    public function edit(TrainingRecord $trainingRecord)
    {
        $trainingRecord->load(['employee', 'trainingType']);

        $employees = Employee::select(['id', 'nip', 'nama_lengkap', 'unit_organisasi'])
                            ->orderBy('nama_lengkap')
                            ->get();

        $trainingTypes = TrainingType::active()->ordered()->get();

        return Inertia::render('TrainingRecords/Edit', [
            'record' => $trainingRecord,
            'employees' => $employees,
            'trainingTypes' => $trainingTypes,
        ]);
    }

    /**
     * Update training record
     */
    public function update(Request $request, TrainingRecord $trainingRecord)
    {
        $validated = $request->validate([
            'employee_nip' => 'required|exists:employees,nip',
            'training_type_id' => 'required|exists:training_types,id',
            'certificate_number' => 'required|string|max:255|unique:training_records,certificate_number,' . $trainingRecord->id,
            'issued_date' => 'nullable|date',
            'valid_from' => 'required|date',
            'valid_until' => 'required|date|after:valid_from',
            'issuing_authority' => 'nullable|string|max:255',
            'training_location' => 'nullable|string|max:255',
            'instructor' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        // Auto-update status based on new dates
        $validUntil = Carbon::parse($validated['valid_until']);
        if ($validUntil->isPast()) {
            $validated['status'] = 'expired';
        } elseif ($validUntil->diffInDays(now()) <= 30) {
            $validated['status'] = 'expiring_soon';
        } else {
            $validated['status'] = 'active';
        }

        $trainingRecord->update($validated);

        return Redirect::route('training-records.show', $trainingRecord)
                      ->with('success', 'Training record updated successfully.');
    }

    /**
     * Delete training record
     */
    public function destroy(TrainingRecord $trainingRecord)
    {
        $trainingRecord->delete();

        return Redirect::route('training-records.index')
                      ->with('success', 'Training record deleted successfully.');
    }

    /**
     * Renew training certificate
     */
    public function renew(Request $request, TrainingRecord $trainingRecord)
    {
        $validated = $request->validate([
            'certificate_number' => 'required|string|max:255|unique:training_records',
            'valid_from' => 'required|date',
            'valid_until' => 'required|date|after:valid_from',
            'issuing_authority' => 'nullable|string|max:255',
            'training_location' => 'nullable|string|max:255',
            'instructor' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        // Create new record for renewal
        $newRecord = $trainingRecord->replicate();
        $newRecord->fill($validated);
        $newRecord->issued_date = $validated['valid_from'];
        $newRecord->status = 'active';
        $newRecord->notes = ($validated['notes'] ?? '') . " [Renewed from certificate: {$trainingRecord->certificate_number}]";
        $newRecord->save();

        // Mark old record as superseded
        $trainingRecord->update([
            'status' => 'superseded',
            'notes' => ($trainingRecord->notes ?? '') . " [Superseded by certificate: {$newRecord->certificate_number}]"
        ]);

        return Redirect::route('training-records.show', $newRecord)
                      ->with('success', 'Training certificate renewed successfully.');
    }

    /**
     * Bulk operations
     */
    public function bulkAction(Request $request)
    {
        $validated = $request->validate([
            'action' => 'required|in:delete,update_status,extend_validity',
            'record_ids' => 'required|array|min:1',
            'record_ids.*' => 'exists:training_records,id',
            'new_status' => 'nullable|in:active,expired,suspended',
            'extend_months' => 'nullable|integer|min:1|max:60',
        ]);

        $records = TrainingRecord::whereIn('id', $validated['record_ids']);
        $count = $records->count();

        switch ($validated['action']) {
            case 'delete':
                $records->delete();
                $message = "{$count} training records deleted successfully.";
                break;

            case 'update_status':
                $records->update(['status' => $validated['new_status']]);
                $message = "{$count} training records status updated successfully.";
                break;

            case 'extend_validity':
                $records->each(function($record) use ($validated) {
                    if ($record->valid_until) {
                        $newValidUntil = Carbon::parse($record->valid_until)->addMonths($validated['extend_months']);
                        $record->update([
                            'valid_until' => $newValidUntil,
                            'status' => 'active',
                        ]);
                    }
                });
                $message = "{$count} training records validity extended successfully.";
                break;
        }

        return Redirect::back()->with('success', $message);
    }

    /**
     * Get record statistics
     */
    private function getRecordStatistics()
    {
        $total = TrainingRecord::count();
        $active = TrainingRecord::where('status', 'active')->count();
        $expired = TrainingRecord::where('status', 'expired')->count();
        $expiringSoon = TrainingRecord::where('status', 'expiring_soon')->count();
        $suspended = TrainingRecord::where('status', 'suspended')->count();

        return [
            'total' => $total,
            'active' => $active,
            'expired' => $expired,
            'expiring_soon' => $expiringSoon,
            'suspended' => $suspended,
            'compliance_rate' => $total > 0 ? round(($active / $total) * 100, 1) : 0,
        ];
    }

    /**
     * Get compliance information for a record
     */
    private function getComplianceInfo(TrainingRecord $record)
    {
        $employee = $record->employee;
        $trainingType = $record->trainingType;

        if (!$employee || !$trainingType) {
            return null;
        }

        // Check if employee has other required trainings
        $allTrainingTypes = TrainingType::active()->get();
        $employeeTrainings = $employee->activeTrainingRecords()->with('trainingType')->get();

        $missingTrainings = $allTrainingTypes->filter(function($type) use ($employeeTrainings) {
            return !$employeeTrainings->contains('training_type_id', $type->id);
        });

        return [
            'employee_compliance_rate' => $employee->training_compliance_percentage,
            'employee_compliance_status' => $employee->training_compliance_status,
            'missing_trainings' => $missingTrainings->values(),
            'total_employee_trainings' => $employeeTrainings->count(),
            'required_trainings' => $allTrainingTypes->count(),
        ];
    }

    /**
     * Export training records
     */
    public function export(Request $request)
    {
        $query = TrainingRecord::with(['employee', 'trainingType']);

        // Apply same filters as index
        if ($request->filled('department')) {
            $query->whereHas('employee', function($q) use ($request) {
                $q->where('unit_organisasi', $request->get('department'));
            });
        }

        if ($request->filled('training_type')) {
            $query->where('training_type_id', $request->get('training_type'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->get('status'));
        }

        $records = $query->get()->map(function($record) {
            return [
                'certificate_number' => $record->certificate_number,
                'employee_nip' => $record->employee_nip,
                'employee_name' => $record->employee->nama_lengkap ?? 'Unknown',
                'department' => $record->employee->unit_organisasi ?? 'Unknown',
                'training_type' => $record->trainingType->name ?? 'Unknown',
                'issued_date' => $record->issued_date?->format('Y-m-d'),
                'valid_from' => $record->valid_from?->format('Y-m-d'),
                'valid_until' => $record->valid_until?->format('Y-m-d'),
                'status' => $record->status,
                'days_until_expiry' => $record->days_until_expiry,
                'issuing_authority' => $record->issuing_authority,
                'training_location' => $record->training_location,
                'instructor' => $record->instructor,
            ];
        });

        return response()->json([
            'data' => $records,
            'total' => $records->count(),
            'exported_at' => now(),
            'filters' => $request->only(['department', 'training_type', 'status']),
        ]);
    }

    /**
     * Get expiring certificates
     */
    public function expiring(Request $request)
    {
        $days = $request->get('days', 30);

        $records = TrainingRecord::with(['employee', 'trainingType'])
                                 ->expiringSoon($days)
                                 ->orderBy('valid_until')
                                 ->get();

        return response()->json([
            'records' => $records,
            'count' => $records->count(),
            'days' => $days,
        ]);
    }
}
