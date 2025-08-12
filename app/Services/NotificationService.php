<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\TrainingRecord;
use App\Models\TrainingType;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification as BaseNotification;

class NotificationService
{
    /**
     * Send expiring certificate notifications
     */
    public function sendExpiringCertificateNotifications(int $days = 30): array
    {
        $expiringRecords = TrainingRecord::with(['employee', 'trainingType'])
            ->where('expiry_date', '<=', Carbon::now()->addDays($days))
            ->where('expiry_date', '>', Carbon::now())
            ->where('completion_status', 'COMPLETED')
            ->get();

        $results = [
            'total_notifications' => 0,
            'employees_notified' => 0,
            'hr_notifications' => 0,
            'errors' => []
        ];

        // Group by employee
        $groupedRecords = $expiringRecords->groupBy('employee_id');

        foreach ($groupedRecords as $employeeId => $records) {
            try {
                $employee = $records->first()->employee;

                // Send notification to employee
                $this->sendEmployeeExpiryNotification($employee, $records);
                $results['employees_notified']++;
                $results['total_notifications'] += $records->count();

            } catch (\Exception $e) {
                $results['errors'][] = [
                    'employee_id' => $employeeId,
                    'error' => $e->getMessage()
                ];
            }
        }

        // Send summary to HR
        if ($expiringRecords->count() > 0) {
            try {
                $this->sendHRExpiryNotification($expiringRecords, $days);
                $results['hr_notifications'] = 1;
            } catch (\Exception $e) {
                $results['errors'][] = [
                    'type' => 'hr_notification',
                    'error' => $e->getMessage()
                ];
            }
        }

        return $results;
    }

