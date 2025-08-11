<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Employee;
use App\Models\TrainingType;
use App\Models\TrainingRecord;
use App\Models\BackgroundCheck;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AnalyticsController extends Controller
{
    /**
     * Display analytics dashboard
     */
    public function index(Request $request)
    {
        $timeframe = $request->get('timeframe', '6months');
        $department = $request->get('department');

        return Inertia::render('Analytics/Index', [
            'overviewStats' => $this->getOverviewStats($department),
            'complianceAnalytics' => $this->getComplianceAnalytics($department),
            'trainingTrends' => $this->getTrainingTrends($timeframe, $department),
            'departmentBreakdown' => $this->getDepartmentBreakdown(),
            'expiryAnalytics' => $this->getExpiryAnalytics($department),
            'filters' => [
                'timeframe' => $timeframe,
                'department' => $department,
            ],
        ]);
    }

    /**
     * Get overview statistics
     */
    private function getOverviewStats($department = null)
    {
        $employeeQuery = Employee::query();
        $trainingQuery = TrainingRecord::query();

        if ($department) {
            $employeeQuery->where('unit_organisasi', $department);
            $trainingQuery->whereHas('employee', function($q) use ($department) {
                $q->where('unit_organisasi', $department);
            });
        }

        $totalEmployees = $employeeQuery->count();
        $activeEmployees = $employeeQuery->where('status_kerja', 'Aktif')->count();

        $totalTrainings = $trainingQuery->count();
        $activeTrainings = $trainingQuery->where('status', 'active')->count();
        $expiredTrainings = $trainingQuery->where('status', 'expired')->count();
        $expiringSoon = $trainingQuery->where('status', 'expiring_soon')->count();

        $validBackgroundChecks = BackgroundCheck::query()
            ->when($department, function($q) use ($department) {
                $q->whereHas('employee', function($subQ) use ($department) {
                    $subQ->where('unit_organisasi', $department);
                });
            })
            ->where('status', 'passed')
            ->count();

        // Calculate compliance rate
        $trainingTypes = TrainingType::active()->count();
        $compliantEmployees = 0;

        if ($trainingTypes > 0 && $totalEmployees > 0) {
            $compliantQuery = $employeeQuery->clone()
                ->whereHas('trainingRecords', function($query) use ($trainingTypes) {
                    $query->where('status', 'active');
                }, '>=', $trainingTypes);

            $compliantEmployees = $compliantQuery->count();
        }

        $complianceRate = $totalEmployees > 0 ? round(($compliantEmployees / $totalEmployees) * 100, 1) : 0;

        return [
            'totalEmployees' => $totalEmployees,
            'activeEmployees' => $activeEmployees,
            'totalTrainings' => $totalTrainings,
            'activeTrainings' => $activeTrainings,
            'expiredTrainings' => $expiredTrainings,
            'expiringSoon' => $expiringSoon,
            'validBackgroundChecks' => $validBackgroundChecks,
            'compliantEmployees' => $compliantEmployees,
            'complianceRate' => $complianceRate,
        ];
    }

    /**
     * Get compliance analytics
     */
    private function getComplianceAnalytics($department = null)
    {
        $trainingTypes = TrainingType::active()->get();
        $analytics = [];

        foreach ($trainingTypes as $trainingType) {
            $query = TrainingRecord::where('training_type_id', $trainingType->id);

            if ($department) {
                $query->whereHas('employee', function($q) use ($department) {
                    $q->where('unit_organisasi', $department);
                });
            }

            $total = $query->count();
            $active = $query->clone()->where('status', 'active')->count();
            $expired = $query->clone()->where('status', 'expired')->count();
            $expiringSoon = $query->clone()->where('status', 'expiring_soon')->count();

            $complianceRate = $total > 0 ? round(($active / $total) * 100, 1) : 0;

            $analytics[] = [
                'training_type' => $trainingType->name,
                'code' => $trainingType->code,
                'total' => $total,
                'active' => $active,
                'expired' => $expired,
                'expiring_soon' => $expiringSoon,
                'compliance_rate' => $complianceRate,
                'duration_months' => $trainingType->duration_months,
            ];
        }

        return $analytics;
    }

    /**
     * Get training trends over time
     */
    private function getTrainingTrends($timeframe, $department = null)
    {
        $startDate = match($timeframe) {
            '3months' => now()->subMonths(3),
            '6months' => now()->subMonths(6),
            '1year' => now()->subYear(),
            '2years' => now()->subYears(2),
            default => now()->subMonths(6),
        };

        $query = TrainingRecord::where('created_at', '>=', $startDate);

        if ($department) {
            $query->whereHas('employee', function($q) use ($department) {
                $q->where('unit_organisasi', $department);
            });
        }

        $period = match($timeframe) {
            '3months' => '%Y-%m-%d',
            '6months' => '%Y-%m',
            '1year' => '%Y-%m',
            '2years' => '%Y-%m',
            default => '%Y-%m',
        };

        $trends = $query->selectRaw("DATE_FORMAT(created_at, '{$period}') as period, COUNT(*) as count")
                       ->groupBy('period')
                       ->orderBy('period')
                       ->get()
                       ->map(function($item) use ($period) {
                           return [
                               'period' => $item->period,
                               'count' => $item->count,
                               'formatted_period' => $this->formatPeriod($item->period, $period),
                           ];
                       });

        // Also get status distribution over time
        $statusTrends = $query->selectRaw("DATE_FORMAT(created_at, '{$period}') as period, status, COUNT(*) as count")
                             ->groupBy(['period', 'status'])
                             ->orderBy('period')
                             ->get()
                             ->groupBy('period')
                             ->map(function($items, $period) {
                                 $statusCounts = $items->pluck('count', 'status')->toArray();
                                 return [
                                     'period' => $period,
                                     'active' => $statusCounts['active'] ?? 0,
                                     'expired' => $statusCounts['expired'] ?? 0,
                                     'expiring_soon' => $statusCounts['expiring_soon'] ?? 0,
                                     'total' => array_sum($statusCounts),
                                 ];
                             })
                             ->values();

        return [
            'total_trends' => $trends,
            'status_trends' => $statusTrends,
        ];
    }

    /**
     * Get department breakdown
     */
    private function getDepartmentBreakdown()
    {
        $departments = Employee::select('unit_organisasi')
                              ->distinct()
                              ->whereNotNull('unit_organisasi')
                              ->pluck('unit_organisasi');

        $breakdown = [];
        $trainingTypes = TrainingType::active()->count();

        foreach ($departments as $department) {
            $employees = Employee::where('unit_organisasi', $department)->get();
            $totalEmployees = $employees->count();

            if ($totalEmployees > 0) {
                $compliantEmployees = $employees->filter(function($employee) use ($trainingTypes) {
                    return $employee->activeTrainingRecords()->count() >= $trainingTypes;
                })->count();

                $totalTrainings = TrainingRecord::whereHas('employee', function($q) use ($department) {
                    $q->where('unit_organisasi', $department);
                })->count();

                $activeTrainings = TrainingRecord::whereHas('employee', function($q) use ($department) {
                    $q->where('unit_organisasi', $department);
                })->where('status', 'active')->count();

                $expiredTrainings = TrainingRecord::whereHas('employee', function($q) use ($department) {
                    $q->where('unit_organisasi', $department);
                })->where('status', 'expired')->count();

                $complianceRate = round(($compliantEmployees / $totalEmployees) * 100, 1);

                $breakdown[] = [
                    'department' => $department,
                    'total_employees' => $totalEmployees,
                    'compliant_employees' => $compliantEmployees,
                    'compliance_rate' => $complianceRate,
                    'total_trainings' => $totalTrainings,
                    'active_trainings' => $activeTrainings,
                    'expired_trainings' => $expiredTrainings,
                    'status' => $complianceRate >= 80 ? 'excellent' :
                               ($complianceRate >= 60 ? 'good' :
                               ($complianceRate >= 40 ? 'warning' : 'critical')),
                ];
            }
        }

        return collect($breakdown)->sortByDesc('compliance_rate')->values();
    }

    /**
     * Get expiry analytics
     */
    private function getExpiryAnalytics($department = null)
    {
        $query = TrainingRecord::query();

        if ($department) {
            $query->whereHas('employee', function($q) use ($department) {
                $q->where('unit_organisasi', $department);
            });
        }

        // Expiry distribution by months
        $expiryDistribution = [];
        for ($i = 1; $i <= 12; $i++) {
            $startDate = now()->addMonths($i - 1)->startOfMonth();
            $endDate = now()->addMonths($i - 1)->endOfMonth();

            $count = $query->clone()
                          ->whereBetween('valid_until', [$startDate, $endDate])
                          ->count();

            $expiryDistribution[] = [
                'month' => $startDate->format('M Y'),
                'count' => $count,
                'period' => $i,
            ];
        }

        // Certificates expiring in next 30, 60, 90 days
        $upcoming = [
            'next_30_days' => $query->clone()->expiringSoon(30)->count(),
            'next_60_days' => $query->clone()->expiringSoon(60)->count(),
            'next_90_days' => $query->clone()->expiringSoon(90)->count(),
        ];

        // Overdue certificates
        $overdue = $query->clone()->expired()->count();

        return [
            'expiry_distribution' => $expiryDistribution,
            'upcoming_expiries' => $upcoming,
            'overdue_certificates' => $overdue,
        ];
    }

    /**
     * Format period for display
     */
    private function formatPeriod($period, $format)
    {
        try {
            if ($format === '%Y-%m-%d') {
                return Carbon::createFromFormat('Y-m-d', $period)->format('M d, Y');
            } else {
                return Carbon::createFromFormat('Y-m', $period)->format('M Y');
            }
        } catch (\Exception $e) {
            return $period;
        }
    }

    /**
     * Export analytics data
     */
    public function export(Request $request)
    {
        $department = $request->get('department');
        $format = $request->get('format', 'json');

        $data = [
            'overview' => $this->getOverviewStats($department),
            'compliance' => $this->getComplianceAnalytics($department),
            'departments' => $this->getDepartmentBreakdown(),
            'expiry' => $this->getExpiryAnalytics($department),
            'generated_at' => now(),
            'filters' => [
                'department' => $department,
            ],
        ];

        if ($format === 'json') {
            return response()->json($data);
        }

        // For other formats (CSV, Excel), implement accordingly
        return response()->json(['error' => 'Format not supported yet'], 400);
    }

    /**
     * Get training performance metrics
     */
    public function performanceMetrics(Request $request)
    {
        $department = $request->get('department');

        // Calculate various performance metrics
        $metrics = [
            'average_completion_time' => $this->getAverageCompletionTime($department),
            'training_frequency' => $this->getTrainingFrequency($department),
            'renewal_rates' => $this->getRenewalRates($department),
            'compliance_trends' => $this->getComplianceTrends($department),
        ];

        return response()->json($metrics);
    }

    /**
     * Get average completion time for training types
     */
    private function getAverageCompletionTime($department)
    {
        // This would calculate average time between training start and completion
        // For now, return placeholder data
        return TrainingType::active()->get()->map(function($type) {
            return [
                'training_type' => $type->name,
                'average_days' => rand(7, 30), // Placeholder
            ];
        });
    }

    /**
     * Get training frequency metrics
     */
    private function getTrainingFrequency($department)
    {
        $query = TrainingRecord::query();

        if ($department) {
            $query->whereHas('employee', function($q) use ($department) {
                $q->where('unit_organisasi', $department);
            });
        }

        return $query->selectRaw('MONTH(created_at) as month, COUNT(*) as count')
                    ->where('created_at', '>=', now()->subYear())
                    ->groupBy('month')
                    ->orderBy('month')
                    ->get()
                    ->map(function($item) {
                        return [
                            'month' => Carbon::create()->month($item->month)->format('M'),
                            'count' => $item->count,
                        ];
                    });
    }

    /**
     * Get renewal rates
     */
    private function getRenewalRates($department)
    {
        // Calculate how many certificates are renewed vs new
        $query = TrainingRecord::query();

        if ($department) {
            $query->whereHas('employee', function($q) use ($department) {
                $q->where('unit_organisasi', $department);
            });
        }

        $total = $query->count();
        $renewals = $query->where('notes', 'like', '%renewal%')->count();
        $newCertificates = $total - $renewals;

        return [
            'total_certificates' => $total,
            'renewals' => $renewals,
            'new_certificates' => $newCertificates,
            'renewal_rate' => $total > 0 ? round(($renewals / $total) * 100, 1) : 0,
        ];
    }

    /**
     * Get compliance trends over time
     */
    private function getComplianceTrends($department)
    {
        // Track compliance rates over the last 12 months
        $trends = [];

        for ($i = 11; $i >= 0; $i--) {
            $month = now()->subMonths($i);
            $monthStart = $month->copy()->startOfMonth();
            $monthEnd = $month->copy()->endOfMonth();

            $employeeQuery = Employee::query();
            if ($department) {
                $employeeQuery->where('unit_organisasi', $department);
            }

            $totalEmployees = $employeeQuery->count();
            $trainingTypes = TrainingType::active()->count();

            if ($totalEmployees > 0 && $trainingTypes > 0) {
                $compliantEmployees = $employeeQuery->clone()
                    ->whereHas('trainingRecords', function($query) use ($monthEnd, $trainingTypes) {
                        $query->where('status', 'active')
                              ->where('created_at', '<=', $monthEnd);
                    }, '>=', $trainingTypes)
                    ->count();

                $complianceRate = round(($compliantEmployees / $totalEmployees) * 100, 1);
            } else {
                $complianceRate = 0;
            }

            $trends[] = [
                'month' => $month->format('M Y'),
                'compliance_rate' => $complianceRate,
                'total_employees' => $totalEmployees,
                'compliant_employees' => $compliantEmployees ?? 0,
            ];
        }

        return $trends;
    }
}
