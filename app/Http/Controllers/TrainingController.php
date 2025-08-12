<?php

namespace App\Http\Controllers;

use App\Models\TrainingRecord;
use App\Models\TrainingType;
use App\Models\Employee;
use App\Services\CertificateGenerationService;
use App\Services\NotificationService;
use App\Services\ExportService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class TrainingController extends Controller
{
    protected $certificateService;
    protected $notificationService;
    protected $exportService;

    /**
     * Constructor with dependency injection
     */
    public function __construct(
        CertificateGenerationService $certificateService,
        NotificationService $notificationService,
        ExportService $exportService
    ) {
        $this->certificateService = $certificateService;
        $this->notificationService = $notificationService;
        $this->exportService = $exportService;
    }

    /**
     * Display a listing of training records
     */
    public function index(Request $request)
    {
        try {
            $perPage = $request->get('per_page', 15);
            $search = $request->get('search');
            $trainingType = $request->get('training_type');
            $status = $request->get('status');
            $employee = $request->get('employee');
            $expiry = $request->get('expiry');
            $department = $request->get('department');
            $sortField = $request->get('sort', 'created_at');
            $sortDirection = $request->get('direction', 'desc');

            $query = TrainingRecord::with(['employee', 'trainingType']);

            // Search functionality
            if (!empty($search)) {
                $query->where(function($q) use ($search) {
                    $q->where('certificate_number', 'like', "%{$search}%")
                      ->orWhereHas('employee', function($employeeQuery) use ($search) {
                          $employeeQuery->where('nama_lengkap', 'like', "%{$search}%")
                                       ->orWhere('nip', 'like', "%{$search}%");
                      })
                      ->orWhereHas('trainingType', function($typeQuery) use ($search) {
                          $typeQuery->where('name', 'like', "%{$search}%");
                      });
                });
            }

            // Filters
            if ($trainingType && $trainingType !== 'all') {
                $query->where('training_type_id', $trainingType);
            }

            if ($department && $department !== 'all') {
                $query->whereHas('employee', function($q) use ($department) {
                    $q->where('unit_organisasi', $department);
                });
            }

            if ($status && $status !== 'all') {
                switch ($status) {
                    case 'valid':
                        $query->where('expiry_date', '>', Carbon::now());
                        break;
                    case 'expired':
                        $query->where('expiry_date', '<=', Carbon::now());
                        break;
                    case 'expiring':
                        $query->where('expiry_date', '>', Carbon::now())
                              ->where('expiry_date', '<=', Carbon::now()->addDays(30));
                        break;
                }
            }

            if ($employee && $employee !== 'all') {
                $query->where('employee_id', $employee);
            }

            if ($expiry && $expiry !== 'all') {
                switch ($expiry) {
                    case '7':
                        $query->where('expiry_date', '>', Carbon::now())
                              ->where('expiry_date', '<=', Carbon::now()->addDays(7));
                        break;
                    case '30':
                        $query->where('expiry_date', '>', Carbon::now())
                              ->where('expiry_date', '<=', Carbon::now()->addDays(30));
                        break;
                    case '90':
                        $query->where('expiry_date', '>', Carbon::now())
                              ->where('expiry_date', '<=', Carbon::now()->addDays(90));
                        break;
                }
            }

            // Sorting
            switch ($sortField) {
                case 'employee_name':
                    $query->join('employees', 'training_records.employee_id', '=', 'employees.id')
                          ->orderBy('employees.nama_lengkap', $sortDirection)
                          ->select('training_records.*');
                    break;
                case 'training_type':
                    $query->join('training_types', 'training_records.training_type_id', '=', 'training_types.id')
                          ->orderBy('training_types.name', $sortDirection)
                          ->select('training_records.*');
                    break;
                default:
                    $query->orderBy($sortField, $sortDirection);
                    break;
            }

            // Get paginated results
            $trainingRecords = $query->paginate($perPage);

            // Calculate statistics
            $statistics = $this->calculateTrainingStatistics();

            // Get filter options
            $filterOptions = $this->getFilterOptions();

            return Inertia::render('Training/Index', [
                'trainingRecords' => $trainingRecords,
                'pagination' => [
                    'current_page' => $trainingRecords->currentPage(),
                    'last_page' => $trainingRecords->lastPage(),
                    'per_page' => $trainingRecords->perPage(),
                    'total' => $trainingRecords->total(),
                    'from' => $trainingRecords->firstItem(),
                    'to' => $trainingRecords->lastItem(),
                ],
                'filters' => [
                    'search' => $search,
                    'training_type' => $trainingType,
                    'status' => $status,
                    'employee' => $employee,
                    'expiry' => $expiry,
                    'department' => $department,
                    'sort' => $sortField,
                    'direction' => $sortDirection,
                ],
                'filterOptions' => $filterOptions,
                'statistics' => $statistics,
                'success' => session('success'),
                'error' => session('error'),
                'title' => 'Training Records Management',
                'subtitle' => 'Kelola data pelatihan dan sertifikasi karyawan PT Gapura Angkasa',
            ]);

        } catch (\Exception $e) {
            Log::error('Training Index Error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return Inertia::render('Training/Index', [
                'trainingRecords' => ['data' => []],
                'pagination' => [],
                'filters' => [],
                'filterOptions' => [],
                'statistics' => [],
                'error' => 'Error loading training records: ' . $e->getMessage(),
            ]);
        }
    }

    /**
     * Show the form for creating a new training record
     */
    public function create()
    {
        try {
            $employees = Employee::where('status_kerja', 'Aktif')
                               ->orderBy('nama_lengkap')
                               ->get(['id', 'nip', 'nama_lengkap', 'unit_organisasi']);

            $trainingTypes = TrainingType::where('is_active', true)
                                       ->orderBy('name')
                                       ->get(['id', 'name', 'category', 'validity_period', 'is_mandatory']);

            return Inertia::render('Training/Create', [
                'employees' => $employees,
                'trainingTypes' => $trainingTypes,
                'title' => 'Add New Training Record',
                'subtitle' => 'Create new training record for employee'
            ]);

        } catch (\Exception $e) {
            Log::error('Training Create Error', [
                'error' => $e->getMessage()
            ]);

            return redirect()->route('training.index')
                           ->with('error', 'Error loading create form: ' . $e->getMessage());
        }
    }

    /**
     * Store a newly created training record
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'employee_id' => 'required|exists:employees,id',
            'training_type_id' => 'required|exists:training_types,id',
            'issue_date' => 'required|date',
            'expiry_date' => 'required|date|after:issue_date',
            'certificate_number' => 'nullable|string|max:255|unique:training_records,certificate_number',
            'training_provider' => 'nullable|string|max:255',
            'cost' => 'nullable|numeric|min:0',
            'completion_status' => 'required|in:completed,in_progress,cancelled',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        try {
            DB::beginTransaction();

            $trainingRecord = TrainingRecord::create([
                'employee_id' => $request->employee_id,
                'training_type_id' => $request->training_type_id,
                'issue_date' => $request->issue_date,
                'expiry_date' => $request->expiry_date,
                'certificate_number' => $request->certificate_number,
                'training_provider' => $request->training_provider,
                'cost' => $request->cost,
                'completion_status' => $request->completion_status,
                'notes' => $request->notes,
            ]);

            $messages = ['Training record created successfully!'];

            // Auto generate certificate if requested
            if ($request->get('auto_generate_certificate', false)) {
                try {
                    $certificateResult = $this->certificateService->generateCertificate($trainingRecord);
                    if ($certificateResult['success']) {
                        $messages[] = 'Certificate generated: ' . $certificateResult['certificate_number'];
                    }
                } catch (\Exception $e) {
                    $messages[] = 'Warning: Certificate generation failed - ' . $e->getMessage();
                }
            }

            // Send notification if requested
            if ($request->get('send_notification', false)) {
                try {
                    $this->notificationService->sendTrainingAssignmentNotification($trainingRecord);
                    $messages[] = 'Notification sent to employee.';
                } catch (\Exception $e) {
                    $messages[] = 'Warning: Notification failed - ' . $e->getMessage();
                }
            }

            DB::commit();

            Log::info('Training record created successfully', [
                'training_record_id' => $trainingRecord->id,
                'employee_id' => $request->employee_id,
                'training_type_id' => $request->training_type_id
            ]);

            return redirect()->route('training.index')
                           ->with('success', implode(' ', $messages));

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Training Store Error', [
                'error' => $e->getMessage(),
                'request_data' => $request->all()
            ]);

            return back()->withErrors(['error' => 'Failed to create training record: ' . $e->getMessage()])->withInput();
        }
    }

    /**
     * Display the specified training record
     */
    public function show(TrainingRecord $training)
    {
        try {
            $training->load(['employee', 'trainingType']);

            return Inertia::render('Training/Show', [
                'training' => $training,
                'title' => 'Training Record Details',
                'subtitle' => 'View training record information'
            ]);

        } catch (\Exception $e) {
            Log::error('Training Show Error', [
                'training_id' => $training->id,
                'error' => $e->getMessage()
            ]);

            return redirect()->route('training.index')
                           ->with('error', 'Error loading training record details.');
        }
    }

    /**
     * Show the form for editing the specified training record
     */
    public function edit(TrainingRecord $training)
    {
        try {
            $training->load(['employee', 'trainingType']);

            $employees = Employee::where('status_kerja', 'Aktif')
                               ->orderBy('nama_lengkap')
                               ->get(['id', 'nip', 'nama_lengkap', 'unit_organisasi']);

            $trainingTypes = TrainingType::where('is_active', true)
                                       ->orderBy('name')
                                       ->get(['id', 'name', 'category', 'validity_period', 'is_mandatory']);

            return Inertia::render('Training/Edit', [
                'training' => $training,
                'employees' => $employees,
                'trainingTypes' => $trainingTypes,
                'title' => 'Edit Training Record',
                'subtitle' => 'Update training record information'
            ]);

        } catch (\Exception $e) {
            Log::error('Training Edit Error', [
                'training_id' => $training->id,
                'error' => $e->getMessage()
            ]);

            return redirect()->route('training.index')
                           ->with('error', 'Error loading edit form.');
        }
    }

    /**
     * Update the specified training record
     */
    public function update(Request $request, TrainingRecord $training)
    {
        $validator = Validator::make($request->all(), [
            'employee_id' => 'required|exists:employees,id',
            'training_type_id' => 'required|exists:training_types,id',
            'issue_date' => 'required|date',
            'expiry_date' => 'required|date|after:issue_date',
            'certificate_number' => 'nullable|string|max:255|unique:training_records,certificate_number,' . $training->id,
            'training_provider' => 'nullable|string|max:255',
            'cost' => 'nullable|numeric|min:0',
            'completion_status' => 'required|in:completed,in_progress,cancelled',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        try {
            DB::beginTransaction();

            $training->update([
                'employee_id' => $request->employee_id,
                'training_type_id' => $request->training_type_id,
                'issue_date' => $request->issue_date,
                'expiry_date' => $request->expiry_date,
                'certificate_number' => $request->certificate_number,
                'training_provider' => $request->training_provider,
                'cost' => $request->cost,
                'completion_status' => $request->completion_status,
                'notes' => $request->notes,
            ]);

            $messages = ['Training record updated successfully!'];

            // Regenerate certificate if requested
            if ($request->get('regenerate_certificate', false)) {
                try {
                    $certificateResult = $this->certificateService->generateCertificate($training);
                    if ($certificateResult['success']) {
                        $messages[] = 'New certificate generated: ' . $certificateResult['certificate_number'];
                    }
                } catch (\Exception $e) {
                    $messages[] = 'Warning: Certificate regeneration failed - ' . $e->getMessage();
                }
            }

            DB::commit();

            Log::info('Training record updated successfully', [
                'training_record_id' => $training->id
            ]);

            return redirect()->route('training.show', $training)
                           ->with('success', implode(' ', $messages));

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Training Update Error', [
                'training_id' => $training->id,
                'error' => $e->getMessage()
            ]);

            return back()->withErrors(['error' => 'Failed to update training record: ' . $e->getMessage()])->withInput();
        }
    }

    /**
     * Remove the specified training record
     */
    public function destroy(TrainingRecord $training)
    {
        try {
            $training->delete();

            Log::info('Training record deleted successfully', [
                'training_record_id' => $training->id
            ]);

            return redirect()->route('training.index')
                           ->with('success', 'Training record deleted successfully!');

        } catch (\Exception $e) {
            Log::error('Training Delete Error', [
                'training_id' => $training->id,
                'error' => $e->getMessage()
            ]);

            return back()->withErrors(['error' => 'Failed to delete training record: ' . $e->getMessage()]);
        }
    }

    /**
     * Export training records
     */
    public function export(Request $request)
    {
        try {
            $filters = $request->only(['department', 'training_type', 'status', 'date_from', 'date_to']);
            $filePath = $this->exportService->exportTrainingRecords($filters);

            return response()->download(storage_path('app/public/' . $filePath))
                           ->deleteFileAfterSend(true);

        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Export failed: ' . $e->getMessage()]);
        }
    }

    /**
     * Generate certificate for training record
     */
    public function generateCertificate(TrainingRecord $training)
    {
        try {
            $result = $this->certificateService->generateCertificate($training);

            if ($result['success']) {
                return back()->with('success', 'Certificate generated successfully: ' . $result['certificate_number']);
            } else {
                return back()->withErrors(['error' => 'Certificate generation failed: ' . ($result['error'] ?? 'Unknown error')]);
            }

        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Certificate generation failed: ' . $e->getMessage()]);
        }
    }

    /**
     * Send expiry notifications
     */
    public function sendExpiryNotifications(Request $request)
    {
        try {
            $days = $request->get('days', 30);
            $result = $this->notificationService->sendExpiringCertificateNotifications($days);

            if ($result['success']) {
                return back()->with('success', "Notifications sent to {$result['employees_notified']} employees for {$result['total_notifications']} expiring certificates.");
            } else {
                return back()->withErrors(['error' => 'Failed to send notifications: ' . ($result['error'] ?? 'Unknown error')]);
            }

        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Failed to send notifications: ' . $e->getMessage()]);
        }
    }

    /**
     * Calculate training statistics
     */
    private function calculateTrainingStatistics()
    {
        try {
            $total = TrainingRecord::count();
            $valid = TrainingRecord::where('expiry_date', '>', Carbon::now())->count();
            $expired = TrainingRecord::where('expiry_date', '<=', Carbon::now())->count();
            $expiring = TrainingRecord::where('expiry_date', '>', Carbon::now())
                                    ->where('expiry_date', '<=', Carbon::now()->addDays(30))
                                    ->count();

            return [
                'total' => $total,
                'valid' => $valid,
                'expired' => $expired,
                'expiring' => $expiring,
                'compliance_rate' => $total > 0 ? round(($valid / $total) * 100, 2) : 0
            ];

        } catch (\Exception $e) {
            Log::error('Calculate Training Statistics Error', [
                'error' => $e->getMessage()
            ]);

            return [
                'total' => 0,
                'valid' => 0,
                'expired' => 0,
                'expiring' => 0,
                'compliance_rate' => 0
            ];
        }
    }

    /**
     * Get filter options for dropdowns
     */
    private function getFilterOptions()
    {
        try {
            $trainingTypes = TrainingType::where('is_active', true)
                                       ->orderBy('name')
                                       ->get(['id', 'name']);

            $departments = Employee::where('status_kerja', 'Aktif')
                                 ->whereNotNull('unit_organisasi')
                                 ->distinct()
                                 ->pluck('unit_organisasi')
                                 ->filter()
                                 ->sort()
                                 ->values();

            $employees = Employee::where('status_kerja', 'Aktif')
                               ->orderBy('nama_lengkap')
                               ->get(['id', 'nip', 'nama_lengkap']);

            return [
                'training_types' => $trainingTypes,
                'departments' => $departments,
                'employees' => $employees,
            ];

        } catch (\Exception $e) {
            Log::error('Get Filter Options Error', [
                'error' => $e->getMessage()
            ]);

            return [
                'training_types' => [],
                'departments' => [],
                'employees' => [],
            ];
        }
    }
}
