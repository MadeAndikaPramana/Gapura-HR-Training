<?php

namespace App\Console\Commands;

use App\Services\NotificationService;
use Illuminate\Console\Command;
use Carbon\Carbon;

class SendTrainingNotifications extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'training:send-notifications
                            {--type=all : Type of notifications to send (all, expiry, compliance, digest)}
                            {--days=30 : Days ahead to check for expiring certificates}
                            {--force : Force send notifications even if not in schedule}';

    /**
     * The console command description.
     */
    protected $description = 'Send training system notifications (expiry alerts, compliance reminders, daily digest)';

    /**
     * Notification service instance
     */
    protected $notificationService;

    /**
     * Create a new command instance.
     */
    public function __construct(NotificationService $notificationService)
    {
        parent::__construct();
        $this->notificationService = $notificationService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $type = $this->option('type');
        $days = (int) $this->option('days');
        $force = $this->option('force');

        $this->info('🚀 GAPURA Training Notification System');
        $this->info('====================================');
        $this->info('Starting notification process...');
        $this->newLine();

        // Check if we should run based on schedule (unless forced)
        if (!$force && !$this->shouldRunNotifications($type)) {
            $this->warn('⏭️  Notifications not scheduled for this time. Use --force to override.');
            return 0;
        }

        $results = [];

        try {
            switch ($type) {
                case 'expiry':
                    $results['expiry'] = $this->sendExpiryNotifications($days);
                    break;

                case 'compliance':
                    $results['compliance'] = $this->sendComplianceReminders();
                    break;

                case 'digest':
                    $results['digest'] = $this->sendDailyDigest();
                    break;

                case 'all':
                default:
                    $results['expiry'] = $this->sendExpiryNotifications($days);
                    $results['compliance'] = $this->sendComplianceReminders();
                    $results['digest'] = $this->sendDailyDigest();
                    break;
            }

            $this->displayResults($results);
            $this->info('✅ Notification process completed successfully!');

        } catch (\Exception $e) {
            $this->error('❌ Notification process failed: ' . $e->getMessage());
            $this->error('Stack trace: ' . $e->getTraceAsString());
            return 1;
        }

        return 0;
    }

    /**
     * Send certificate expiry notifications
     */
    private function sendExpiryNotifications(int $days): array
    {
        $this->info("📧 Sending certificate expiry notifications ({$days} days ahead)...");

        $results = $this->notificationService->sendExpiringCertificateNotifications($days);

        $this->line("   • Employees notified: {$results['employees_notified']}");
        $this->line("   • Total notifications: {$results['total_notifications']}");
        $this->line("   • HR notifications: {$results['hr_notifications']}");

        if (!empty($results['errors'])) {
            $this->warn("   • Errors: " . count($results['errors']));
            foreach ($results['errors'] as $error) {
                $this->error("     - {$error['error']}");
            }
        }

        return $results;
    }

    /**
     * Send compliance reminder notifications
     */
    private function sendComplianceReminders(): array
    {
        $this->info('🔔 Sending compliance reminder notifications...');

        $results = $this->notificationService->sendComplianceReminders();

        $this->line("   • Non-compliant employees: {$results['non_compliant_employees']}");
        $this->line("   • Notifications sent: {$results['notifications_sent']}");

        if (!empty($results['errors'])) {
            $this->warn("   • Errors: " . count($results['errors']));
            foreach ($results['errors'] as $error) {
                $this->error("     - Employee ID {$error['employee_id']}: {$error['error']}");
            }
        }

        return $results;
    }

    /**
     * Send daily digest
     */
    private function sendDailyDigest(): array
    {
        $this->info('📊 Sending daily digest to HR team...');

        $success = $this->notificationService->sendDailyDigest();

        $results = ['success' => $success];

        if ($success) {
            $this->line('   • Daily digest sent successfully');
        } else {
            $this->error('   • Failed to send daily digest');
        }

        return $results;
    }

    /**
     * Check if notifications should run based on schedule
     */
    private function shouldRunNotifications(string $type): bool
    {
        $now = Carbon::now();
        $hour = $now->hour;
        $dayOfWeek = $now->dayOfWeek;

        switch ($type) {
            case 'expiry':
                // Send expiry notifications daily at 9 AM
                return $hour === 9;

            case 'compliance':
                // Send compliance reminders on Monday at 10 AM
                return $dayOfWeek === Carbon::MONDAY && $hour === 10;

            case 'digest':
                // Send daily digest at 8 AM on weekdays
                return $hour === 8 && $dayOfWeek >= Carbon::MONDAY && $dayOfWeek <= Carbon::FRIDAY;

            case 'all':
                // Run all if any condition is met
                return $this->shouldRunNotifications('expiry') ||
                       $this->shouldRunNotifications('compliance') ||
                       $this->shouldRunNotifications('digest');

            default:
                return false;
        }
    }

    /**
     * Display notification results
     */
    private function displayResults(array $results): void
    {
        $this->newLine();
        $this->info('📈 NOTIFICATION SUMMARY');
        $this->info('======================');

        foreach ($results as $type => $result) {
            $this->line("📌 " . ucfirst($type) . " Notifications:");

            if ($type === 'expiry' && is_array($result)) {
                $this->line("   Employees Notified: {$result['employees_notified']}");
                $this->line("   Total Notifications: {$result['total_notifications']}");
                $this->line("   HR Notifications: {$result['hr_notifications']}");
                $this->line("   Errors: " . count($result['errors']));
            } elseif ($type === 'compliance' && is_array($result)) {
                $this->line("   Non-compliant Employees: {$result['non_compliant_employees']}");
                $this->line("   Notifications Sent: {$result['notifications_sent']}");
                $this->line("   Errors: " . count($result['errors']));
            } elseif ($type === 'digest' && is_array($result)) {
                $this->line("   Status: " . ($result['success'] ? 'Success' : 'Failed'));
            }

            $this->newLine();
        }

        // Calculate totals
        $totalNotifications = 0;
        $totalErrors = 0;

        foreach ($results as $result) {
            if (is_array($result)) {
                $totalNotifications += $result['total_notifications'] ?? $result['notifications_sent'] ?? 0;
                $totalErrors += count($result['errors'] ?? []);
            }
        }

        $this->info("🎯 TOTAL SUMMARY:");
        $this->line("   Total Notifications Sent: {$totalNotifications}");
        $this->line("   Total Errors: {$totalErrors}");
        $this->line("   Success Rate: " . ($totalNotifications > 0 ? round((($totalNotifications - $totalErrors) / $totalNotifications) * 100, 2) : 100) . "%");
    }
}
