<?php

namespace App\Http\Controllers;

use App\Models\TrainingType;
use App\Models\TrainingRecord;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class TrainingTypeController extends Controller
{
    /**
     * Display a listing of training types
     */
    public function index(Request $request)
    {
        try {
            $search = $request->get('search');
            $category = $request->get('category', 'all');
            $status = $request->get('status', 'all');
            $perPage = $request->get('per_page', 20);

            $query = TrainingType::query();

            // Apply search filter
            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%")
                      ->orWhere('category', 'like', "%{$search}%");
                });
            }

            // Apply category filter
            if ($category && $category !== 'all') {
                $query->where('category', $category);
            }

            // Apply status filter
            if ($status && $status !== 'all') {
                if ($status === 'active') {
                    $query->where('is_active', true);
                } else {
                    $query->where('is_active', false);
                }
            }

            // Get training types with statistics
            $trainingTypes = $query->withCount([
                'trainingRecords as total_records',
                'trainingRecords as valid_records' => function($q) {
                    $q->where('expiry_date', '>', now());
                },
                'trainingRecords as expired_records' => function($q) {
                    $q->where('expiry_date', '<=', now());
                }
            ])->orderBy('category')->orderBy('name')->paginate($perPage);

            // Calculate statistics
            $statistics = [
                'total_types' => TrainingType::count(),
                'active_types' => TrainingType::where('is_active', true)->count(),
                'mandatory_types' => TrainingType::where('is_mandatory', true)->count(),
                'categories' => TrainingType::distinct('category')->count(),
            ];

            // Get filter options
            $filterOptions = [
                'categories' => TrainingType::distinct('category')
                                          ->whereNotNull('category')
                                          ->orderBy('category')
                                          ->pluck('category'),
            ];

            return Inertia::render('TrainingTypes/Index', [
                'trainingTypes' => $trainingTypes,
                'filters' => [
                    'search' => $search,
                    'category' => $category,
                    'status' => $status,
                ],
                'filterOptions' => $filterOptions,
                'statistics' => $statistics,
                'success' => session('success'),
                'error' => session('error'),
                'title' => 'Training Types Management',
                'subtitle' => 'Manage training categories and requirements'
            ]);

        } catch (\Exception $e) {
            Log::error('TrainingType Index Error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return Inertia::render('TrainingTypes/Index', [
                'trainingTypes' => ['data' => []],
                'filters' => [],
                'filterOptions' => [],
                'statistics' => [],
                'error' => 'Error loading training types: ' . $e->getMessage(),
            ]);
        }
    }

    /**
     * Show the form for creating a new training type
     */
    public function create()
    {
        return Inertia::render('TrainingTypes/Create', [
            'categories' => TrainingType::CATEGORIES,
            'complianceLevels' => TrainingType::COMPLIANCE_LEVELS,
            'success' => session('success'),
            'error' => session('error'),
        ]);
    }

    /**
     * Store a newly created training type
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255|unique:training_types,name',
                'category' => 'required|string|max:100',
                'description' => 'nullable|string',
                'validity_period' => 'required|integer|min:1|max:120',
                'is_mandatory' => 'boolean',
                'compliance_level' => 'required|in:CRITICAL,HIGH,MEDIUM,LOW,OPTIONAL',
                'training_provider_default' => 'nullable|string|max:255',
                'cost_estimate' => 'nullable|numeric|min:0',
                'requirements' => 'nullable|array',
                'notification_days' => 'nullable|integer|min:1|max:365',
            ]);

            TrainingType::create([
                ...$validated,
                'is_active' => true,
                'renewal_required' => true,
                'created_by' => auth()->user()->name,
            ]);

            return redirect()->route('training-types.index')
                ->with('success', 'Training type created successfully!');

        } catch (ValidationException $e) {
            return redirect()->back()
                ->withErrors($e->errors())
                ->withInput();
        } catch (\Exception $e) {
            Log::error('TrainingType Store Error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()->back()
                ->with('error', 'Error creating training type: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Display the specified training type
     */
    public function show(TrainingType $trainingType)
    {
        try {
            $trainingType->loadCount([
                'trainingRecords as total_records',
                'trainingRecords as valid_records' => function($q) {
                    $q->where('expiry_date', '>', now());
                },
                'trainingRecords as expired_records' => function($q) {
                    $q->where('expiry_date', '<=', now());
                }
            ]);

            // Get recent training records
            $recentRecords = $trainingType->trainingRecords()
                                        ->with('employee:id,nip,nama_lengkap')
                                        ->latest()
                                        ->limit(10)
                                        ->get();

            // Get expiring certificates
            $expiringRecords = $trainingType->trainingRecords()
                                          ->with('employee:id,nip,nama_lengkap')
                                          ->where('expiry_date', '>', now())
                                          ->where('expiry_date', '<=', now()->addDays(60))
                                          ->orderBy('expiry_date')
                                          ->get();

            return Inertia::render('TrainingTypes/Show', [
                'trainingType' => $trainingType,
                'recentRecords' => $recentRecords,
                'expiringRecords' => $expiringRecords,
                'success' => session('success'),
                'error' => session('error'),
            ]);

        } catch (\Exception $e) {
            Log::error('TrainingType Show Error', [
                'training_type_id' => $trainingType->id,
                'error' => $e->getMessage()
            ]);

            return redirect()->route('training-types.index')
                ->with('error', 'Error loading training type details.');
        }
    }

    /**
     * Show the form for editing the specified training type
     */
    public function edit(TrainingType $trainingType)
    {
        return Inertia::render('TrainingTypes/Edit', [
            'trainingType' => $trainingType,
            'categories' => TrainingType::CATEGORIES,
            'complianceLevels' => TrainingType::COMPLIANCE_LEVELS,
            'success' => session('success'),
            'error' => session('error'),
        ]);
    }

    /**
     * Update the specified training type
     */
    public function update(Request $request, TrainingType $trainingType)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255|unique:training_types,name,' . $trainingType->id,
                'category' => 'required|string|max:100',
                'description' => 'nullable|string',
                'validity_period' => 'required|integer|min:1|max:120',
                'is_mandatory' => 'boolean',
                'is_active' => 'boolean',
                'compliance_level' => 'required|in:CRITICAL,HIGH,MEDIUM,LOW,OPTIONAL',
                'training_provider_default' => 'nullable|string|max:255',
                'cost_estimate' => 'nullable|numeric|min:0',
                'requirements' => 'nullable|array',
                'notification_days' => 'nullable|integer|min:1|max:365',
            ]);

            $trainingType->update([
                ...$validated,
                'updated_by' => auth()->user()->name,
            ]);

            return redirect()->route('training-types.index')
                ->with('success', 'Training type updated successfully!');

        } catch (ValidationException $e) {
            return redirect()->back()
                ->withErrors($e->errors())
                ->withInput();
        } catch (\Exception $e) {
            Log::error('TrainingType Update Error', [
                'training_type_id' => $trainingType->id,
                'error' => $e->getMessage()
            ]);

            return redirect()->back()
                ->with('error', 'Error updating training type: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Remove the specified training type
     */
    public function destroy(TrainingType $trainingType)
    {
        try {
            // Check if training type has associated records
            $recordCount = $trainingType->trainingRecords()->count();

            if ($recordCount > 0) {
                return redirect()->route('training-types.index')
                    ->with('error', "Cannot delete training type. It has {$recordCount} associated training records.");
            }

            $trainingType->delete();

            return redirect()->route('training-types.index')
                ->with('success', 'Training type deleted successfully!');

        } catch (\Exception $e) {
            Log::error('TrainingType Delete Error', [
                'training_type_id' => $trainingType->id,
                'error' => $e->getMessage()
            ]);

            return redirect()->route('training-types.index')
                ->with('error', 'Error deleting training type: ' . $e->getMessage());
        }
    }

    /**
     * Bulk update training types
     */
    public function bulkUpdate(Request $request)
    {
        try {
            $validated = $request->validate([
                'ids' => 'required|array',
                'ids.*' => 'exists:training_types,id',
                'action' => 'required|in:activate,deactivate,delete',
            ]);

            $count = 0;

            switch ($validated['action']) {
                case 'activate':
                    $count = TrainingType::whereIn('id', $validated['ids'])
                                       ->update(['is_active' => true]);
                    break;

                case 'deactivate':
                    $count = TrainingType::whereIn('id', $validated['ids'])
                                       ->update(['is_active' => false]);
                    break;

                case 'delete':
                    // Check for associated records
                    $typesWithRecords = TrainingType::whereIn('id', $validated['ids'])
                                                  ->whereHas('trainingRecords')
                                                  ->count();

                    if ($typesWithRecords > 0) {
                        return redirect()->back()
                            ->with('error', 'Cannot delete training types that have associated records.');
                    }

                    $count = TrainingType::whereIn('id', $validated['ids'])->delete();
                    break;
            }

            return redirect()->route('training-types.index')
                ->with('success', "Successfully {$validated['action']}d {$count} training type(s).");

        } catch (ValidationException $e) {
            return redirect()->back()->withErrors($e->errors());
        } catch (\Exception $e) {
            Log::error('TrainingType Bulk Update Error', [
                'error' => $e->getMessage(),
                'action' => $request->action,
                'ids' => $request->ids
            ]);

            return redirect()->back()
                ->with('error', 'Error performing bulk operation: ' . $e->getMessage());
        }
    }

    /**
     * Toggle training type status
     */
    public function toggleStatus(TrainingType $trainingType)
    {
        try {
            $trainingType->update([
                'is_active' => !$trainingType->is_active,
                'updated_by' => auth()->user()->name,
            ]);

            $status = $trainingType->is_active ? 'activated' : 'deactivated';

            return redirect()->back()
                ->with('success', "Training type {$status} successfully!");

        } catch (\Exception $e) {
            Log::error('TrainingType Toggle Status Error', [
                'training_type_id' => $trainingType->id,
                'error' => $e->getMessage()
            ]);

            return redirect()->back()
                ->with('error', 'Error toggling training type status.');
        }
    }

    /**
     * Generate compliance report for training type
     */
    public function complianceReport(TrainingType $trainingType)
    {
        try {
            // Get all employees with this training type
            $records = $trainingType->trainingRecords()
                                  ->with('employee:id,nip,nama_lengkap,unit_organisasi')
                                  ->get()
                                  ->groupBy('employee.unit_organisasi');

            $report = [];
            foreach ($records as $department => $departmentRecords) {
                $valid = $departmentRecords->where('expiry_date', '>', now())->count();
                $expired = $departmentRecords->where('expiry_date', '<=', now())->count();
                $total = $departmentRecords->count();

                $report[] = [
                    'department' => $department ?: 'Unknown',
                    'total' => $total,
                    'valid' => $valid,
                    'expired' => $expired,
                    'compliance_rate' => $total > 0 ? round(($valid / $total) * 100, 2) : 0,
                ];
            }

            return response()->json($report);

        } catch (\Exception $e) {
            Log::error('TrainingType Compliance Report Error', [
                'training_type_id' => $trainingType->id,
                'error' => $e->getMessage()
            ]);

            return response()->json(['error' => 'Error generating compliance report'], 500);
        }
    }
}
