<?php

namespace App\Http\Controllers;

use App\Models\TrainingRecord;
use App\Models\TrainingType;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Carbon\Carbon;

class TrainingDashboardController extends Controller
{
    /**
     * Training dashboard with comprehensive overview
     */
    public function index()
    {
        try {
            $today = Carbon::today();

            // Calculate key statistics
            $stats = [
                'total_records' => TrainingRecord::count(),
                'valid_certificates' => TrainingRecord::where('expiry_date', '>', $today)->count(),
                'expired_certificates' => TrainingRecord::where('expiry_date', '<=', $today)->count(),
                'due_soon' => TrainingRecord::whereBetween('expiry_date', [$today, $today->copy()->addDays(30)])->count(),
                'total_employees' => Employee::whereHas('trainingRecords')->count(),
                'total_training_types' => TrainingType::where('is_active', true)->count(),
                'completion_rate' => $this->calculateCompletionRate(),
                'compliance_rate' => $this->calculateComplianceRate(),
            ];

            // Recent activities
            $recentTrainings = TrainingRecord::with(['employee:id,nama_lengkap,nip', 'trainingType:id,name'])
                                           ->latest()
                                           ->limit(10)
                                           ->get();

            // Expiring certificates
            $expiringCertificates = TrainingRecord::with(['employee:id,nama_lengkap,nip', 'trainingType:id,name'])
                                                ->whereBetween('expiry_date', [$today, $today->copy()->addDays(30)])
                                                ->orderBy('expiry_date')
                                                ->limit(10)
                                                ->get();

            // Training type statistics
            $trainingTypeStats = TrainingType::withCount([
                'trainingRecords as total_records',
                'trainingRecords as valid_records' => function($query) use ($today) {
                    $query->where('expiry_date', '>', $today);
                },
                'trainingRecords as expired_records' => function($query) use ($today) {
                    $query->where('expiry_date', '<=', $today);
                }
            ])->where('is_active', true)->get();

            // Department compliance overview
            $departmentStats = $this->getDepartmentComplianceStats();

            // Quick actions data
            $quickActions = [
                [
                    'name' => 'Add Training Record',
                    'description' => 'Register new employee training',
                    'href' => route('training.create'),
                    'icon' => 'Plus',
                    'color' => 'bg-green-500'
                ],
                [
                    'name' => 'View Expiring Certificates',
                    'description' => 'Check certificates due for renewal',
                    'href' => route('certificates.expiring'),
                    'icon' => 'AlertTriangle',
                    'color' => 'bg-yellow-500'
                ],
                [
                    'name' => 'Training Analytics',
                    'description' => 'View detailed training reports',
                    'href' => route('training.analytics'),
                    'icon' => 'TrendingUp',
                    'color' => 'bg-blue-500'
                ],
                [
                    'name' => 'Compliance Report',
                    'description' => 'Generate compliance reports',
                    'href' => route('training.reports.compliance'),
                    'icon' => 'FileCheck',
                    'color' => 'bg-purple-500'
                ]
            ];

            return Inertia::render('Training/Dashboard', [
                'stats' => $stats,
                'recentTrainings' => $recentTrainings,
                'expiringCertificates' => $expiringCertificates,
                'trainingTypeStats' => $trainingTypeStats,
                'departmentStats' => $departmentStats,
                'quickActions' => $quickActions,
                'title' => 'Training Dashboard',
                'subtitle' => 'Overview of training records and compliance status'
            ]);

        } catch (\Exception $e) {
            Log::error('Training Dashboard Error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return Inertia::render('Training/Dashboard', [
                'stats' => [],
                'recentTrainings' => [],
                'expiringCertificates' => [],
                'trainingTypeStats' => [],
                'departmentStats' => [],
                'quickActions' => [],
                'error' => 'Error loading dashboard data: ' . $e->getMessage(),
                'title' => 'Training Dashboard',
                'subtitle' => 'Overview of training records and compliance status'
            ]);
        }
    }

    /**
     * Training analytics page with detailed charts and reports
     */
    public function analytics()
    {
        try {
            // Monthly training trends (last 12 months)
            $monthlyTrends = $this->getMonthlyTrainingTrends();

            // Training completion by category
            $categoryStats = $this->getTrainingCategoryStats();

            // Department comparison
            $departmentComparison = $this->getDepartmentComparisonData();

            // Training cost analysis
            $costAnalysis = $this->getTrainingCostAnalysis();

            return Inertia::render('Training/Analytics', [
                'monthlyTrends' => $monthlyTrends,
                'categoryStats' => $categoryStats,
                'departmentComparison' => $departmentComparison,
                'costAnalysis' => $costAnalysis,
                'title' => 'Training Analytics',
                'subtitle' => 'Detailed training statistics and trends'
            ]);

        } catch (\Exception $e) {
            Log::error('Training Analytics Error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return Inertia::render('Training/Analytics', [
                'monthlyTrends' => [],
                'categoryStats' => [],
                'departmentComparison' => [],
                'costAnalysis' => [],
                'error' => 'Error loading analytics data: ' . $e->getMessage(),
                'title' => 'Training Analytics',
                'subtitle' => 'Detailed training statistics and trends'
            ]);
        }
    }

    /**
     * Training notifications overview
     */
    public function notifications()
    {
        try {
            $today = Carbon::today();

            // Certificates expiring in different time frames
            $notifications = [
                'expiring_this_week' => TrainingRecord::with(['employee:id,nama_lengkap,nip', 'trainingType:id,name'])
                                                    ->whereBetween('expiry_date', [$today, $today->copy()->addDays(7)])
                                                    ->orderBy('expiry_date')
                                                    ->get(),

                'expiring_this_month' => TrainingRecord::with(['employee:id,nama_lengkap,nip', 'trainingType:id,name'])
                                                     ->whereBetween('expiry_date', [$today->copy()->addDays(8), $today->copy()->addDays(30)])
                                                     ->orderBy('expiry_date')
                                                     ->get(),

                'expired_recently' => TrainingRecord::with(['employee:id,nama_lengkap,nip', 'trainingType:id,name'])
                                                  ->whereBetween('expiry_date', [$today->copy()->subDays(30), $today])
                                                  ->orderBy('expiry_date', 'desc')
                                                  ->get(),

                'missing_mandatory' => $this->getMissingMandatoryTrainings(),
            ];

            return Inertia::render('Training/Notifications', [
                'notifications' => $notifications,
                'title' => 'Training Notifications',
                'subtitle' => 'Certificate expiry alerts and compliance notifications'
            ]);

        } catch (\Exception $e) {
            Log::error('Training Notifications Error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return Inertia::render('Training/Notifications', [
                'notifications' => [],
                'error' => 'Error loading notifications: ' . $e->getMessage(),
                'title' => 'Training Notifications',
                'subtitle' => 'Certificate expiry alerts and compliance notifications'
            ]);
        }
    }

    /**
     * API endpoint for compliance statistics
     */
    public function complianceStats()
    {
        try {
            $trainingTypes = TrainingType::where('is_active', true)->get();
            $stats = [];

            foreach ($trainingTypes as $type) {
                $totalRecords = $type->trainingRecords()->count();
                $validRecords = $type->trainingRecords()->where('expiry_date', '>', Carbon::today())->count();
                $expiredRecords = $type->trainingRecords()->where('expiry_date', '<=', Carbon::today())->count();

                $stats[] = [
                    'name' => $type->name,
                    'category' => $type->category,
                    'total' => $totalRecords,
                    'valid' => $validRecords,
                    'expired' => $expiredRecords,
                    'compliance_rate' => $totalRecords > 0 ? round(($validRecords / $totalRecords) * 100, 2) : 0,
                ];
            }

            return response()->json($stats);

        } catch (\Exception $e) {
            Log::error('Compliance Stats API Error', [
                'error' => $e->getMessage()
            ]);

            return response()->json(['error' => 'Error loading compliance stats'], 500);
        }
    }

    /**
     * API endpoint for expiry trends
     */
    public function expiryTrends()
    {
        try {
            $months = [];
            $now = Carbon::now();

            for ($i = 11; $i >= 0; $i--) {
                $month = $now->copy()->subMonths($i);
                $expiringCount = TrainingRecord::whereMonth('expiry_date', $month->month)
                                             ->whereYear('expiry_date', $month->year)
                                             ->count();

                $months[] = [
                    'month' => $month->format('M Y'),
                    'expiring' => $expiringCount,
                ];
            }

            return response()->json($months);

        } catch (\Exception $e) {
            Log::error('Expiry Trends API Error', [
                'error' => $e->getMessage()
            ]);

            return response()->json(['error' => 'Error loading expiry trends'], 500);
        }
    }

    /**
     * API endpoint for training distribution
     */
    public function trainingDistribution()
    {
        try {
            $distribution = TrainingType::withCount('trainingRecords')
                                      ->where('is_active', true)
                                      ->get()
                                      ->map(function ($type) {
                                          return [
                                              'name' => $type->name,
                                              'category' => $type->category,
                                              'count' => $type->training_records_count,
                                          ];
                                      });

            return response()->json($distribution);

        } catch (\Exception $e) {
            Log::error('Training Distribution API Error', [
                'error' => $e->getMessage()
            ]);

            return response()->json(['error' => 'Error loading training distribution'], 500);
        }
    }

    /**
     * Calculate overall completion rate
     */
    private function calculateCompletionRate()
    {
        $totalRecords = TrainingRecord::count();
        $completedRecords = TrainingRecord::where('completion_status', 'completed')->count();

        return $totalRecords > 0 ? round(($completedRecords / $totalRecords) * 100, 2) : 0;
    }

    /**
     * Calculate compliance rate (valid certificates vs total requirements)
     */
    private function calculateComplianceRate()
    {
        $mandatoryTrainingTypes = TrainingType::where('is_mandatory', true)->where('is_active', true)->count();
        $activeEmployees = Employee::where('status_kerja', 'Aktif')->count();
        $totalRequiredCertificates = $mandatoryTrainingTypes * $activeEmployees;

        if ($totalRequiredCertificates === 0) {
            return 100;
        }

        $validMandatoryCertificates = TrainingRecord::where('expiry_date', '>', Carbon::today())
                                                  ->whereHas('trainingType', function($query) {
                                                      $query->where('is_mandatory', true);
                                                  })
                                                  ->count();

        return round(($validMandatoryCertificates / $totalRequiredCertificates) * 100, 2);
    }

    /**
     * Get department compliance statistics
     */
    private function getDepartmentComplianceStats()
    {
        try {
            $departments = Employee::distinct('unit_organisasi')
                                 ->whereNotNull('unit_organisasi')
                                 ->where('unit_organisasi', '!=', '')
                                 ->pluck('unit_organisasi');

            $stats = [];
            foreach ($departments as $department) {
                $employeeCount = Employee::where('unit_organisasi', $department)->count();
                $trainingCount = TrainingRecord::whereHas('employee', function($query) use ($department) {
                    $query->where('unit_organisasi', $department);
                })->count();

                $validTrainingCount = TrainingRecord::where('expiry_date', '>', Carbon::today())
                                                  ->whereHas('employee', function($query) use ($department) {
                                                      $query->where('unit_organisasi', $department);
                                                  })->count();

                $stats[] = [
                    'department' => $department,
                    'employees' => $employeeCount,
                    'total_trainings' => $trainingCount,
                    'valid_trainings' => $validTrainingCount,
                    'compliance_rate' => $trainingCount > 0 ? round(($validTrainingCount / $trainingCount) * 100, 2) : 0,
                ];
            }

            return collect($stats)->sortByDesc('compliance_rate')->values()->all();

        } catch (\Exception $e) {
            Log::error('Department Stats Error', ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Get monthly training trends for the last 12 months
     */
    private function getMonthlyTrainingTrends()
    {
        try {
            $trends = [];
            $now = Carbon::now();

            for ($i = 11; $i >= 0; $i--) {
                $month = $now->copy()->subMonths($i);

                $trends[] = [
                    'month' => $month->format('M Y'),
                    'new_trainings' => TrainingRecord::whereMonth('created_at', $month->month)
                                                   ->whereYear('created_at', $month->year)
                                                   ->count(),
                    'expiring' => TrainingRecord::whereMonth('expiry_date', $month->month)
                                              ->whereYear('expiry_date', $month->year)
                                              ->count(),
                ];
            }

            return $trends;

        } catch (\Exception $e) {
            Log::error('Monthly Trends Error', ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Get training statistics by category
     */
    private function getTrainingCategoryStats()
    {
        try {
            return TrainingType::select('category')
                             ->withCount([
                                 'trainingRecords as total',
                                 'trainingRecords as valid' => function($query) {
                                     $query->where('expiry_date', '>', Carbon::today());
                                 }
                             ])
                             ->where('is_active', true)
                             ->groupBy('category')
                             ->get()
                             ->map(function($item) {
                                 return [
                                     'category' => $item->category,
                                     'total' => $item->total,
                                     'valid' => $item->valid,
                                     'compliance_rate' => $item->total > 0 ? round(($item->valid / $item->total) * 100, 2) : 0,
                                 ];
                             });

        } catch (\Exception $e) {
            Log::error('Category Stats Error', ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Get department comparison data
     */
    private function getDepartmentComparisonData()
    {
        return $this->getDepartmentComplianceStats();
    }

    /**
     * Get training cost analysis
     */
    private function getTrainingCostAnalysis()
    {
        try {
            return [
                'total_cost' => TrainingRecord::sum('training_cost') ?? 0,
                'average_cost' => TrainingRecord::avg('training_cost') ?? 0,
                'cost_by_category' => TrainingRecord::join('training_types', 'training_records.training_type_id', '=', 'training_types.id')
                                                  ->select('training_types.category')
                                                  ->selectRaw('SUM(training_records.training_cost) as total_cost')
                                                  ->selectRaw('COUNT(*) as training_count')
                                                  ->groupBy('training_types.category')
                                                  ->get(),
                'monthly_spending' => TrainingRecord::selectRaw('YEAR(created_at) as year, MONTH(created_at) as month, SUM(training_cost) as total')
                                                  ->whereYear('created_at', Carbon::now()->year)
                                                  ->groupBy('year', 'month')
                                                  ->orderBy('year')
                                                  ->orderBy('month')
                                                  ->get()
                                                  ->map(function($item) {
                                                      return [
                                                          'month' => Carbon::createFromDate($item->year, $item->month, 1)->format('M Y'),
                                                          'total' => $item->total ?? 0,
                                                      ];
                                                  }),
            ];

        } catch (\Exception $e) {
            Log::error('Cost Analysis Error', ['error' => $e->getMessage()]);
            return [
                'total_cost' => 0,
                'average_cost' => 0,
                'cost_by_category' => [],
                'monthly_spending' => [],
            ];
        }
    }

    /**
     * Get employees missing mandatory trainings
     */
    private function getMissingMandatoryTrainings()
    {
        try {
            $mandatoryTypes = TrainingType::where('is_mandatory', true)->where('is_active', true)->get();
            $missing = [];

            foreach ($mandatoryTypes as $type) {
                $employeesWithoutTraining = Employee::where('status_kerja', 'Aktif')
                                                  ->whereDoesntHave('trainingRecords', function($query) use ($type) {
                                                      $query->where('training_type_id', $type->id)
                                                           ->where('expiry_date', '>', Carbon::today());
                                                  })
                                                  ->select('id', 'nip', 'nama_lengkap', 'unit_organisasi')
                                                  ->get();

                if ($employeesWithoutTraining->isNotEmpty()) {
                    $missing[] = [
                        'training_type' => $type->name,
                        'employees' => $employeesWithoutTraining,
                        'count' => $employeesWithoutTraining->count(),
                    ];
                }
            }

            return $missing;

        } catch (\Exception $e) {
            Log::error('Missing Mandatory Trainings Error', ['error' => $e->getMessage()]);
            return [];
        }
    }
}
