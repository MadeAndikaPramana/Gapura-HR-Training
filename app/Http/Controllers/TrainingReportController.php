<?php

namespace App\Http\Controllers;

use App\Models\TrainingRecord;
use App\Models\TrainingType;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Carbon\Carbon;

class TrainingReportController extends Controller
{
    /**
     * Training reports overview page
     */
    public function index()
    {
        try {
            // Available report types
            $reportTypes = [
                [
                    'name' => 'Compliance Report',
                    'description' => 'Overall training compliance by department and training type',
                    'href' => route('training.reports.compliance'),
                    'icon' => 'CheckCircle',
                    'color' => 'bg-green-500'
                ],
                [
                    'name' => 'Expiry Report',
                    'description' => 'Certificates expiring in the next 90 days',
                    'href' => route('training.reports.expiry'),
                    'icon' => 'AlertTriangle',
                    'color' => 'bg-yellow-500'
                ],
                [
                    'name' => 'Employee Reports',
                    'description' => 'Individual employee training records and history',
                    'href' => '#',
                    'icon' => 'User',
                    'color' => 'bg-blue-500'
                ],
                [
                    'name' => 'Department Reports',
                    'description' => 'Department-wise training statistics and compliance',
                    'href' => '#',
                    'icon' => 'Building2',
                    'color' => 'bg-purple-500'
                ]
            ];

            // Quick stats
            $stats = [
                'total_reports_available' => 4,
                'compliance_rate' => $this->calculateOverallCompliance(),
                'expiring_this_month' => TrainingRecord::whereBetween('expiry_date', [
                    Carbon::now()->startOfMonth(),
                    Carbon::now()->endOfMonth()
                ])->count(),
                'reports_generated_today' => 0, // Will be implemented with actual report tracking
            ];

            return Inertia::render('Training/Reports/Index', [
                'reportTypes' => $reportTypes,
                'stats' => $stats,
                'title' => 'Training Reports',
                'subtitle' => 'Generate comprehensive training and compliance reports'
            ]);

        } catch (\Exception $e) {
            Log::error('Training Reports Index Error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return Inertia::render('Training/Reports/Index', [
                'reportTypes' => [],
                'stats' => [],
                'error' => 'Error loading reports page: ' . $e->getMessage(),
                'title' => 'Training Reports',
                'subtitle' => 'Generate comprehensive training and compliance reports'
            ]);
        }
    }

    /**
     * Compliance report page
     */
    public function compliance(Request $request)
    {
        try {
            $department = $request->get('department', 'all');
            $trainingType = $request->get('training_type', 'all');
            $dateRange = $request->get('date_range', '30');

            // Get compliance data
            $complianceData = $this->getComplianceData($department, $trainingType);

            // Get department options
            $departments = Employee::distinct('unit_organisasi')
                                 ->whereNotNull('unit_organisasi')
                                 ->where('unit_organisasi', '!=', '')
                                 ->orderBy('unit_organisasi')
                                 ->pluck('unit_organisasi');

            // Get training type options
            $trainingTypes = TrainingType::where('is_active', true)
                                       ->select('id', 'name', 'category')
                                       ->orderBy('category')
                                       ->orderBy('name')
                                       ->get();

            return Inertia::render('Training/Reports/Compliance', [
                'complianceData' => $complianceData,
                'filters' => [
                    'department' => $department,
                    'training_type' => $trainingType,
                    'date_range' => $dateRange,
                ],
                'filterOptions' => [
                    'departments' => $departments,
                    'trainingTypes' => $trainingTypes,
                ],
                'title' => 'Compliance Report',
                'subtitle' => 'Training compliance status across departments and training types'
            ]);

        } catch (\Exception $e) {
            Log::error('Compliance Report Error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return Inertia::render('Training/Reports/Compliance', [
                'complianceData' => [],
                'filters' => [],
                'filterOptions' => [],
                'error' => 'Error loading compliance report: ' . $e->getMessage(),
                'title' => 'Compliance Report',
                'subtitle' => 'Training compliance status across departments and training types'
            ]);
        }
    }

    /**
     * Expiry report page
     */
    public function expiry(Request $request)
    {
        try {
            $days = $request->get('days', 90);
            $department = $request->get('department', 'all');
            $trainingType = $request->get('training_type', 'all');

            $today = Carbon::today();
            $endDate = $today->copy()->addDays($days);

            // Build query
            $query = TrainingRecord::with(['employee:id,nip,nama_lengkap,unit_organisasi', 'trainingType:id,name,category'])
                                 ->whereBetween('expiry_date', [$today, $endDate]);

            // Apply filters
            if ($department && $department !== 'all') {
                $query->whereHas('employee', function($q) use ($department) {
                    $q->where('unit_organisasi', $department);
                });
            }

            if ($trainingType && $trainingType !== 'all') {
                $query->where('training_type_id', $trainingType);
            }

            $expiringRecords = $query->orderBy('expiry_date')->get();

            // Group by expiry periods
            $groupedRecords = [
                'expired' => $expiringRecords->filter(function($record) use ($today) {
                    return Carbon::parse($record->expiry_date)->lt($today);
                }),
                'this_week' => $expiringRecords->filter(function($record) use ($today) {
                    $expiry = Carbon::parse($record->expiry_date);
                    return $expiry->gte($today) && $expiry->lte($today->copy()->addDays(7));
                }),
                'this_month' => $expiringRecords->filter(function($record) use ($today) {
                    $expiry = Carbon::parse($record->expiry_date);
                    return $expiry->gt($today->copy()->addDays(7)) && $expiry->lte($today->copy()->addDays(30));
                }),
                'next_60_days' => $expiringRecords->filter(function($record) use ($today) {
                    $expiry = Carbon::parse($record->expiry_date);
                    return $expiry->gt($today->copy()->addDays(30)) && $expiry->lte($today->copy()->addDays(60));
                }),
                'beyond_60_days' => $expiringRecords->filter(function($record) use ($today) {
                    $expiry = Carbon::parse($record->expiry_date);
                    return $expiry->gt($today->copy()->addDays(60));
                }),
            ];

            // Get filter options
            $departments = Employee::distinct('unit_organisasi')
                                 ->whereNotNull('unit_organisasi')
                                 ->where('unit_organisasi', '!=', '')
                                 ->orderBy('unit_organisasi')
                                 ->pluck('unit_organisasi');

            $trainingTypes = TrainingType::where('is_active', true)
                                       ->select('id', 'name', 'category')
                                       ->orderBy('category')
                                       ->orderBy('name')
                                       ->get();

            return Inertia::render('Training/Reports/Expiry', [
                'expiringRecords' => $groupedRecords,
                'totalCount' => $expiringRecords->count(),
                'filters' => [
                    'days' => $days,
                    'department' => $department,
                    'training_type' => $trainingType,
                ],
                'filterOptions' => [
                    'departments' => $departments,
                    'trainingTypes' => $trainingTypes,
                ],
                'title' => 'Expiry Report',
                'subtitle' => "Certificates expiring in the next {$days} days"
            ]);

        } catch (\Exception $e) {
            Log::error('Expiry Report Error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return Inertia::render('Training/Reports/Expiry', [
                'expiringRecords' => [],
                'totalCount' => 0,
                'filters' => [],
                'filterOptions' => [],
                'error' => 'Error loading expiry report: ' . $e->getMessage(),
                'title' => 'Expiry Report',
                'subtitle' => 'Certificates expiring soon'
            ]);
        }
    }

    /**
     * Individual employee training report
     */
    public function employee(Request $request, Employee $employee)
    {
        try {
            // Get employee with all training records
            $employee->load([
                'trainingRecords.trainingType:id,name,category,validity_period',
                'trainingRecords' => function($query) {
                    $query->orderBy('expiry_date', 'desc');
                }
            ]);

            // Calculate employee statistics
            $stats = [
                'total_trainings' => $employee->trainingRecords->count(),
                'valid_trainings' => $employee->trainingRecords->where('expiry_date', '>', Carbon::today())->count(),
                'expired_trainings' => $employee->trainingRecords->where('expiry_date', '<=', Carbon::today())->count(),
                'expiring_soon' => $employee->trainingRecords->filter(function($record) {
                    $expiry = Carbon::parse($record->expiry_date);
                    return $expiry->gt(Carbon::today()) && $expiry->lte(Carbon::today()->addDays(30));
                })->count(),
                'compliance_rate' => $employee->trainingRecords->count() > 0
                    ? round(($employee->trainingRecords->where('expiry_date', '>', Carbon::today())->count() / $employee->trainingRecords->count()) * 100, 2)
                    : 0,
            ];

            // Get mandatory trainings status
            $mandatoryTypes = TrainingType::where('is_mandatory', true)->where('is_active', true)->get();
            $mandatoryStatus = [];

            foreach ($mandatoryTypes as $type) {
                $latestRecord = $employee->trainingRecords->where('training_type_id', $type->id)->first();
                $mandatoryStatus[] = [
                    'training_type' => $type->name,
                    'required' => true,
                    'completed' => $latestRecord ? true : false,
                    'status' => $latestRecord
                        ? (Carbon::parse($latestRecord->expiry_date)->gt(Carbon::today()) ? 'valid' : 'expired')
                        : 'missing',
                    'expiry_date' => $latestRecord ? $latestRecord->expiry_date : null,
                    'certificate_number' => $latestRecord ? $latestRecord->certificate_number : null,
                ];
            }

            return Inertia::render('Training/Reports/Employee', [
                'employee' => $employee,
                'stats' => $stats,
                'mandatoryStatus' => $mandatoryStatus,
                'title' => "Training Report - {$employee->nama_lengkap}",
                'subtitle' => "Comprehensive training record for {$employee->nip}"
            ]);

        } catch (\Exception $e) {
            Log::error('Employee Report Error', [
                'employee_id' => $employee->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()->route('training.reports.index')
                ->with('error', 'Error loading employee report: ' . $e->getMessage());
        }
    }

    /**
     * Department training report
     */
    public function department(Request $request, $department)
    {
        try {
            // Get department employees
            $employees = Employee::where('unit_organisasi', $department)
                               ->with([
                                   'trainingRecords.trainingType:id,name,category',
                                   'trainingRecords' => function($query) {
                                       $query->orderBy('expiry_date', 'desc');
                                   }
                               ])
                               ->get();

            // Calculate department statistics
            $totalTrainings = $employees->sum(function($employee) {
                return $employee->trainingRecords->count();
            });

            $validTrainings = $employees->sum(function($employee) {
                return $employee->trainingRecords->where('expiry_date', '>', Carbon::today())->count();
            });

            $expiredTrainings = $employees->sum(function($employee) {
                return $employee->trainingRecords->where('expiry_date', '<=', Carbon::today())->count();
            });

            $stats = [
                'total_employees' => $employees->count(),
                'total_trainings' => $totalTrainings,
                'valid_trainings' => $validTrainings,
                'expired_trainings' => $expiredTrainings,
                'compliance_rate' => $totalTrainings > 0 ? round(($validTrainings / $totalTrainings) * 100, 2) : 0,
                'average_trainings_per_employee' => $employees->count() > 0 ? round($totalTrainings / $employees->count(), 2) : 0,
            ];

            // Training type breakdown
            $trainingBreakdown = [];
            $allTrainingTypes = TrainingType::where('is_active', true)->get();

            foreach ($allTrainingTypes as $type) {
                $typeRecords = $employees->flatMap->trainingRecords->where('training_type_id', $type->id);
                $validCount = $typeRecords->where('expiry_date', '>', Carbon::today())->count();
                $totalCount = $typeRecords->count();

                if ($totalCount > 0) {
                    $trainingBreakdown[] = [
                        'training_type' => $type->name,
                        'category' => $type->category,
                        'total' => $totalCount,
                        'valid' => $validCount,
                        'expired' => $totalCount - $validCount,
                        'compliance_rate' => round(($validCount / $totalCount) * 100, 2),
                    ];
                }
            }

            // Sort by compliance rate (lowest first for attention)
            $trainingBreakdown = collect($trainingBreakdown)->sortBy('compliance_rate')->values()->all();

            return Inertia::render('Training/Reports/Department', [
                'department' => $department,
                'employees' => $employees,
                'stats' => $stats,
                'trainingBreakdown' => $trainingBreakdown,
                'title' => "Department Report - {$department}",
                'subtitle' => "Training compliance and statistics for {$department} department"
            ]);

        } catch (\Exception $e) {
            Log::error('Department Report Error', [
                'department' => $department,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()->route('training.reports.index')
                ->with('error', 'Error loading department report: ' . $e->getMessage());
        }
    }

    /**
     * Export compliance report
     */
    public function exportCompliance(Request $request)
    {
        try {
            // This would integrate with Laravel Excel or similar package
            // For now, return JSON data that can be processed by frontend

            $department = $request->get('department', 'all');
            $trainingType = $request->get('training_type', 'all');

            $complianceData = $this->getComplianceData($department, $trainingType);

            return response()->json([
                'success' => true,
                'data' => $complianceData,
                'filename' => 'compliance_report_' . Carbon::now()->format('Y_m_d_H_i_s') . '.json'
            ]);

        } catch (\Exception $e) {
            Log::error('Export Compliance Error', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Error exporting compliance report: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Export expiry report
     */
    public function exportExpiry(Request $request)
    {
        try {
            $days = $request->get('days', 90);
            $department = $request->get('department', 'all');
            $trainingType = $request->get('training_type', 'all');

            $today = Carbon::today();
            $endDate = $today->copy()->addDays($days);

            $query = TrainingRecord::with(['employee:id,nip,nama_lengkap,unit_organisasi', 'trainingType:id,name,category'])
                                 ->whereBetween('expiry_date', [$today, $endDate]);

            if ($department && $department !== 'all') {
                $query->whereHas('employee', function($q) use ($department) {
                    $q->where('unit_organisasi', $department);
                });
            }

            if ($trainingType && $trainingType !== 'all') {
                $query->where('training_type_id', $trainingType);
            }

            $expiringRecords = $query->orderBy('expiry_date')->get();

            return response()->json([
                'success' => true,
                'data' => $expiringRecords,
                'filename' => 'expiry_report_' . Carbon::now()->format('Y_m_d_H_i_s') . '.json'
            ]);

        } catch (\Exception $e) {
            Log::error('Export Expiry Error', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Error exporting expiry report: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Export employee report
     */
    public function exportEmployee(Employee $employee)
    {
        try {
            $employee->load([
                'trainingRecords.trainingType:id,name,category,validity_period'
            ]);

            return response()->json([
                'success' => true,
                'data' => $employee,
                'filename' => 'employee_report_' . $employee->nip . '_' . Carbon::now()->format('Y_m_d_H_i_s') . '.json'
            ]);

        } catch (\Exception $e) {
            Log::error('Export Employee Error', [
                'employee_id' => $employee->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Error exporting employee report: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Export department report
     */
    public function exportDepartment($department)
    {
        try {
            $employees = Employee::where('unit_organisasi', $department)
                               ->with([
                                   'trainingRecords.trainingType:id,name,category'
                               ])
                               ->get();

            return response()->json([
                'success' => true,
                'data' => $employees,
                'filename' => 'department_report_' . str_replace(' ', '_', $department) . '_' . Carbon::now()->format('Y_m_d_H_i_s') . '.json'
            ]);

        } catch (\Exception $e) {
            Log::error('Export Department Error', [
                'department' => $department,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Error exporting department report: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get compliance data with filters
     */
    private function getComplianceData($department = 'all', $trainingType = 'all')
    {
        try {
            $today = Carbon::today();

            if ($department !== 'all' && $trainingType !== 'all') {
                // Specific department and training type
                $employees = Employee::where('unit_organisasi', $department)->get();
                $records = TrainingRecord::where('training_type_id', $trainingType)
                                       ->whereIn('employee_id', $employees->pluck('id'))
                                       ->with(['employee:id,nip,nama_lengkap', 'trainingType:id,name'])
                                       ->get();

                return [
                    'department' => $department,
                    'training_type' => TrainingType::find($trainingType)->name ?? 'Unknown',
                    'total_employees' => $employees->count(),
                    'total_records' => $records->count(),
                    'valid_records' => $records->where('expiry_date', '>', $today)->count(),
                    'expired_records' => $records->where('expiry_date', '<=', $today)->count(),
                    'records' => $records,
                ];
            } else {
                // Department-wise summary
                $departments = Employee::distinct('unit_organisasi')
                                     ->whereNotNull('unit_organisasi')
                                     ->where('unit_organisasi', '!=', '')
                                     ->pluck('unit_organisasi');

                $summary = [];
                foreach ($departments as $dept) {
                    if ($department !== 'all' && $dept !== $department) continue;

                    $deptEmployees = Employee::where('unit_organisasi', $dept)->pluck('id');
                    $query = TrainingRecord::whereIn('employee_id', $deptEmployees);

                    if ($trainingType !== 'all') {
                        $query->where('training_type_id', $trainingType);
                    }

                    $deptRecords = $query->get();

                    $summary[] = [
                        'department' => $dept,
                        'total_employees' => $deptEmployees->count(),
                        'total_records' => $deptRecords->count(),
                        'valid_records' => $deptRecords->where('expiry_date', '>', $today)->count(),
                        'expired_records' => $deptRecords->where('expiry_date', '<=', $today)->count(),
                        'compliance_rate' => $deptRecords->count() > 0
                            ? round(($deptRecords->where('expiry_date', '>', $today)->count() / $deptRecords->count()) * 100, 2)
                            : 0,
                    ];
                }

                return $summary;
            }

        } catch (\Exception $e) {
            Log::error('Get Compliance Data Error', [
                'error' => $e->getMessage(),
                'department' => $department,
                'training_type' => $trainingType
            ]);

            return [];
        }
    }

    /**
     * Calculate overall compliance rate
     */
    private function calculateOverallCompliance()
    {
        try {
            $totalRecords = TrainingRecord::count();
            $validRecords = TrainingRecord::where('expiry_date', '>', Carbon::today())->count();

            return $totalRecords > 0 ? round(($validRecords / $totalRecords) * 100, 2) : 0;

        } catch (\Exception $e) {
            Log::error('Calculate Overall Compliance Error', [
                'error' => $e->getMessage()
            ]);

            return 0;
        }
    }
}
