<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule for GAPURA Training System
     * Automated notifications and maintenance tasks
     */
    protected function schedule(Schedule $schedule): void
    {
        // ========================================================================
        // TRAINING NOTIFICATION SCHEDULES
        // ========================================================================

        // Daily expiry notifications (9:00 AM, Monday-Friday)
        $schedule->command('training:send-notifications --type=expiry --days=30')
                 ->weekdays()
                 ->dailyAt('09:00')
                 ->description('Send certificate expiry notifications (30 days)')
                 ->emailOutputOnFailure('admin@gapura.com');

        // Additional expiry notifications for urgent cases (7 days notice)
        $schedule->command('training:send-notifications --type=expiry --days=7')
                 ->weekdays()
                 ->dailyAt('10:00')
                 ->description('Send urgent certificate expiry notifications (7 days)')
                 ->emailOutputOnFailure('admin@gapura.com');

        // Weekly compliance reminders (Monday 10:00 AM)
        $schedule->command('training:send-notifications --type=compliance')
                 ->mondays()
                 ->at('10:00')
                 ->description('Send weekly compliance reminders')
                 ->emailOutputOnFailure('admin@gapura.com');

        // Daily digest for HR team (8:00 AM, Monday-Friday)
        $schedule->command('training:send-notifications --type=digest')
                 ->weekdays()
                 ->dailyAt('08:00')
                 ->description('Send daily training system digest to HR')
                 ->emailOutputOnFailure('admin@gapura.com');

        // ========================================================================
        // CERTIFICATE MANAGEMENT SCHEDULES
        // ========================================================================

        // Generate missing certificates (daily at 11:00 PM)
        $schedule->command('training:generate-certificates --missing --batch-size=20')
                 ->daily()
                 ->at('23:00')
                 ->description('Generate missing training certificates')
                 ->emailOutputOnFailure('admin@gapura.com');

        // ========================================================================
        // SYSTEM MAINTENANCE SCHEDULES
        // ========================================================================

        // Clean up temporary files (daily at 2:00 AM)
        $schedule->call(function () {
            // Clean up old export files (older than 7 days)
            $exportPath = storage_path('app/public/exports');
            if (is_dir($exportPath)) {
                $files = glob($exportPath . '/*');
                $now = time();
                foreach ($files as $file) {
                    if (is_file($file) && ($now - filemtime($file)) >= (7 * 24 * 3600)) {
                        unlink($file);
                    }
                }
            }

            // Clean up old certificate files (older than 30 days)
            $certificatePath = storage_path('app/public/certificates');
            if (is_dir($certificatePath)) {
                $files = glob($certificatePath . '/*');
                $now = time();
                foreach ($files as $file) {
                    if (is_file($file) && ($now - filemtime($file)) >= (30 * 24 * 3600)) {
                        unlink($file);
                    }
                }
            }
        })
        ->daily()
        ->at('02:00')
        ->description('Clean up temporary and old files');

        // Database optimization (weekly on Sunday at 3:00 AM)
        $schedule->command('optimize:clear')
                 ->sundays()
                 ->at('03:00')
                 ->description('Clear Laravel optimization caches');

        // Backup database (daily at 1:00 AM)
        $schedule->call(function () {
            // Create database backup
            $backupPath = storage_path('app/backups');
            if (!is_dir($backupPath)) {
                mkdir($backupPath, 0755, true);
            }

            $filename = 'gapura_training_backup_' . date('Y-m-d_H-i-s') . '.sql';
            $command = sprintf(
                'mysqldump --user=%s --password=%s --host=%s %s > %s',
                env('DB_USERNAME'),
                env('DB_PASSWORD'),
                env('DB_HOST'),
                env('DB_DATABASE'),
                $backupPath . '/' . $filename
            );

            exec($command);

            // Keep only last 7 backups
            $backups = glob($backupPath . '/gapura_training_backup_*.sql');
            if (count($backups) > 7) {
                usort($backups, function($a, $b) {
                    return filemtime($a) - filemtime($b);
                });
                foreach (array_slice($backups, 0, -7) as $oldBackup) {
                    unlink($oldBackup);
                }
            }
        })
        ->daily()
        ->at('01:00')
        ->description('Create daily database backup');

        // ========================================================================
        // MONITORING AND ALERTS
        // ========================================================================

        // System health check (every 4 hours)
        $schedule->call(function () {
            $stats = [
                'expired_certificates' => \App\Models\TrainingRecord::where('expiry_date', '<', now())->count(),
                'expiring_soon' => \App\Models\TrainingRecord::whereBetween('expiry_date', [now(), now()->addDays(7)])->count(),
                'missing_certificates' => \App\Models\TrainingRecord::whereNull('certificate_number')->count(),
                'inactive_employees' => \App\Models\Employee::where('is_active', false)->count(),
            ];

            // Send alert if critical thresholds exceeded
            if ($stats['expired_certificates'] > 50 || $stats['expiring_soon'] > 100) {
                $adminUsers = \App\Models\User::where('role', 'super_admin')->get();
                foreach ($adminUsers as $user) {
                    \Mail::send('emails.system-alert', [
                        'user' => $user,
                        'stats' => $stats,
                        'timestamp' => now()
                    ], function ($message) use ($user) {
                        $message->to($user->email, $user->name)
                                ->subject('GAPURA Training System - Critical Alert');
                    });
                }
            }
        })
        ->everyFourHours()
        ->description('System health monitoring');

        // ========================================================================
        // WEEKLY AND MONTHLY REPORTS
        // ========================================================================

        // Weekly compliance report (Friday 4:00 PM)
        $schedule->call(function () {
            $exportService = app(\App\Services\ExportService::class);
            $filePath = $exportService->exportComplianceReport();

            $hrUsers = \App\Models\User::whereIn('role', ['admin', 'super_admin'])->get();
            foreach ($hrUsers as $user) {
                \Mail::send('emails.weekly-compliance-report', [
                    'user' => $user,
                    'week' => now()->format('W'),
                    'year' => now()->format('Y')
                ], function ($message) use ($user, $filePath) {
                    $message->to($user->email, $user->name)
                            ->subject('Weekly Compliance Report - Week ' . now()->format('W'))
                            ->attach(storage_path('app/public/' . $filePath));
                });
            }
        })
        ->fridays()
        ->at('16:00')
        ->description('Generate and send weekly compliance report');

        // Monthly training summary (last day of month, 5:00 PM)
        $schedule->call(function () {
            $stats = [
                'certificates_issued' => \App\Models\TrainingRecord::whereMonth('created_at', now()->month)->count(),
                'employees_trained' => \App\Models\TrainingRecord::whereMonth('created_at', now()->month)->distinct('employee_id')->count(),
                'compliance_rate' => $this->calculateMonthlyComplianceRate(),
                'total_cost' => \App\Models\TrainingRecord::whereMonth('created_at', now()->month)->sum('cost'),
            ];

            $hrUsers = \App\Models\User::whereIn('role', ['admin', 'super_admin'])->get();
            foreach ($hrUsers as $user) {
                \Mail::send('emails.monthly-training-summary', [
                    'user' => $user,
                    'stats' => $stats,
                    'month' => now()->format('F Y')
                ], function ($message) use ($user) {
                    $message->to($user->email, $user->name)
                            ->subject('Monthly Training Summary - ' . now()->format('F Y'));
                });
            }
        })
        ->monthlyOn(now()->endOfMonth()->day, '17:00')
        ->description('Generate and send monthly training summary');

        // ========================================================================
        // CONDITIONAL SCHEDULES
        // ========================================================================

        // Emergency notification check (every 30 minutes during business hours)
        $schedule->call(function () {
            $emergencyExpiring = \App\Models\TrainingRecord::where('expiry_date', '<=', now()->addDays(3))
                                                          ->where('expiry_date', '>', now())
                                                          ->whereHas('trainingType', function($query) {
                                                              $query->where('compliance_level', 'CRITICAL');
                                                          })
                                                          ->count();

            if ($emergencyExpiring > 0) {
                $notificationService = app(\App\Services\NotificationService::class);
                $notificationService->sendExpiringCertificateNotifications(3);
            }
        })
        ->weekdays()
        ->hourly()
        ->between('08:00', '17:00')
        ->description('Emergency expiry check for critical certificates');

        // ========================================================================
        // DEVELOPMENT AND TESTING SCHEDULES
        // ========================================================================

        // Only run in production
        if (app()->environment('production')) {
            // Additional production-only schedules can be added here
        }

        // Only run in development/staging
        if (app()->environment(['local', 'staging'])) {
            // Test notification schedule (every minute for testing)
            // $schedule->command('training:send-notifications --type=digest')
            //          ->everyMinute()
            //          ->description('Test notification schedule');
        }
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }

    /**
     * Calculate monthly compliance rate
     */
    private function calculateMonthlyComplianceRate(): float
    {
        $mandatoryTrainingTypes = \App\Models\TrainingType::where('is_mandatory', true)->count();
        $activeEmployees = \App\Models\Employee::where('is_active', true)->count();

        if ($mandatoryTrainingTypes === 0 || $activeEmployees === 0) {
            return 0;
        }

        $totalRequiredRecords = $mandatoryTrainingTypes * $activeEmployees;
        $validRecords = \App\Models\TrainingRecord::whereHas('trainingType', function($query) {
                $query->where('is_mandatory', true);
            })
            ->where('expiry_date', '>', now())
            ->count();

        return round(($validRecords / $totalRequiredRecords) * 100, 2);
    }
}
