<?php

namespace App\Providers;

use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],

        // Training System Events
        'App\Events\TrainingRecordCreated' => [
            'App\Listeners\SendTrainingNotification',
            'App\Listeners\LogTrainingActivity',
        ],

        'App\Events\TrainingRecordUpdated' => [
            'App\Listeners\LogTrainingActivity',
        ],

        'App\Events\CertificateGenerated' => [
            'App\Listeners\SendCertificateNotification',
            'App\Listeners\LogCertificateActivity',
        ],

        'App\Events\CertificateExpiring' => [
            'App\Listeners\SendExpiryNotification',
        ],

        'App\Events\CertificateExpired' => [
            'App\Listeners\SendExpiredNotification',
            'App\Listeners\UpdateComplianceStatus',
        ],

        'App\Events\EmployeeCreated' => [
            'App\Listeners\CheckMandatoryTrainings',
            'App\Listeners\LogEmployeeActivity',
        ],

        'App\Events\EmployeeUpdated' => [
            'App\Listeners\LogEmployeeActivity',
        ],

        'App\Events\UserLoggedIn' => [
            'App\Listeners\LogUserActivity',
        ],

        'App\Events\UserLoggedOut' => [
            'App\Listeners\LogUserActivity',
        ],

        'App\Events\ComplianceCheckFailed' => [
            'App\Listeners\SendComplianceAlert',
            'App\Listeners\LogComplianceIssue',
        ],

        'App\Events\BulkImportCompleted' => [
            'App\Listeners\SendImportSummary',
            'App\Listeners\LogImportActivity',
        ],
    ];

    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        // Register training system events manually if needed
        Event::listen(
            'training.record.created',
            function ($trainingRecord) {
                // Log training record creation
                logger()->info('Training record created', [
                    'training_record_id' => $trainingRecord->id,
                    'employee_id' => $trainingRecord->employee_id,
                    'training_type_id' => $trainingRecord->training_type_id,
                ]);
            }
        );

        Event::listen(
            'certificate.generated',
            function ($certificate) {
                // Log certificate generation
                logger()->info('Certificate generated', [
                    'certificate_number' => $certificate['certificate_number'],
                    'training_record_id' => $certificate['training_record_id'] ?? null,
                ]);
            }
        );

        Event::listen(
            'user.login',
            function ($user) {
                // Log user login
                logger()->info('User logged in', [
                    'user_id' => $user->id,
                    'user_email' => $user->email,
                    'user_role' => $user->role,
                    'timestamp' => now(),
                ]);
            }
        );

        Event::listen(
            'compliance.check',
            function ($employee, $results) {
                // Log compliance check results
                logger()->info('Compliance check performed', [
                    'employee_id' => $employee->id,
                    'compliance_rate' => $results['compliance_rate'] ?? 0,
                    'missing_trainings' => $results['missing_trainings'] ?? [],
                ]);
            }
        );
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     */
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
