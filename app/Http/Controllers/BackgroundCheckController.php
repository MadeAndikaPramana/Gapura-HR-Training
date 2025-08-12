<?php

namespace App\Http\Controllers;

use App\Models\BackgroundCheck;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Carbon\Carbon;

class BackgroundCheckController extends Controller
{
    /**
     * Display a listing of background checks
     */
    public function index(Request $request)
    {
        try {
            $search = $request->get('search');
            $status = $request->get('status', 'all');
            $department = $request->get('department', 'all');
            $checkType = $request->get('check_type', 'all');
            $perPage = $request->get('per_page', 20);

            $query = BackgroundCheck::with(['employee:id,nip,nama_lengkap,unit_organisasi,jabatan']);

            // Apply search filter
            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('check_reference', 'like', "%{$search}%")
                      ->orWhere('issuing_authority', 'like', "%{$search}%")
                      ->orWhere('notes', 'like', "%{$search}%")
                      ->orWhereHas('employee', function ($eq) use ($search) {
                          $eq->where('nama_lengkap', 'like', "%{$search}%")
                             ->orWhere('nip', 'like', "%{$search}%");
                      });
                });
            }

            // Apply status filter
            if ($status && $status !== 'all') {
                $query->where('status', $status);
            }

            // Apply department filter
            if ($department && $department !== 'all') {
                $query->whereHas('employee', function ($eq) use ($department) {
                    $eq->where('unit_organisasi', $department);
                });
            }

            // Apply check type filter
            if ($checkType && $checkType !== 'all') {
                $query->where('check_type', $checkType);
            }

            $backgroundChecks = $query->orderBy('checked_at', 'desc')->paginate($perPage);

            // Calculate statistics
            $statistics = [
                'total_checks' => BackgroundCheck::count(),
                'passed' => BackgroundCheck::where('status', 'passed')->count(),
                'pending' => BackgroundCheck::where('status', 'pending')->count(),
                'failed' => BackgroundCheck::where('status', 'failed')->count(),
                'expired' => BackgroundCheck::where('valid_until', '<', Carbon::today())->count(),
            ];

            // Get filter options
            $filterOptions = [
                'departments' => Employee::distinct('unit_organisasi')
                                       ->whereNotNull('unit_organisasi')
                                       ->where('unit_organisasi', '!=', '')
                                       ->orderBy('unit_organisasi')
                                       ->pluck('unit_organisasi'),
                'checkTypes' => BackgroundCheck::distinct('check_type')
                                             ->whereNotNull('check_type')
                                             ->orderBy('check_type')
                                             ->pluck('check_type'),
            ];

            return Inertia::render('BackgroundChecks/Index', [
                'backgroundChecks' => $backgroundChecks,
                'filters' => [
                    'search' => $search,
                    'status' => $status,
                    'department' => $department,
                    'check_type' => $checkType,
                ],
                'filterOptions' => $filterOptions,
                'statistics' => $statistics,
                'success' => session('success'),
                'error' => session('error'),
                'title' => 'Background Checks Management',
                'subtitle' => 'Manage employee security clearances and background verifications'
            ]);

        } catch (\Exception $e) {
            Log::error('BackgroundCheck Index Error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return Inertia::render('BackgroundChecks/Index', [
                'backgroundChecks' => ['data' => []],
                'filters' => [],
                'filterOptions' => [],
                'statistics' => [],
                'error' => 'Error loading background checks: ' . $e->getMessage(),
                'title' => 'Background Checks Management',
                'subtitle' => 'Manage employee security clearances and background verifications'
            ]);
        }
    }

    /**
     * Show the form for creating a new background check
     */
    public function create()
    {
        try {
            $employees = Employee::select('id', 'nip', 'nama_lengkap', 'unit_organisasi', 'jabatan')
                               ->where('status_kerja', 'Aktif')
                               ->orderBy('nama_lengkap')
                               ->get();

            $checkTypes = [
                'CRIMINAL_RECORD' => 'Criminal Record Check',
                'EMPLOYMENT_HISTORY' => 'Employment History Verification',
                'EDUCATION_VERIFICATION' => 'Education Verification',
                'REFERENCE_CHECK' => 'Reference Check',
                'CREDIT_CHECK' => 'Credit History Check',
                'SECURITY_CLEARANCE' => 'Security Clearance',
                'DRUG_TEST' => 'Drug and Alcohol Test',
                'MEDICAL_CLEARANCE' => 'Medical Clearance',
            ];

            return Inertia::render('BackgroundChecks/Create', [
                'employees' => $employees,
                'checkTypes' => $checkTypes,
                'success' => session('success'),
                'error' => session('error'),
            ]);

        } catch (\Exception $e) {
            Log::error('BackgroundCheck Create Error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()->route('background-checks.index')
                ->with('error', 'Error loading create form: ' . $e->getMessage());
        }
    }

    /**
     * Store a newly created background check
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'employee_id' => 'required|exists:employees,id',
                'check_type' => 'required|string|max:100',
                'check_reference' => 'required|string|max:255|unique:background_checks,check_reference',
                'issuing_authority' => 'required|string|max:255',
                'checked_at' => 'required|date',
                'valid_until' => 'required|date|after:checked_at',
                'status' => 'required|in:pending,cleared,flagged,expired',
                'clearance_level' => 'nullable|string|max:100',
                'notes' => 'nullable|string',
                'document_file' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240', // 10MB
            ]);

            DB::beginTransaction();

            // Handle file upload
            $documentPath = null;
            if ($request->hasFile('document_file')) {
                $documentPath = $request->file('document_file')->store('background_checks', 'public');
            }

            // Create background check
            BackgroundCheck::create([
                ...$validated,
                'document_path' => $documentPath,
                'created_by' => Auth::user() ? Auth::user()->name : 'System',
            ]);

            DB::commit();

            return redirect()->route('background-checks.index')
                ->with('success', 'Background check record created successfully!');

        } catch (ValidationException $e) {
            DB::rollBack();
            return redirect()->back()
                ->withErrors($e->errors())
                ->withInput();

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('BackgroundCheck Store Error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all()
            ]);

            return redirect()->back()
                ->with('error', 'Error creating background check: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Show the form for editing the specified background check
     */
    public function edit(BackgroundCheck $backgroundCheck)
    {
        try {
            $backgroundCheck->load(['employee:id,nip,nama_lengkap,unit_organisasi,jabatan']);

            $employees = Employee::select('id', 'nip', 'nama_lengkap', 'unit_organisasi', 'jabatan')
                               ->where('status_kerja', 'Aktif')
                               ->orderBy('nama_lengkap')
                               ->get();

            $checkTypes = [
                'CRIMINAL_RECORD' => 'Criminal Record Check',
                'EMPLOYMENT_HISTORY' => 'Employment History Verification',
                'EDUCATION_VERIFICATION' => 'Education Verification',
                'REFERENCE_CHECK' => 'Reference Check',
                'CREDIT_CHECK' => 'Credit History Check',
                'SECURITY_CLEARANCE' => 'Security Clearance',
                'DRUG_TEST' => 'Drug and Alcohol Test',
                'MEDICAL_CLEARANCE' => 'Medical Clearance',
            ];

            return Inertia::render('BackgroundChecks/Edit', [
                'backgroundCheck' => $backgroundCheck,
                'employees' => $employees,
                'checkTypes' => $checkTypes,
                'success' => session('success'),
                'error' => session('error'),
            ]);

        } catch (\Exception $e) {
            Log::error('BackgroundCheck Edit Error', [
                'background_check_id' => $backgroundCheck->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()->route('background-checks.index')
                ->with('error', 'Error loading edit form: ' . $e->getMessage());
        }
    }

    /**
     * Update the specified background check
     */
    public function update(Request $request, BackgroundCheck $backgroundCheck)
    {
        try {
            $validated = $request->validate([
                'employee_id' => 'required|exists:employees,id',
                'check_type' => 'required|string|max:100',
                'check_reference' => 'required|string|max:255|unique:background_checks,check_reference,' . $backgroundCheck->id,
                'issuing_authority' => 'required|string|max:255',
                'checked_at' => 'required|date',
                'valid_until' => 'required|date|after:checked_at',
                'status' => 'required|in:pending,cleared,flagged,expired',
                'clearance_level' => 'nullable|string|max:100',
                'notes' => 'nullable|string',
                'document_file' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240',
            ]);

            DB::beginTransaction();

            // Handle file upload
            $documentPath = $backgroundCheck->document_path;
            if ($request->hasFile('document_file')) {
                // Delete old file
                if ($documentPath) {
                    Storage::disk('public')->delete($documentPath);
                }
                $documentPath = $request->file('document_file')->store('background_checks', 'public');
            }

            // Update background check
            $backgroundCheck->update([
                ...$validated,
                'document_path' => $documentPath,
                'updated_by' => Auth::user() ? Auth::user()->name : 'System',
            ]);

            DB::commit();

            return redirect()->route('background-checks.index')
                ->with('success', 'Background check record updated successfully!');

        } catch (ValidationException $e) {
            DB::rollBack();
            return redirect()->back()
                ->withErrors($e->errors())
                ->withInput();

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('BackgroundCheck Update Error', [
                'background_check_id' => $backgroundCheck->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all()
            ]);

            return redirect()->back()
                ->with('error', 'Error updating background check: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Remove the specified background check
     */
    public function destroy(BackgroundCheck $backgroundCheck)
    {
        try {
            DB::beginTransaction();

            // Delete associated file
            if ($backgroundCheck->document_path) {
                Storage::disk('public')->delete($backgroundCheck->document_path);
            }

            // Delete the background check
            $backgroundCheck->delete();

            DB::commit();

            return redirect()->route('background-checks.index')
                ->with('success', 'Background check record deleted successfully!');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('BackgroundCheck Delete Error', [
                'background_check_id' => $backgroundCheck->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()->route('background-checks.index')
                ->with('error', 'Error deleting background check: ' . $e->getMessage());
        }
    }

    /**
     * Download background check document
     */
    public function downloadDocument(BackgroundCheck $backgroundCheck)
    {
        try {
            if (!$backgroundCheck->document_path) {
                return redirect()->back()->with('error', 'No document file found.');
            }

            $filePath = storage_path('app/public/' . $backgroundCheck->document_path);

            if (!file_exists($filePath)) {
                return redirect()->back()->with('error', 'Document file not found.');
            }

            $filename = 'BackgroundCheck_' . $backgroundCheck->check_reference . '_' . $backgroundCheck->employee->nama_lengkap . '.pdf';

            return response()->download($filePath, $filename);

        } catch (\Exception $e) {
            Log::error('BackgroundCheck Download Error', [
                'background_check_id' => $backgroundCheck->id,
                'error' => $e->getMessage()
            ]);

            return redirect()->back()->with('error', 'Error downloading document.');
        }
    }

    /**
     * Bulk update background check statuses
     */
    public function bulkUpdate(Request $request)
    {
        try {
            $validated = $request->validate([
                'ids' => 'required|array',
                'ids.*' => 'exists:background_checks,id',
                'action' => 'required|in:approve,flag,expire,delete',
            ]);

            $count = 0;

            switch ($validated['action']) {
                case 'approve':
                    $count = BackgroundCheck::whereIn('id', $validated['ids'])
                                          ->update(['status' => 'cleared']);
                    break;

                case 'flag':
                    $count = BackgroundCheck::whereIn('id', $validated['ids'])
                                          ->update(['status' => 'flagged']);
                    break;

                case 'expire':
                    $count = BackgroundCheck::whereIn('id', $validated['ids'])
                                          ->update(['status' => 'expired']);
                    break;

                case 'delete':
                    // Delete associated files first
                    $backgroundChecks = BackgroundCheck::whereIn('id', $validated['ids'])->get();
                    foreach ($backgroundChecks as $check) {
                        if ($check->document_path) {
                            Storage::disk('public')->delete($check->document_path);
                        }
                    }

                    $count = BackgroundCheck::whereIn('id', $validated['ids'])->delete();
                    break;
            }

            return redirect()->route('background-checks.index')
                ->with('success', "Successfully {$validated['action']}d {$count} background check(s).");

        } catch (ValidationException $e) {
            return redirect()->back()->withErrors($e->errors());
        } catch (\Exception $e) {
            Log::error('BackgroundCheck Bulk Update Error', [
                'error' => $e->getMessage(),
                'action' => $request->action,
                'ids' => $request->ids
            ]);

            return redirect()->back()
                ->with('error', 'Error performing bulk operation: ' . $e->getMessage());
        }
    }

    /**
     * Get background check statistics for dashboard
     */
    public function getStatistics()
    {
        try {
            $today = Carbon::today();
            $thirtyDaysAgo = $today->copy()->subDays(30);

            return [
                'total_checks' => BackgroundCheck::count(),
                'cleared' => BackgroundCheck::where('status', 'cleared')->count(),
                'pending' => BackgroundCheck::where('status', 'pending')->count(),
                'flagged' => BackgroundCheck::where('status', 'flagged')->count(),
                'expired' => BackgroundCheck::where('valid_until', '<', $today)->count(),
                'expiring_soon' => BackgroundCheck::whereBetween('valid_until', [$today, $today->copy()->addDays(30)])->count(),
                'recent_checks' => BackgroundCheck::where('created_at', '>=', $thirtyDaysAgo)->count(),
                'clearance_rate' => $this->calculateClearanceRate(),
            ];

        } catch (\Exception $e) {
            Log::error('BackgroundCheck Statistics Error', [
                'error' => $e->getMessage()
            ]);

            return [
                'total_checks' => 0,
                'cleared' => 0,
                'pending' => 0,
                'flagged' => 0,
                'expired' => 0,
                'expiring_soon' => 0,
                'recent_checks' => 0,
                'clearance_rate' => 0,
            ];
        }
    }

    /**
     * Calculate clearance rate
     */
    private function calculateClearanceRate()
    {
        $totalChecks = BackgroundCheck::count();
        $clearedChecks = BackgroundCheck::where('status', 'cleared')->count();

        return $totalChecks > 0 ? round(($clearedChecks / $totalChecks) * 100, 2) : 0;
    }

    /**
     * Export background checks data
     */
    public function export(Request $request)
    {
        try {
            $query = BackgroundCheck::with(['employee:id,nip,nama_lengkap,unit_organisasi']);

            // Apply filters from request
            if ($request->status && $request->status !== 'all') {
                $query->where('status', $request->status);
            }

            if ($request->check_type && $request->check_type !== 'all') {
                $query->where('check_type', $request->check_type);
            }

            if ($request->department && $request->department !== 'all') {
                $query->whereHas('employee', function($q) use ($request) {
                    $q->where('unit_organisasi', $request->department);
                });
            }

            $backgroundChecks = $query->orderBy('checked_at', 'desc')->get();

            return response()->json([
                'success' => true,
                'data' => $backgroundChecks,
                'filename' => 'background_checks_' . Carbon::now()->format('Y_m_d_H_i_s') . '.json'
            ]);

        } catch (\Exception $e) {
            Log::error('BackgroundCheck Export Error', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Error exporting background checks: ' . $e->getMessage()
            ], 500);
        }
    }
}
