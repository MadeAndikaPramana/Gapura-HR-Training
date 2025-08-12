<?php

namespace App\Http\Controllers;

use App\Models\TrainingRecord;
use App\Models\TrainingType;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Carbon\Carbon;

class TrainingController extends Controller
{
    /**
     * Display a listing of training records with filters and search
     * Following the pattern from EmployeeController
     */
    public function index(Request $request)
    {
        try {
            // Get filter parameters
            $search = $request->get('search');
            $trainingType = $request->get('training_type');
            $status = $request->get('status');
            $employee = $request->get('employee');
            $expiry = $request->get('expiry');
            $department = $request->get('department');
            $perPage = $request->get('per_page', 20);
            $sortField = $request->get('sort', 'expiry_date');
            $sortDirection = $request->get('direction', 'asc');

            // Build query with relationships
            $query = TrainingRecord::with([
                'employee:id,nip,nama_lengkap,unit_organisasi,jabatan,email,handphone',
                'trainingType:id,name,category,validity_period'
            ]);

            // Apply search filter
            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('certificate_number', 'like', "%{$search}%")
                      ->orWhere('training_provider', 'like', "%{$search}%")
                      ->orWhere('notes', 'like', "%{$search}%")
                      ->orWhereHas('employee', function ($eq) use ($search) {
                          $eq->where('nama_lengkap', 'like', "%{$search}%")
                             ->orWhere('nip', 'like', "%{$search}%");
                      })
                      ->orWhereHas('trainingType', function ($tq) use ($search) {
                          $tq->where('name', 'like', "%{$search}%")
                             ->orWhere('category', 'like', "%{$search}%");
                      });
                });
            }

            // Apply training type filter
            if ($trainingType && $trainingType !== 'all') {
                $query->where('training_type_id', $trainingType);
            }

            // Apply status filter (based on expiry date)
            if ($status && $status !== 'all') {
                $today = Carbon::today();

                switch ($status) {
                    case 'valid':
                        $query->where('expiry_date', '>', $today);
                        break;
                    case 'due_soon':
                        $query->whereBetween('expiry_date', [$today, $today->copy()->addDays(30)]);
                        break;
                    case 'expired':
                        $query->where('expiry_date', '<=', $today);
                        break;
                }
            }

            // Apply expiry period filter
            if ($expiry && $expiry !== 'all') {
                $today = Carbon::today();

                switch ($expiry) {
                    case 'this_month':
                        $query->whereBetween('expiry_date', [$today, $today->copy()->endOfMonth()]);
                        break;
                    case 'next_30_days':
                        $query->whereBetween('expiry_date', [$today, $today->copy()->addDays(30)]);
                        break;
                    case 'next_90_days':
                        $query->whereBetween('expiry_date', [$today, $today->copy()->addDays(90)]);
                        break;
                    case 'expired':
                        $query->where('expiry_date', '<', $today);
                        break;
                }
            }

            // Apply department filter
            if ($department && $department !== 'all') {
                $query->whereHas('employee', function ($eq) use ($department) {
                    $eq->where('unit_organisasi', $department);
                });
            }

            // Apply sorting
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
                    'first_page_url' => $trainingRecords->url(1),
                    'last_page_url' => $trainingRecords->url($trainingRecords->lastPage()),
                    'next_page_url' => $trainingRecords->nextPageUrl(),
                    'prev_page_url' => $trainingRecords->previousPageUrl(),
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
                'message' => session('message'),
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
                'title' => 'Training Records Management',
                'subtitle' => 'Kelola data pelatihan dan sertifikasi karyawan PT Gapura Angkasa',
            ]);
        }
    }

    /**
     * Show the form for creating a new training record
     */
    public function create()
    {
        try {
            $employees = Employee::select('id', 'nip', 'nama_lengkap', 'unit_organisasi', 'jabatan')
                                ->where('status_kerja', 'Aktif')
                                ->orderBy('nama_lengkap')
                                ->get();

            $trainingTypes = TrainingType::select('id', 'name', 'category', 'validity_period')
                                       ->where('is_active', true)
                                       ->orderBy('category')
                                       ->orderBy('name')
                                       ->get();

            return Inertia::render('Training/Create', [
                'employees' => $employees,
                'trainingTypes' => $trainingTypes,
                'success' => session('success'),
                'error' => session('error'),
                'message' => session('message'),
            ]);

        } catch (\Exception $e) {
            Log::error('Training Create Error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
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
        try {
            // Validation rules
            $validated = $request->validate([
                'employee_id' => 'required|exists:employees,id',
                'training_type_id' => 'required|exists:training_types,id',
                'certificate_number' => 'required|string|max:255|unique:training_records,certificate_number',
                'training_provider' => 'required|string|max:255',
                'issue_date' => 'required|date',
                'expiry_date' => 'required|date|after:issue_date',
                'validity_period' => 'nullable|integer|min:1|max:120',
                'training_location' => 'nullable|string|max:255',
                'training_duration' => 'nullable|string|max:100',
                'instructor_name' => 'nullable|string|max:255',
                'completion_status' => 'required|in:completed,in_progress,failed,cancelled',
                'training_cost' => 'nullable|numeric|min:0',
                'internal_external' => 'required|in:internal,external',
                'batch_id' => 'nullable|string|max:100',
                'notes' => 'nullable|string',
                'compliance_requirements' => 'nullable|string',
                'renewal_required' => 'boolean',
                'notification_before_expiry' => 'nullable|integer|min:1|max:365',
                'certificate_file' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240', // 10MB
                'supporting_documents.*' => 'nullable|file|mimes:pdf,jpg,jpeg,png,doc,docx|max:5120', // 5MB each
            ]);

            DB::beginTransaction();

            // Handle file uploads
            $certificateFilePath = null;
            if ($request->hasFile('certificate_file')) {
                $certificateFilePath = $request->file('certificate_file')->store('certificates', 'public');
            }

            $supportingDocuments = [];
            if ($request->hasFile('supporting_documents')) {
                foreach ($request->file('supporting_documents') as $file) {
                    $path = $file->store('supporting_documents', 'public');
                    $supportingDocuments[] = [
                        'filename' => $file->getClientOriginalName(),
                        'path' => $path,
                    ];
                }
            }

            // Create training record
            $trainingRecord = TrainingRecord::create([
                'employee_id' => $validated['employee_id'],
                'training_type_id' => $validated['training_type_id'],
                'certificate_number' => $validated['certificate_number'],
                'training_provider' => $validated['training_provider'],
                'issue_date' => $validated['issue_date'],
                'expiry_date' => $validated['expiry_date'],
                'validity_period' => $validated['validity_period'],
                'training_location' => $validated['training_location'],
                'training_duration' => $validated['training_duration'],
                'instructor_name' => $validated['instructor_name'],
                'completion_status' => $validated['completion_status'],
                'training_cost' => $validated['training_cost'],
                'internal_external' => $validated['internal_external'],
                'batch_id' => $validated['batch_id'],
                'notes' => $validated['notes'],
                'compliance_requirements' => $validated['compliance_requirements'],
                'renewal_required' => $validated['renewal_required'] ?? false,
                'notification_before_expiry' => $validated['notification_before_expiry'] ?? 30,
                'certificate_file' => $certificateFilePath,
                'supporting_documents' => !empty($supportingDocuments) ? json_encode($supportingDocuments) : null,
                'created_by' => auth()->user()->name,
            ]);

            DB::commit();

            return redirect()->route('training.index')
                ->with('success', 'Training record created successfully!');

        } catch (ValidationException $e) {
            DB::rollBack();
            return redirect()->back()
                ->withErrors($e->errors())
                ->withInput();

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Training Store Error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all()
            ]);

            return redirect()->back()
                ->with('error', 'Error creating training record: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Display the specified training record
     */
    public function show(TrainingRecord $training)
    {
        try {
            $training->load([
                'employee:id,nip,nama_lengkap,unit_organisasi,jabatan,email,handphone',
                'trainingType:id,name,category,validity_period'
            ]);

            return Inertia::render('Training/Show', [
                'training' => $training,
                'success' => session('success'),
                'error' => session('error'),
                'message' => session('message'),
            ]);

        } catch (\Exception $e) {
            Log::error('Training Show Error', [
                'training_id' => $training->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()->route('training.index')
                ->with('error', 'Error loading training record: ' . $e->getMessage());
        }
    }

    /**
     * Show the form for editing the specified training record
     */
    public function edit(TrainingRecord $training)
    {
        try {
            $training->load([
                'employee:id,nip,nama_lengkap,unit_organisasi,jabatan',
                'trainingType:id,name,category,validity_period'
            ]);

            $employees = Employee::select('id', 'nip', 'nama_lengkap', 'unit_organisasi', 'jabatan')
                                ->where('status_kerja', 'Aktif')
                                ->orderBy('nama_lengkap')
                                ->get();

            $trainingTypes = TrainingType::select('id', 'name', 'category', 'validity_period')
                                       ->where('is_active', true)
                                       ->orderBy('category')
                                       ->orderBy('name')
                                       ->get();

            return Inertia::render('Training/Edit', [
                'training' => $training,
                'employees' => $employees,
                'trainingTypes' => $trainingTypes,
                'success' => session('success'),
                'error' => session('error'),
                'message' => session('message'),
            ]);

        } catch (\Exception $e) {
            Log::error('Training Edit Error', [
                'training_id' => $training->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()->route('training.index')
                ->with('error', 'Error loading edit form: ' . $e->getMessage());
        }
    }

    /**
     * Update the specified training record
     */
    public function update(Request $request, TrainingRecord $training)
    {
        try {
            // Validation rules
            $validated = $request->validate([
                'employee_id' => 'required|exists:employees,id',
                'training_type_id' => 'required|exists:training_types,id',
                'certificate_number' => 'required|string|max:255|unique:training_records,certificate_number,' . $training->id,
                'training_provider' => 'required|string|max:255',
                'issue_date' => 'required|date',
                'expiry_date' => 'required|date|after:issue_date',
                'validity_period' => 'nullable|integer|min:1|max:120',
                'training_location' => 'nullable|string|max:255',
                'training_duration' => 'nullable|string|max:100',
                'instructor_name' => 'nullable|string|max:255',
                'completion_status' => 'required|in:completed,in_progress,failed,cancelled',
                'training_cost' => 'nullable|numeric|min:0',
                'internal_external' => 'required|in:internal,external',
                'batch_id' => 'nullable|string|max:100',
                'notes' => 'nullable|string',
                'compliance_requirements' => 'nullable|string',
                'renewal_required' => 'boolean',
                'notification_before_expiry' => 'nullable|integer|min:1|max:365',
                'certificate_file' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240',
                'supporting_documents.*' => 'nullable|file|mimes:pdf,jpg,jpeg,png,doc,docx|max:5120',
            ]);

            DB::beginTransaction();

            // Handle certificate file upload
            $certificateFilePath = $training->certificate_file;
            if ($request->hasFile('certificate_file')) {
                // Delete old file
                if ($certificateFilePath) {
                    Storage::disk('public')->delete($certificateFilePath);
                }
                $certificateFilePath = $request->file('certificate_file')->store('certificates', 'public');
            }

            // Handle supporting documents
            $supportingDocuments = $training->supporting_documents ? json_decode($training->supporting_documents, true) : [];
            if ($request->hasFile('supporting_documents')) {
                // Keep existing documents and add new ones
                foreach ($request->file('supporting_documents') as $file) {
                    $path = $file->store('supporting_documents', 'public');
                    $supportingDocuments[] = [
                        'filename' => $file->getClientOriginalName(),
                        'path' => $path,
                    ];
                }
            }

            // Update training record
            $training->update([
                'employee_id' => $validated['employee_id'],
                'training_type_id' => $validated['training_type_id'],
                'certificate_number' => $validated['certificate_number'],
                'training_provider' => $validated['training_provider'],
                'issue_date' => $validated['issue_date'],
                'expiry_date' => $validated['expiry_date'],
                'validity_period' => $validated['validity_period'],
                'training_location' => $validated['training_location'],
                'training_duration' => $validated['training_duration'],
                'instructor_name' => $validated['instructor_name'],
                'completion_status' => $validated['completion_status'],
                'training_cost' => $validated['training_cost'],
                'internal_external' => $validated['internal_external'],
                'batch_id' => $validated['batch_id'],
                'notes' => $validated['notes'],
                'compliance_requirements' => $validated['compliance_requirements'],
                'renewal_required' => $validated['renewal_required'] ?? false,
                'notification_before_expiry' => $validated['notification_before_expiry'] ?? 30,
                'certificate_file' => $certificateFilePath,
                'supporting_documents' => !empty($supportingDocuments) ? json_encode($supportingDocuments) : null,
                'updated_by' => auth()->user()->name,
            ]);

            DB::commit();

            return redirect()->route('training.index')
                ->with('success', 'Training record updated successfully!');

        } catch (ValidationException $e) {
            DB::rollBack();
            return redirect()->back()
                ->withErrors($e->errors())
                ->withInput();

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Training Update Error', [
                'training_id' => $training->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all()
            ]);

            return redirect()->back()
                ->with('error', 'Error updating training record: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Remove the specified training record
     */
    public function destroy(TrainingRecord $training)
    {
        try {
            DB::beginTransaction();

            // Delete associated files
            if ($training->certificate_file) {
                Storage::disk('public')->delete($training->certificate_file);
            }

            if ($training->supporting_documents) {
                $documents = json_decode($training->supporting_documents, true);
                foreach ($documents as $document) {
                    Storage::disk('public')->delete($document['path']);
                }
            }

            // Delete the training record
            $training->delete();

            DB::commit();

            return redirect()->route('training.index')
                ->with('success', 'Training record deleted successfully!');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Training Delete Error', [
                'training_id' => $training->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()->route('training.index')
                ->with('error', 'Error deleting training record: ' . $e->getMessage());
        }
    }

    /**
     * Calculate training statistics
     */
    private function calculateTrainingStatistics()
    {
        $today = Carbon::today();

        return [
            'total' => TrainingRecord::count(),
            'valid' => TrainingRecord::where('expiry_date', '>', $today)->count(),
            'expired' => TrainingRecord::where('expiry_date', '<=', $today)->count(),
            'dueSoon' => TrainingRecord::whereBetween('expiry_date', [$today, $today->copy()->addDays(30)])->count(),
            'uniqueTrainingTypes' => TrainingRecord::distinct('training_type_id')->count(),
            'uniqueEmployees' => TrainingRecord::distinct('employee_id')->count(),
            'thisMonth' => TrainingRecord::whereMonth('created_at', $today->month)
                                       ->whereYear('created_at', $today->year)
                                       ->count(),
        ];
    }

    /**
     * Get filter options
     */
    private function getFilterOptions()
    {
        return [
            'trainingTypes' => TrainingType::select('id', 'name', 'category')
                                         ->where('is_active', true)
                                         ->orderBy('category')
                                         ->orderBy('name')
                                         ->get(),
            'departments' => Employee::distinct('unit_organisasi')
                                   ->whereNotNull('unit_organisasi')
                                   ->where('unit_organisasi', '!=', '')
                                   ->orderBy('unit_organisasi')
                                   ->pluck('unit_organisasi'),
            'employees' => Employee::select('id', 'nip', 'nama_lengkap')
                                 ->whereHas('trainingRecords')
                                 ->orderBy('nama_lengkap')
                                 ->get(),
        ];
    }

    /**
     * Download certificate file
     */
    public function downloadCertificate(TrainingRecord $training)
    {
        try {
            if (!$training->certificate_file) {
                return redirect()->back()->with('error', 'No certificate file found.');
            }

            $filePath = storage_path('app/public/' . $training->certificate_file);

            if (!file_exists($filePath)) {
                return redirect()->back()->with('error', 'Certificate file not found.');
            }

            $filename = 'Certificate_' . $training->certificate_number . '_' . $training->employee->nama_lengkap . '.pdf';

            return response()->download($filePath, $filename);

        } catch (\Exception $e) {
            Log::error('Certificate Download Error', [
                'training_id' => $training->id,
                'error' => $e->getMessage()
            ]);

            return redirect()->back()->with('error', 'Error downloading certificate.');
        }
    }
}
