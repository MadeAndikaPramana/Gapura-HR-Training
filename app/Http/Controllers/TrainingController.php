<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\TrainingType;
use App\Models\TrainingRecord;
use App\Models\Employee;
use Illuminate\Support\Facades\Redirect;

class TrainingController extends Controller
{
    /**
     * Display training types management
     */
    public function index()
    {
        $trainingTypes = TrainingType::withCount([
                'trainingRecords',
                'trainingRecords as active_count' => function($query) {
                    $query->where('status', 'active');
                },
                'trainingRecords as expired_count' => function($query) {
                    $query->where('status', 'expired');
                },
                'trainingRecords as expiring_soon_count' => function($query) {
                    $query->where('status', 'expiring_soon');
                }
            ])
            ->ordered()
            ->get();

        return Inertia::render('Training/Index', [
            'trainingTypes' => $trainingTypes,
            'statistics' => $this->getTrainingStatistics(),
        ]);
    }

    /**
     * Show training type details
     */
    public function show(TrainingType $trainingType)
    {
        $trainingType->load([
            'trainingRecords.employee',
            'trainingRecords' => function($query) {
                $query->latest('valid_until');
            }
        ]);

        $records = $trainingType->trainingRecords()->with('employee')
                                                  ->latest('valid_until')
                                                  ->paginate(20);

        return Inertia::render('Training/Show', [
            'trainingType' => $trainingType,
            'records' => $records,
            'statistics' => [
                'total' => $trainingType->training_records_count ?? 0,
                'active' => $trainingType->active_count ?? 0,
                'expired' => $trainingType->expired_count ?? 0,
                'expiring_soon' => $trainingType->expiring_soon_count ?? 0,
            ],
        ]);
    }

    /**
     * Show create training type form
     */
    public function create()
    {
        return Inertia::render('Training/Create');
    }

    /**
     * Store new training type
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:100|unique:training_types',
            'description' => 'nullable|string',
            'duration_months' => 'required|integer|min:1|max:120',
            'certificate_format' => 'nullable|string|max:255',
            'requires_background_check' => 'boolean',
            'is_active' => 'boolean',
            'sort_order' => 'nullable|integer',
        ]);

        // Set default sort order if not provided
        if (!isset($validated['sort_order'])) {
            $validated['sort_order'] = TrainingType::max('sort_order') + 1;
        }

        TrainingType::create($validated);

        return Redirect::route('training.index')
                      ->with('success', 'Training type created successfully.');
    }

    /**
     * Show edit training type form
     */
    public function edit(TrainingType $trainingType)
    {
        return Inertia::render('Training/Edit', [
            'trainingType' => $trainingType,
        ]);
    }

    /**
     * Update training type
     */
    public function update(Request $request, TrainingType $trainingType)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:100|unique:training_types,code,' . $trainingType->id,
            'description' => 'nullable|string',
            'duration_months' => 'required|integer|min:1|max:120',
            'certificate_format' => 'nullable|string|max:255',
            'requires_background_check' => 'boolean',
            'is_active' => 'boolean',
            'sort_order' => 'nullable|integer',
        ]);

        $trainingType->update($validated);

        return Redirect::route('training.index')
                      ->with('success', 'Training type updated successfully.');
    }

    /**
     * Delete training type
     */
    public function destroy(TrainingType $trainingType)
    {
        // Check if training type has records
        if ($trainingType->trainingRecords()->count() > 0) {
            return Redirect::back()
                          ->with('error', 'Cannot delete training type that has existing records.');
        }

        $trainingType->delete();

        return Redirect::route('training.index')
                      ->with('success', 'Training type deleted successfully.');
    }

    /**
     * Get training statistics
     */
    private function getTrainingStatistics()
    {
        $totalTypes = TrainingType::count();
        $activeTypes = TrainingType::active()->count();
        $totalRecords = TrainingRecord::count();
        $activeRecords = TrainingRecord::where('status', 'active')->count();
        $expiredRecords = TrainingRecord::where('status', 'expired')->count();
        $expiringSoonRecords = TrainingRecord::where('status', 'expiring_soon')->count();

        return [
            'totalTypes' => $totalTypes,
            'activeTypes' => $activeTypes,
            'totalRecords' => $totalRecords,
            'activeRecords' => $activeRecords,
            'expiredRecords' => $expiredRecords,
            'expiringSoonRecords' => $expiringSoonRecords,
        ];
    }

    /**
     * Update training type order
     */
    public function updateOrder(Request $request)
    {
        $validated = $request->validate([
            'items' => 'required|array',
            'items.*.id' => 'required|exists:training_types,id',
            'items.*.sort_order' => 'required|integer|min:0',
        ]);

        foreach ($validated['items'] as $item) {
            TrainingType::where('id', $item['id'])
                        ->update(['sort_order' => $item['sort_order']]);
        }

        return response()->json(['message' => 'Training type order updated successfully.']);
    }

    /**
     * Toggle training type status
     */
    public function toggleStatus(TrainingType $trainingType)
    {
        $trainingType->update([
            'is_active' => !$trainingType->is_active
        ]);

        $status = $trainingType->is_active ? 'activated' : 'deactivated';

        return Redirect::back()
                      ->with('success', "Training type {$status} successfully.");
    }

    /**
     * Get training compliance report
     */
    public function complianceReport()
    {
        $departments = Employee::select('unit_organisasi')
                              ->distinct()
                              ->whereNotNull('unit_organisasi')
                              ->pluck('unit_organisasi');

        $trainingTypes = TrainingType::active()->get();
        $report = [];

        foreach ($departments as $department) {
            $employees = Employee::where('unit_organisasi', $department)->get();
            $departmentData = [
                'department' => $department,
                'totalEmployees' => $employees->count(),
                'trainingCompliance' => [],
                'overallCompliance' => 0,
            ];

            foreach ($trainingTypes as $trainingType) {
                $compliantEmployees = $employees->filter(function($employee) use ($trainingType) {
                    return $employee->hasValidTraining($trainingType->code);
                })->count();

                $complianceRate = $employees->count() > 0
                    ? round(($compliantEmployees / $employees->count()) * 100, 1)
                    : 0;

                $departmentData['trainingCompliance'][] = [
                    'trainingType' => $trainingType->name,
                    'compliantEmployees' => $compliantEmployees,
                    'totalEmployees' => $employees->count(),
                    'complianceRate' => $complianceRate,
                ];
            }

            // Calculate overall compliance
            $totalRequired = $employees->count() * $trainingTypes->count();
            $totalCompliant = 0;

            foreach ($employees as $employee) {
                $totalCompliant += $employee->activeTrainingRecords()->count();
            }

            $departmentData['overallCompliance'] = $totalRequired > 0
                ? round(($totalCompliant / $totalRequired) * 100, 1)
                : 0;

            $report[] = $departmentData;
        }

        return response()->json($report);
    }
}
