<?php

// Update app/Http/Controllers/DashboardController.php or create TrainingDashboardController.php
namespace App\Http\Controllers;

use App\Models\TrainingRecord;
use App\Models\TrainingType;
use App\Models\Employee;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;

class TrainingDashboardController extends Controller
{
    /**
     * Training dashboard with comprehensive overview
     */
    public function index()
    {
        $today = Carbon::today();

        // Calculate key statistics
        $stats = [
            'total_records' => TrainingRecord::count(),
            'valid_certificates' => TrainingRecord::valid()->count(),
            'expired_certificates' => TrainingRecord::expired()->count(),
            'due_soon' => TrainingRecord::dueSoon(30)->count(),
            'total_employees' => Employee::whereHas('trainingRecords')->count(),
            'total_training_types' => TrainingType::active()->count(),
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
                                            ->dueSoon(30)
                                            ->orderBy('expiry_date')
                                            ->limit(10)
                                            ->get();

        // Training type statistics
        $trainingTypeStats = TrainingType::withCount([
            'trainingRecords as total_records',
            'trainingRecords as valid_records' => function($query) {
                $query->valid();
            },
            'trainingRecords as expired_records' => function($query) {
                $query->expired();
            }
        ])->active()->get();

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
            'quickActions' => $quickActions,
            'title' => 'Training Dashboard',
            'subtitle' => 'Overview of training records and compliance status'
        ]);
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
        $mandatoryTrainingTypes = TrainingType::mandatory()->active()->count();
        $activeEmployees = Employee::where('status_kerja', 'Aktif')->count();
        $totalRequiredCertificates = $mandatoryTrainingTypes * $activeEmployees;

        if ($totalRequiredCertificates === 0) {
            return 100;
        }

        $validMandatoryCertificates = TrainingRecord::valid()
                                                  ->whereHas('trainingType', function($query) {
                                                      $query->mandatory();
                                                  })
                                                  ->count();

        return round(($validMandatoryCertificates / $totalRequiredCertificates) * 100, 2);
    }

    /**
     * API endpoint for compliance statistics
     */
    public function complianceStats()
    {
        $trainingTypes = TrainingType::active()->get();
        $stats = [];

        foreach ($trainingTypes as $type) {
            $total = $type->total_records_count;
            $valid = $type->active_records_count;
            $expired = $type->expired_records_count;

            $stats[] = [
                'name' => $type->name,
                'category' => $type->category,
                'total' => $total,
                'valid' => $valid,
                'expired' => $expired,
                'compliance_rate' => $total > 0 ? round(($valid / $total) * 100, 2) : 0,
            ];
        }

        return response()->json($stats);
    }

    /**
     * API endpoint for expiry trends
     */
    public function expiryTrends()
    {
        $months = [];
        $now = Carbon::now();

        for ($i = 5; $i >= 0; $i--) {
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
    }
}