    /**
     * Send new training assignment notification
     */
    public function sendTrainingAssignmentNotification(TrainingRecord $trainingRecord): bool
    {
        try {
            $employee = $trainingRecord->employee;
            $trainingType = $trainingRecord->trainingType;

            // Send to employee
            Mail::send('emails.training-assignment', [
                'employee' => $employee,
                'trainingRecord' => $trainingRecord,
                'trainingType' => $trainingType
            ], function ($message) use ($employee, $trainingType) {
                $message->to($employee->email, $employee->name)
                        ->subject('New Training Certificate - ' . $trainingType->name);
            });

            return true;
        } catch (\Exception $e) {
            \Log::error('Training assignment notification failed', [
                'training_record_id' => $trainingRecord->id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Send compliance reminder notifications
     */
    public function sendComplianceReminders(): array
    {
        $mandatoryTrainingTypes = TrainingType::where('is_mandatory', true)
                                             ->where('is_active', true)
                                             ->get();

        $employees = Employee::where('is_active', true)
                            ->with(['trainingRecords.trainingType'])
                            ->get();

        $results = [
            'non_compliant_employees' => 0,
            'notifications_sent' => 0,
            'errors' => []
        ];

        foreach ($employees as $employee) {
            $nonCompliantTrainings = [];

            foreach ($mandatoryTrainingTypes as $trainingType) {
                $validRecord = $employee->trainingRecords
                    ->where('training_type_id', $trainingType->id)
                    ->filter(function($record) {
                        return $record->expiry_date > Carbon::now();
                    })
                    ->first();

                if (!$validRecord) {
                    $nonCompliantTrainings[] = $trainingType;
                }
            }

            if (!empty($nonCompliantTrainings)) {
                try {
                    $this->sendComplianceReminderToEmployee($employee, $nonCompliantTrainings);
                    $results['non_compliant_employees']++;
                    $results['notifications_sent']++;
                } catch (\Exception $e) {
                    $results['errors'][] = [
                        'employee_id' => $employee->id,
                        'error' => $e->getMessage()
                    ];
                }
            }
        }

        return $results;
    }

    /**
     * Send daily digest to HR team
     */
    public function sendDailyDigest(): bool
    {
        try {
            $stats = $this->getDailyStats();

            $hrUsers = User::whereIn('role', ['admin', 'super_admin'])->get();

            foreach ($hrUsers as $user) {
                Mail::send('emails.daily-digest', [
                    'user' => $user,
                    'stats' => $stats,
                    'date' => Carbon::now()->format('Y-m-d')
                ], function ($message) use ($user) {
                    $message->to($user->email, $user->name)
                            ->subject('Daily Training System Digest - ' . Carbon::now()->format('Y-m-d'));
                });
            }

            return true;
        } catch (\Exception $e) {
            \Log::error('Daily digest notification failed', [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Send bulk certificate generation notification
     */
    public function sendBulkCertificateNotification(array $results, User $user): bool
    {
        try {
            Mail::send('emails.bulk-certificate-generated', [
                'user' => $user,
                'results' => $results,
                'timestamp' => Carbon::now()
            ], function ($message) use ($user) {
                $message->to($user->email, $user->name)
                        ->subject('Bulk Certificate Generation Complete');
            });

            return true;
        } catch (\Exception $e) {
            \Log::error('Bulk certificate notification failed', [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Send individual employee expiry notification
     */
    private function sendEmployeeExpiryNotification(Employee $employee, $expiringRecords): void
    {
        Mail::send('emails.certificate-expiry-employee', [
            'employee' => $employee,
            'expiringRecords' => $expiringRecords
        ], function ($message) use ($employee) {
            $message->to($employee->email, $employee->name)
                    ->subject('Training Certificate Expiry Reminder');
        });
    }

    /**
     * Send HR summary expiry notification
     */
    private function sendHRExpiryNotification($expiringRecords, int $days): void
    {
        $hrUsers = User::whereIn('role', ['admin', 'super_admin'])->get();

        $summary = [
            'total_expiring' => $expiringRecords->count(),
            'employees_affected' => $expiringRecords->groupBy('employee_id')->count(),
            'by_department' => $expiringRecords->groupBy('employee.department')->map->count(),
            'by_training_type' => $expiringRecords->groupBy('trainingType.name')->map->count(),
            'days_notice' => $days
        ];

        foreach ($hrUsers as $user) {
            Mail::send('emails.certificate-expiry-hr', [
                'user' => $user,
                'summary' => $summary,
                'expiringRecords' => $expiringRecords
            ], function ($message) use ($user, $days) {
                $message->to($user->email, $user->name)
                        ->subject("Training Certificates Expiring in {$days} Days - HR Alert");
            });
        }
    }

    /**
     * Send compliance reminder to employee
     */
    private function sendComplianceReminderToEmployee(Employee $employee, array $nonCompliantTrainings): void
    {
        Mail::send('emails.compliance-reminder', [
            'employee' => $employee,
            'nonCompliantTrainings' => $nonCompliantTrainings
        ], function ($message) use ($employee) {
            $message->to($employee->email, $employee->name)
                    ->subject('Training Compliance Reminder - Action Required');
        });
    }

    /**
     * Get daily statistics
     */
    private function getDailyStats(): array
    {
        $today = Carbon::now();

        return [
            'total_employees' => Employee::where('is_active', true)->count(),
            'total_training_records' => TrainingRecord::count(),
            'expiring_today' => TrainingRecord::whereDate('expiry_date', $today)->count(),
            'expiring_this_week' => TrainingRecord::whereBetween('expiry_date', [
                $today, $today->copy()->addDays(7)
            ])->count(),
            'expiring_this_month' => TrainingRecord::whereBetween('expiry_date', [
                $today, $today->copy()->addDays(30)
            ])->count(),
            'expired_total' => TrainingRecord::where('expiry_date', '<', $today)->count(),
            'certificates_issued_today' => TrainingRecord::whereDate('created_at', $today)->count(),
            'new_employees_today' => Employee::whereDate('created_at', $today)->count(),
            'compliance_rate' => $this->calculateOverallComplianceRate(),
            'department_stats' => $this->getDepartmentStats(),
            'training_type_stats' => $this->getTrainingTypeStats()
        ];
    }

    /**
     * Calculate overall compliance rate
     */
    private function calculateOverallComplianceRate(): float
    {
        $mandatoryTrainingTypes = TrainingType::where('is_mandatory', true)->count();
        $activeEmployees = Employee::where('is_active', true)->count();

        if ($mandatoryTrainingTypes === 0 || $activeEmployees === 0) {
            return 0;
        }

        $totalRequiredRecords = $mandatoryTrainingTypes * $activeEmployees;
        $validRecords = TrainingRecord::whereHas('trainingType', function($query) {
                $query->where('is_mandatory', true);
            })
            ->where('expiry_date', '>', Carbon::now())
            ->count();

        return round(($validRecords / $totalRequiredRecords) * 100, 2);
    }

    /**
     * Get department statistics
     */
    private function getDepartmentStats(): array
    {
        return Employee::where('is_active', true)
            ->with(['trainingRecords' => function($query) {
                $query->where('expiry_date', '>', Carbon::now());
            }])
            ->get()
            ->groupBy('department')
            ->map(function($employees, $department) {
                return [
                    'department' => $department,
                    'total_employees' => $employees->count(),
                    'total_valid_trainings' => $employees->sum(function($employee) {
                        return $employee->trainingRecords->count();
                    }),
                    'avg_trainings_per_employee' => $employees->count() > 0 ?
                        round($employees->sum(function($employee) {
                            return $employee->trainingRecords->count();
                        }) / $employees->count(), 2) : 0
                ];
            })
            ->values()
            ->toArray();
    }

    /**
     * Get training type statistics
     */
    private function getTrainingTypeStats(): array
    {
        return TrainingType::withCount([
                'trainingRecords as total_records',
                'trainingRecords as valid_records' => function($query) {
                    $query->where('expiry_date', '>', Carbon::now());
                },
                'trainingRecords as expiring_records' => function($query) {
                    $query->whereBetween('expiry_date', [
                        Carbon::now(),
                        Carbon::now()->addDays(30)
                    ]);
                }
            ])
            ->get()
            ->map(function($trainingType) {
                return [
                    'name' => $trainingType->name,
                    'category' => $trainingType->category,
                    'is_mandatory' => $trainingType->is_mandatory,
                    'total_records' => $trainingType->total_records,
                    'valid_records' => $trainingType->valid_records,
                    'expiring_records' => $trainingType->expiring_records,
                    'compliance_rate' => $trainingType->total_records > 0 ?
                        round(($trainingType->valid_records / $trainingType->total_records) * 100, 2) : 0
                ];
            })
            ->toArray();
    }

    /**
     * Send custom notification
     */
    public function sendCustomNotification(array $employeeIds, string $subject, string $message): array
    {
        $employees = Employee::whereIn('id', $employeeIds)->get();
        $results = [
            'sent' => 0,
            'failed' => 0,
            'errors' => []
        ];

        foreach ($employees as $employee) {
            try {
                Mail::send('emails.custom-notification', [
                    'employee' => $employee,
                    'custom_message' => $message
                ], function ($mail) use ($employee, $subject) {
                    $mail->to($employee->email, $employee->name)
                         ->subject($subject);
                });

                $results['sent']++;
            } catch (\Exception $e) {
                $results['failed']++;
                $results['errors'][] = [
                    'employee_id' => $employee->id,
                    'error' => $e->getMessage()
                ];
            }
        }

        return $results;
    }

    /**
     * Get notification preferences for user
     */
    public function getNotificationPreferences(User $user): array
    {
        return [
            'email_enabled' => true, // Could be stored in user preferences
            'daily_digest' => $user->role !== 'staff',
            'expiry_notifications' => true,
            'compliance_reminders' => true,
            'new_training_alerts' => true
        ];
    }

    /**
     * Schedule automatic notifications
     */
    public function scheduleNotifications(): void
    {
        // This would be called by Laravel's task scheduler
        // Send daily digest every morning
        if (Carbon::now()->hour === 8) {
            $this->sendDailyDigest();
        }

        // Send expiry notifications every day at 9 AM
        if (Carbon::now()->hour === 9) {
            $this->sendExpiringCertificateNotifications(30);
        }

        // Send weekly compliance reminders on Monday
        if (Carbon::now()->isMonday() && Carbon::now()->hour === 10) {
            $this->sendComplianceReminders();
        }
    }
}
