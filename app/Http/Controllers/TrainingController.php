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
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class TrainingController extends Controller
{
    protected $certificateService;
    protected $notificationService;
    protected $exportService;

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
        $query = TrainingRecord::with(['employee', 'trainingType']);

        // Search functionality
        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('certificate_number', 'like', "%{$search}%")
                  ->orWhereHas('employee', function($employeeQuery) use ($search) {
                      $employeeQuery->where('name', 'like', "%{$search}%")
                                   ->orWhere('nik', 'like', "%{$search}%")
                                   ->orWhere('nip', 'like', "%{$search}%");
                  })
                  ->orWhereHas('trainingType', function($typeQuery) use ($search) {
                      $typeQuery->where('name', 'like', "%{$search}%");
                  });
            });
        }

        // Filters
        if ($request->has('training_type') && $request->training_type !== 'all') {
            $query->where('training_type_id', $request->training_type);
        }

        if ($request->has('department') && $request->department !== 'all') {
            $query->whereHas('employee', function($q) use ($request) {
                $q->where('department', $request->department);
            });
        }

        if ($request->has('status') && $request->status !== 'all') {
            switch ($request->status) {
                case 'valid':
                    $query->where('expiry_date', '>', Carbon::now());
                    break;
                case 'expired':
                    $query->where('expiry_date', '<=', Carbon::now());
                    break;
                case 'expiring':
                    $query->whereBetween('expiry_date', [Carbon::now(), Carbon::now()->addDays(30)]);
                    break;
            }
        }

        // Date filters
        if ($request->has('date_from')) {
            $query->where('issue_date', '>=', $request->date_from);
        }

        if ($request->has('date_to')) {
            $query->where('issue_date', '<=', $request->date_to);
        }

        // Sorting
        $sortField = $request->get('sort', 'expiry_date');
        $sortDirection = $request->get('direction', 'asc');

        if (in_array($sortField, ['issue_date', 'expiry_date', 'created_at'])) {
            $query->orderBy($sortField, $sortDirection);
        } elseif ($sortField === 'employee_name') {
            $query->join('employees', 'training_records.employee_id', '=', 'employees.id')
                  ->orderBy('employees.name', $sortDirection)
                  ->select('training_records.*');
        } elseif ($sortField === 'training_type') {
            $query->join('training_types', 'training_records.training_type_id', '=', 'training_types.id')
                  ->orderBy('training_types.name', $sortDirection)
                  ->select('training_records.*');
        }

        // Pagination
        $perPage = $request->get('per_page', 20);
        $trainingRecords = $query->paginate($perPage);

        // Get filter options
        $trainingTypes = TrainingType::where('is_active', true)->get();
        $departments = Employee::distinct('department')->pluck('department')->filter();

        // Statistics
        $stats = [
            'total_records' => TrainingRecord::count(),
            'valid_records' => TrainingRecord::where('expiry_date', '>', Carbon::now())->count(),
            'expired_records' => TrainingRecord::where('expiry_date', '<=', Carbon::now())->count(),
            'expiring_soon' => TrainingRecord::whereBetween('expiry_date', [
                Carbon::now(),
                Carbon::now()->addDays(30)
            ])->count(),
        ];

        // Notifications
        $notifications = [
            'expiring_certificates' => $this->certificateService->getExpiringCertificates(30)->take(5),
        ];

        return Inertia::render('Training/Index', [
            'trainingRecords' => $trainingRecords,
            'filters' => $request->only(['search', 'training_type', 'department', 'status', 'date_from', 'date_to', 'sort', 'direction']),
            'trainingTypes' => $trainingTypes,
            'departments' => $departments,
            'stats' => $stats,
            'notifications' => $notifications,
            'title' => 'Training Records Management',
            'subtitle' => 'Kelola data pelatihan dan sertifikasi karyawan'
        ]);
    }

    /**
     * Show the form for creating a new training record
     */
    public function create()
    {
        $employees = Employee::where('is_active', true)->orderBy('name')->get();
        $trainingTypes = TrainingType::where('is_active', true)->orderBy('name')->get();

        return Inertia::render('Training/Create', [
            'employees' => $employees,
            'trainingTypes' => $trainingTypes,
            'title' => 'Add New Training Record',
            'subtitle' => 'Tambah data pelatihan karyawan baru'
        ]);
    }

    /**
     * Store a newly created training record with auto certificate generation
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'employee_id' => 'required|exists:employees,id',
            'training_type_id' => 'required|exists:training_types,id',
            'issue_date' => 'required|date',
            'expiry_date' => 'required|date|after:issue_date',
            'training_provider' => 'nullable|string|max:255',
            'cost' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
            'auto_generate_certificate' => 'boolean',
            'send_notification' => 'boolean',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        try {
            DB::beginTransaction();

            // Create training record
            $trainingRecord = TrainingRecord::create([
                'employee_id' => $request->employee_id,
                'training_type_id' => $request->training_type_id,
                'issue_date' => $request->issue_date,
                'expiry_date' => $request->expiry_date,
                'completion_status' => 'COMPLETED',
                'training_provider' => $request->training_provider,
                'cost' => $request->cost,
                'notes' => $request->notes,
            ]);

            $messages = ['Training record created successfully!'];

            // Auto generate certificate if requested
            if ($request->get('auto_generate_certificate', true)) {
                try {
                    $certificateResult = $this->certificateService->generateCertificate($trainingRecord);
                    $messages[] = 'Certificate generated: ' . $certificateResult['certificate_number'];
                } catch (\Exception $e) {
                    $messages[] = 'Warning: Certificate generation failed - ' . $e->getMessage();
                }
            }

            // Send notification if requested
            if ($request->get('send_notification', true)) {
                try {
                    $this->notificationService->sendTrainingAssignmentNotification($trainingRecord);
                    $messages[] = 'Notification sent to employee.';
                } catch (\Exception $e) {
                    $messages[] = 'Warning: Notification failed - ' . $e->getMessage();
                }
            }

            DB::commit();

            return redirect()->route('training.index')
                           ->with('success', implode(' ', $messages));

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Failed to create training record: ' . $e->getMessage()])->withInput();
        }
    }

    /**
     * Display the specified training record
     */
    public function show(TrainingRecord $training)
    {
        $training->load(['employee', 'trainingType']);

        // Check certificate validity
        $certificateValidity = [
            'is_valid' => $this->certificateService->isCertificateValid($training),
            'days_until_expiry' => Carbon::now()->diffInDays($training->expiry_date, false),
            'status' => $training->expiry_date > Carbon::now() ? 'Valid' : 'Expired'
        ];

        return Inertia::render('Training/Show', [
            'training' => $training,
            'certificateValidity' => $certificateValidity,
            'title' => 'Training Record Details',
            'subtitle' => 'Detail data pelatihan karyawan'
        ]);
    }

    /**
     * Show the form for editing the specified training record
     */
    public function edit(TrainingRecord $training)
    {
        $training->load(['employee', 'trainingType']);
        $employees = Employee::where('is_active', true)->orderBy('name')->get();
        $trainingTypes = TrainingType::where('is_active', true)->orderBy('name')->get();

        return Inertia::render('Training/Edit', [
            'training' => $training,
            'employees' => $employees,
            'trainingTypes' => $trainingTypes,
            'title' => 'Edit Training Record',
            'subtitle' => 'Edit data pelatihan karyawan'
        ]);
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
            'training_provider' => 'nullable|string|max:255',
            'cost' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
            'regenerate_certificate' => 'boolean',
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
                'training_provider' => $request->training_provider,
                'cost' => $request->cost,
                'notes' => $request->notes,
            ]);

            $messages = ['Training record updated successfully!'];

            // Regenerate certificate if requested
            if ($request->get('regenerate_certificate', false)) {
                try {
                    $certificateResult = $this->certificateService->generateCertificate($training);
                    $messages[] = 'New certificate generated: ' . $certificateResult['certificate_number'];
                } catch (\Exception $e) {
                    $messages[] = 'Warning: Certificate regeneration failed - ' . $e->getMessage();
                }
            }

            DB::commit();

            return redirect()->route('training.show', $training)
                           ->with('success', implode(' ', $messages));

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Failed to update training record: ' . $e->getMessage()])->withInput();
        }
    }

    /**
     * Remove the specified training record
     */
    public function destroy(TrainingRecord $training)
    {
        try {
            // Delete associated certificate file if exists
            if ($training->certificate_path && Storage::disk('public')->exists($training->certificate_path)) {
                Storage::disk('public')->delete($training->certificate_path);
            }

            $training->delete();

            return redirect()->route('training.index')
                           ->with('success', 'Training record deleted successfully!');

        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Failed to delete training record: ' . $e->getMessage()]);
        }
    }

    /**
     * Download certificate
     */
    public function downloadCertificate(TrainingRecord $training)
    {
        try {
            // Generate certificate if not exists
            if (!$training->certificate_number) {
                $certificateResult = $this->certificateService->generateCertificate($training);
            }

            // If certificate file doesn't exist, regenerate it
            if (!$training->certificate_path || !Storage::disk('public')->exists($training->certificate_path)) {
                $certificateResult = $this->certificateService->generateCertificate($training);
                $training->refresh();
            }

            $filePath = storage_path('app/public/' . $training->certificate_path);

            if (!file_exists($filePath)) {
                return back()->withErrors(['error' => 'Certificate file not found.']);
            }

            return response()->download($filePath, 'certificate_' . $training->certificate_number . '.pdf');

        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Failed to download certificate: ' . $e->getMessage()]);
        }
    }

    /**
     * Bulk certificate generation
     */
    public function bulkGenerateCertificates(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'training_record_ids' => 'required|array',
            'training_record_ids.*' => 'exists:training_records,id',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator);
        }

        try {
            $results = $this->certificateService->generateBulkCertificates($request->training_record_ids);

            // Send notification to user
            $this->notificationService->sendBulkCertificateNotification($results, auth()->user());

            return back()->with('success',
                "Bulk certificate generation completed. {$results['successful']} certificates generated, {$results['failed']} failed."
            );

        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Bulk certificate generation failed: ' . $e->getMessage()]);
        }
    }

    /**
     * Renew training record
     */
    public function renew(Request $request, TrainingRecord $training)
    {
        $validator = Validator::make($request->all(), [
            'new_expiry_date' => 'required|date|after:today',
            'training_provider' => 'nullable|string|max:255',
            'cost' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator);
        }

        try {
            DB::beginTransaction();

            // Create new training record for renewal
            $newTraining = TrainingRecord::create([
                'employee_id' => $training->employee_id,
                'training_type_id' => $training->training_type_id,
                'issue_date' => Carbon::now(),
                'expiry_date' => $request->new_expiry_date,
                'completion_status' => 'COMPLETED',
                'training_provider' => $request->training_provider ?? $training->training_provider,
                'cost' => $request->cost,
                'notes' => $request->notes,
                'previous_training_id' => $training->id,
            ]);

            // Generate new certificate
            $certificateResult = $this->certificateService->generateCertificate($newTraining);

            // Send notification
            $this->notificationService->sendTrainingAssignmentNotification($newTraining);

            DB::commit();

            return back()->with('success',
                'Training renewed successfully! New certificate: ' . $certificateResult['certificate_number']
            );

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Failed to renew training: ' . $e->getMessage()]);
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
     * Export selected training records
     */
    public function exportSelected(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'training_record_ids' => 'required|array',
            'training_record_ids.*' => 'exists:training_records,id',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator);
        }

        try {
            $trainingRecords = TrainingRecord::whereIn('id', $request->training_record_ids)
                                           ->with(['employee', 'trainingType'])
                                           ->get();

            $filePath = $this->exportService->exportTrainingRecordsOnly($trainingRecords);

            return response()->download(storage_path('app/public/' . $filePath))
                           ->deleteFileAfterSend(true);

        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Export failed: ' . $e->getMessage()]);
        }
    }

    /**
     * Get import template
     */
    public function downloadImportTemplate()
    {
        try {
            $filePath = $this->exportService->getImportTemplate();

            return response()->download(storage_path('app/public/' . $filePath))
                           ->deleteFileAfterSend(true);

        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Template download failed: ' . $e->getMessage()]);
        }
    }

    /**
     * Send expiry notifications manually
     */
    public function sendExpiryNotifications(Request $request)
    {
        $days = $request->get('days', 30);

        try {
            $results = $this->notificationService->sendExpiringCertificateNotifications($days);

            return back()->with('success',
                "Expiry notifications sent! {$results['employees_notified']} employees notified for {$results['total_notifications']} expiring certificates."
            );

        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Failed to send notifications: ' . $e->getMessage()]);
        }
    }

    /**
     * Bulk update training records
     */
    public function bulkUpdate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'training_record_ids' => 'required|array',
            'training_record_ids.*' => 'exists:training_records,id',
            'action' => 'required|in:extend,update_provider,regenerate_certificates',
            'days_to_extend' => 'required_if:action,extend|integer|min:1',
            'new_provider' => 'required_if:action,update_provider|string|max:255',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator);
        }

        try {
            DB::beginTransaction();

            $trainingRecords = TrainingRecord::whereIn('id', $request->training_record_ids);
            $count = $trainingRecords->count();

            switch ($request->action) {
                case 'extend':
                    $trainingRecords->update([
                        'expiry_date' => DB::raw("DATE_ADD(expiry_date, INTERVAL {$request->days_to_extend} DAY)")
                    ]);
                    $message = "$count training records extended by {$request->days_to_extend} days!";
                    break;

                case 'update_provider':
                    $trainingRecords->update(['training_provider' => $request->new_provider]);
                    $message = "$count training records updated with new provider!";
                    break;

                case 'regenerate_certificates':
                    $results = $this->certificateService->generateBulkCertificates($request->training_record_ids);
                    $message = "Certificates regenerated: {$results['successful']} successful, {$results['failed']} failed!";
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
