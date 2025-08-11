<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Employee;
use App\Models\TrainingType;
use App\Models\TrainingRecord;
use App\Models\BackgroundCheck;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * Display the dashboard
     */
    public function index()
    {
        // Get dashboard statistics
        $stats = $this->getDashboardStats();

        // Get recent activities
        $recentActivities = $this->getRecentActivities();

        // Get compliance overview
        $complianceOverview = $this->getComplianceOverview();

        return Inertia::render('Dashboard', [
            'stats' => $stats,
            'recentActivities' => $recentActivities,
            'complianceOverview' => $complianceOverview,
        ]);
    }

    /**
     * Get dashboard statistics
     */
    private function getDashboardStats()
    {
        $totalEmployees = Employee::count();
        $totalTrainings = TrainingRecord::count();
        $expiredCertificates = TrainingRecord::expired()->count();
        $expiringSoon = TrainingRecord::expiringSoon(30)->count();
        $validBackgroundChecks = BackgroundCheck::passed()->count();

        // Calculate compliance rate
        $totalTrainingTypes = TrainingType::active()->count();
        $compliantEmployees = 0;

        if ($totalTrainingTypes > 0) {
            $compliantEmployees = Employee::whereHas('trainingRecords', function($query) use ($totalTrainingTypes) {
                $query->where('status', 'active');
            }, '>=', $totalTrainingTypes)->count();
        }

        $complianceRate = $totalEmployees > 0 ? round(($compliantEmployees / $totalEmployees) * 100, 1) : 0;

        return [
            'totalEmployees' => $totalEmployees,
            'totalTrainings' => $totalTrainings,
            'expiredCertificates' => $expiredCertificates,
            'expiringSoon' => $expiringSoon,
            'backgroundChecksValid' => $validBackgroundChecks,
            'complianceRate' => $complianceRate,
            'compliantEmployees' => $compliantEmployees,
        ];
    }

    /**
     * Get recent activities
     */
    private function getRecentActivities()
    {
        $recentTrainings = TrainingRecord::with(['employee', 'trainingType'])
                                        ->latest('created_at')
                                        ->limit(10)
                                        ->get()
                                        ->map(function($record) {
                                            return [
                                                'type' => 'training',
                                                'title' => $record->trainingType->name ?? 'Unknown Training',
                                                'employee' => $record->employee->nama_lengkap ?? 'Unknown Employee',
                                                'status' => $record->status,
                                                'date' => $record->created_at,
                                                'icon' => 'GraduationCap',
                                            ];
                                        });

        $recentBackgroundChecks = BackgroundCheck::with('employee')
                                                 ->latest('created_at')
                                                 ->limit(5)
                                                 ->get()
                                                 ->map(function($check) {
                                                     return [
                                                         'type' => 'background_check',
                                                         'title' => 'Background Check',
                                                         'employee' => $check->employee->nama_lengkap ?? 'Unknown Employee',
                                                         'status' => $check->status,
                                                         'date' => $check->created_at,
                                                         'icon' => 'Shield',
                                                     ];
                                                 });

        return $recentTrainings->concat($recentBackgroundChecks)
                              ->sortByDesc('date')
                              ->take(10)
                              ->values();
    }

    /**
     * Get compliance overview by department
     */
    private function getComplianceOverview()
    {
        $departments = Employee::select('unit_organisasi')
                              ->distinct()
                              ->whereNotNull('unit_organisasi')
                              ->pluck('unit_organisasi');

        $overview = [];
        $totalTrainingTypes = TrainingType::active()->count();

        foreach ($departments as $department) {
            $employeesInDept = Employee::where('unit_organisasi', $department)->count();

            if ($employeesInDept > 0 && $totalTrainingTypes > 0) {
                $compliantInDept = Employee::where('unit_organisasi', $department)
                                          ->whereHas('trainingRecords', function($query) use ($totalTrainingTypes) {
                                              $query->where('status', 'active');
                                          }, '>=', $totalTrainingTypes)
                                          ->count();

                $complianceRate = round(($compliantInDept / $employeesInDept) * 100, 1);

                $overview[] = [
                    'department' => $department,
                    'totalEmployees' => $employeesInDept,
                    'compliantEmployees' => $compliantInDept,
                    'complianceRate' => $complianceRate,
                    'status' => $complianceRate >= 80 ? 'good' : ($complianceRate >= 60 ? 'warning' : 'critical'),
                ];
            }
        }

        return collect($overview)->sortByDesc('complianceRate')->values();
    }

    /**
     * Get training statistics for charts
     */
    public function getTrainingStats()
    {
        // Training status distribution
        $statusDistribution = TrainingRecord::selectRaw('status, COUNT(*) as count')
                                           ->groupBy('status')
                                           ->get()
                                           ->pluck('count', 'status');

        // Monthly training trends (last 6 months)
        $monthlyTrends = TrainingRecord::selectRaw('DATE_FORMAT(created_at, "%Y-%m") as month, COUNT(*) as count')
                                      ->where('created_at', '>=', now()->subMonths(6))
                                      ->groupBy('month')
                                      ->orderBy('month')
                                      ->get();

        // Training type distribution
        $typeDistribution = TrainingRecord::with('trainingType')
                                         ->selectRaw('training_type_id, COUNT(*) as count')
                                         ->groupBy('training_type_id')
                                         ->get()
                                         ->map(function($record) {
                                             return [
                                                 'name' => $record->trainingType->name ?? 'Unknown',
                                                 'count' => $record->count,
                                             ];
                                         });

        return response()->json([
            'statusDistribution' => $statusDistribution,
            'monthlyTrends' => $monthlyTrends,
            'typeDistribution' => $typeDistribution,
        ]);
    }
}
