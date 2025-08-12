<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\TrainingController;
use App\Http\Controllers\TrainingTypeController;
use App\Http\Controllers\TrainingDashboardController;
use App\Http\Controllers\TrainingReportController;
use App\Http\Controllers\BackgroundCheckController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\CertificateController;
use App\Http\Controllers\ExportController;
use App\Http\Controllers\ImportController;
use App\Http\Controllers\NotificationController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

/*
|--------------------------------------------------------------------------
| Web Routes - GAPURA ANGKASA Training Management System
|--------------------------------------------------------------------------
|
| Complete training management system with CRUD operations, auto certificate
| generation, notifications, export/import functionality, and compliance reporting.
|
*/

// ============================================================================
// PUBLIC ROUTES
// ============================================================================

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

// ============================================================================
// AUTHENTICATED ROUTES
// ============================================================================

Route::middleware(['auth', 'verified'])->group(function () {

    // ========================================================================
    // DASHBOARD
    // ========================================================================

    Route::get('/dashboard', [TrainingDashboardController::class, 'index'])->name('dashboard');

    // ========================================================================
    // EMPLOYEE MANAGEMENT ROUTES
    // ========================================================================

    // Core Employee CRUD
    Route::resource('employees', EmployeeController::class)->names([
        'index' => 'employees.index',
        'create' => 'employees.create',
        'store' => 'employees.store',
        'show' => 'employees.show',
        'edit' => 'employees.edit',
        'update' => 'employees.update',
        'destroy' => 'employees.destroy',
    ]);

    // Employee Additional Operations
    Route::prefix('employees')->name('employees.')->group(function () {
        // Training Management for Employees
        Route::post('{employee}/training', [EmployeeController::class, 'addTraining'])->name('add-training');
        Route::delete('{employee}/training/{trainingRecord}', [EmployeeController::class, 'removeTraining'])->name('remove-training');

        // Bulk Operations
        Route::post('bulk-update', [EmployeeController::class, 'bulkUpdate'])->name('bulk.update');

        // Export Operations
        Route::get('export', [EmployeeController::class, 'export'])->name('export');
        Route::post('export-selected', [EmployeeController::class, 'exportSelected'])->name('export.selected');
    });

    // ========================================================================
    // TRAINING MANAGEMENT ROUTES
    // ========================================================================

    // Core Training Records CRUD
    Route::resource('training', TrainingController::class)->names([
        'index' => 'training.index',
        'create' => 'training.create',
        'store' => 'training.store',
        'show' => 'training.show',
        'edit' => 'training.edit',
        'update' => 'training.update',
        'destroy' => 'training.destroy',
    ]);

    // Training Records - Additional Operations
    Route::prefix('training')->name('training.')->group(function () {

        // Certificate Management
        Route::get('{training}/certificate/download', [TrainingController::class, 'downloadCertificate'])
              ->name('certificate.download');
        Route::post('bulk-generate-certificates', [TrainingController::class, 'bulkGenerateCertificates'])
              ->name('bulk-generate-certificates');

        // Training Renewal & Extension
        Route::post('{training}/renew', [TrainingController::class, 'renew'])->name('renew');
        Route::post('{training}/extend', [TrainingController::class, 'extend'])->name('extend');

        // Bulk Operations
        Route::post('bulk-update', [TrainingController::class, 'bulkUpdate'])->name('bulk.update');
        Route::post('bulk-delete', [TrainingController::class, 'bulkDelete'])->name('bulk.delete');
        Route::post('bulk-extend', [TrainingController::class, 'bulkExtend'])->name('bulk.extend');

        // Export Operations
        Route::get('export', [TrainingController::class, 'export'])->name('export');
        Route::post('export-selected', [TrainingController::class, 'exportSelected'])->name('export.selected');
        Route::get('download-import-template', [TrainingController::class, 'downloadImportTemplate'])
              ->name('download-import-template');

        // Notification Operations
        Route::post('send-expiry-notifications', [TrainingController::class, 'sendExpiryNotifications'])
              ->name('send-expiry-notifications');
        Route::post('{training}/notify', [TrainingController::class, 'sendNotification'])->name('notify');
    });

    // ========================================================================
    // TRAINING TYPES MANAGEMENT
    // ========================================================================

    // Core Training Types CRUD
    Route::resource('training-types', TrainingTypeController::class)->names([
        'index' => 'training-types.index',
        'create' => 'training-types.create',
        'store' => 'training-types.store',
        'show' => 'training-types.show',
        'edit' => 'training-types.edit',
        'update' => 'training-types.update',
        'destroy' => 'training-types.destroy',
    ]);

    // Training Types - Additional Operations
    Route::prefix('training-types')->name('training-types.')->group(function () {
        Route::post('bulk-update', [TrainingTypeController::class, 'bulkUpdate'])->name('bulk.update');
        Route::post('{trainingType}/toggle-status', [TrainingTypeController::class, 'toggleStatus'])
              ->name('toggle.status');
        Route::get('{trainingType}/compliance-report', [TrainingTypeController::class, 'complianceReport'])
              ->name('compliance.report');
        Route::get('export', [TrainingTypeController::class, 'export'])->name('export');
    });

    // ========================================================================
    // CERTIFICATES MANAGEMENT
    // ========================================================================

    Route::prefix('certificates')->name('certificates.')->group(function () {
        Route::get('/', [CertificateController::class, 'index'])->name('index');
        Route::get('expiring', [CertificateController::class, 'expiring'])->name('expiring');
        Route::get('expired', [CertificateController::class, 'expired'])->name('expired');
        Route::get('valid', [CertificateController::class, 'valid'])->name('valid');

        // Certificate Operations
        Route::post('bulk-renew', [CertificateController::class, 'bulkRenew'])->name('bulk.renew');
        Route::post('bulk-generate', [CertificateController::class, 'bulkGenerate'])->name('bulk.generate');
        Route::get('{certificate}/download', [CertificateController::class, 'download'])->name('download');
        Route::get('{certificate}/verify', [CertificateController::class, 'verify'])->name('verify');

        // Export Operations
        Route::get('export', [CertificateController::class, 'export'])->name('export');
        Route::post('export-expiring', [CertificateController::class, 'exportExpiring'])->name('export.expiring');
        Route::post('export-expired', [CertificateController::class, 'exportExpired'])->name('export.expired');
    });

    // ========================================================================
    // BACKGROUND CHECKS MANAGEMENT
    // ========================================================================

    Route::resource('background-checks', BackgroundCheckController::class)->except(['show'])->names([
        'index' => 'background-checks.index',
        'create' => 'background-checks.create',
        'store' => 'background-checks.store',
        'edit' => 'background-checks.edit',
        'update' => 'background-checks.update',
        'destroy' => 'background-checks.destroy',
    ]);

    // Background Checks Additional Operations
    Route::prefix('background-checks')->name('background-checks.')->group(function () {
        Route::post('bulk-update', [BackgroundCheckController::class, 'bulkUpdate'])->name('bulk.update');
        Route::get('export', [BackgroundCheckController::class, 'export'])->name('export');
    });

    // ========================================================================
    // TRAINING DASHBOARD & ANALYTICS
    // ========================================================================

    Route::prefix('training')->name('training.')->group(function () {

        // Dashboard Pages
        Route::get('dashboard', [TrainingDashboardController::class, 'index'])->name('dashboard');
        Route::get('analytics', [TrainingDashboardController::class, 'analytics'])->name('analytics');
        Route::get('notifications', [TrainingDashboardController::class, 'notifications'])->name('notifications');

        // API Endpoints for Dashboard Charts & Data
        Route::prefix('api')->name('api.')->group(function () {
            Route::get('compliance-stats', [TrainingDashboardController::class, 'complianceStats'])
                  ->name('compliance');
            Route::get('expiry-trends', [TrainingDashboardController::class, 'expiryTrends'])
                  ->name('expiry-trends');
            Route::get('training-distribution', [TrainingDashboardController::class, 'trainingDistribution'])
                  ->name('distribution');
            Route::get('department-stats', [TrainingDashboardController::class, 'departmentStats'])
                  ->name('department-stats');
            Route::get('monthly-stats', [TrainingDashboardController::class, 'monthlyStats'])
                  ->name('monthly-stats');
        });
    });

    // ========================================================================
    // TRAINING REPORTS
    // ========================================================================

    Route::prefix('training/reports')->name('training.reports.')->group(function () {

        // Report Pages
        Route::get('/', [TrainingReportController::class, 'index'])->name('index');
        Route::get('compliance', [TrainingReportController::class, 'compliance'])->name('compliance');
        Route::get('expiry', [TrainingReportController::class, 'expiry'])->name('expiry');
        Route::get('employee/{employee}', [TrainingReportController::class, 'employee'])->name('employee');
        Route::get('department/{department}', [TrainingReportController::class, 'department'])
              ->name('department');
        Route::get('training-type/{trainingType}', [TrainingReportController::class, 'trainingType'])
              ->name('training-type');

        // Export Operations
        Route::prefix('export')->name('export.')->group(function () {
            Route::post('compliance', [TrainingReportController::class, 'exportCompliance'])->name('compliance');
            Route::post('expiry', [TrainingReportController::class, 'exportExpiry'])->name('expiry');
            Route::post('employee/{employee}', [TrainingReportController::class, 'exportEmployee'])->name('employee');
            Route::post('department/{department}', [TrainingReportController::class, 'exportDepartment'])->name('department');
            Route::post('training-type/{trainingType}', [TrainingReportController::class, 'exportTrainingType'])->name('training-type');
            Route::post('custom', [TrainingReportController::class, 'exportCustom'])->name('custom');
        });
    });

    // ========================================================================
    // IMPORT/EXPORT OPERATIONS
    // ========================================================================

    Route::prefix('import-export')->name('import-export.')->group(function () {

        // Import/Export Dashboard
        Route::get('/', [ImportController::class, 'index'])->name('index');

        // Import Operations
        Route::post('import-employees', [ImportController::class, 'importEmployees'])->name('import.employees');
        Route::post('import-training-records', [ImportController::class, 'importTrainingRecords'])->name('import.training-records');
        Route::post('import-training-types', [ImportController::class, 'importTrainingTypes'])->name('import.training-types');

        // Template Downloads
        Route::get('template/employees', [ImportController::class, 'employeeTemplate'])->name('template.employees');
        Route::get('template/training-records', [ImportController::class, 'trainingRecordsTemplate'])->name('template.training-records');
        Route::get('template/training-types', [ImportController::class, 'trainingTypesTemplate'])->name('template.training-types');

        // Export Operations
        Route::get('export/all-data', [ExportController::class, 'exportAllData'])->name('export.all-data');
        Route::get('export/employees', [ExportController::class, 'exportEmployees'])->name('export.employees');
        Route::get('export/training-records', [ExportController::class, 'exportTrainingRecords'])->name('export.training-records');
        Route::get('export/compliance-report', [ExportController::class, 'exportComplianceReport'])->name('export.compliance-report');

        // Validation & Preview
        Route::post('validate-import', [ImportController::class, 'validateImport'])->name('validate');
        Route::post('preview-import', [ImportController::class, 'previewImport'])->name('preview');
    });

    // ========================================================================
    // NOTIFICATION MANAGEMENT
    // ========================================================================

    Route::prefix('notifications')->name('notifications.')->group(function () {

        // Notification Dashboard
        Route::get('/', [NotificationController::class, 'index'])->name('index');
        Route::get('settings', [NotificationController::class, 'settings'])->name('settings');

        // Manual Notification Operations
        Route::post('send-expiry-alerts', [NotificationController::class, 'sendExpiryAlerts'])->name('send.expiry');
        Route::post('send-compliance-reminders', [NotificationController::class, 'sendComplianceReminders'])->name('send.compliance');
        Route::post('send-custom', [NotificationController::class, 'sendCustom'])->name('send.custom');
        Route::post('send-daily-digest', [NotificationController::class, 'sendDailyDigest'])->name('send.digest');

        // Notification History & Tracking
        Route::get('history', [NotificationController::class, 'history'])->name('history');
        Route::get('statistics', [NotificationController::class, 'statistics'])->name('statistics');

        // Notification Preferences
        Route::post('preferences', [NotificationController::class, 'updatePreferences'])->name('preferences.update');
        Route::get('preferences', [NotificationController::class, 'getPreferences'])->name('preferences.get');
    });

    // ========================================================================
    // SYSTEM SETTINGS & CONFIGURATION
    // ========================================================================

    Route::prefix('settings')->name('settings.')->group(function () {

        // System Settings
        Route::get('/', [SettingsController::class, 'index'])->name('index');
        Route::post('update', [SettingsController::class, 'update'])->name('update');

        // User Management
        Route::get('users', [SettingsController::class, 'users'])->name('users');
        Route::post('users', [SettingsController::class, 'createUser'])->name('users.create');
        Route::put('users/{user}', [SettingsController::class, 'updateUser'])->name('users.update');
        Route::delete('users/{user}', [SettingsController::class, 'deleteUser'])->name('users.delete');

        // System Maintenance
        Route::post('clear-cache', [SettingsController::class, 'clearCache'])->name('clear-cache');
        Route::post('regenerate-certificates', [SettingsController::class, 'regenerateCertificates'])->name('regenerate-certificates');
        Route::get('system-info', [SettingsController::class, 'systemInfo'])->name('system-info');

        // Backup & Restore
        Route::post('backup', [SettingsController::class, 'backup'])->name('backup');
        Route::post('restore', [SettingsController::class, 'restore'])->name('restore');
    });

    // ========================================================================
    // API ROUTES FOR FRONTEND
    // ========================================================================

    Route::prefix('api')->name('api.')->group(function () {

        // Search & Autocomplete
        Route::get('search/employees', [ApiController::class, 'searchEmployees'])->name('search.employees');
        Route::get('search/training-types', [ApiController::class, 'searchTrainingTypes'])->name('search.training-types');
        Route::get('search/certificates', [ApiController::class, 'searchCertificates'])->name('search.certificates');

        // Quick Stats
        Route::get('stats/dashboard', [ApiController::class, 'dashboardStats'])->name('stats.dashboard');
        Route::get('stats/employee/{employee}', [ApiController::class, 'employeeStats'])->name('stats.employee');
        Route::get('stats/department/{department}', [ApiController::class, 'departmentStats'])->name('stats.department');

        // Validation
        Route::post('validate/employee', [ApiController::class, 'validateEmployee'])->name('validate.employee');
        Route::post('validate/training-record', [ApiController::class, 'validateTrainingRecord'])->name('validate.training-record');
        Route::get('validate/certificate/{certificateNumber}', [ApiController::class, 'validateCertificate'])->name('validate.certificate');

        // Data Lists
        Route::get('departments', [ApiController::class, 'departments'])->name('departments');
        Route::get('positions', [ApiController::class, 'positions'])->name('positions');
        Route::get('training-providers', [ApiController::class, 'trainingProviders'])->name('training-providers');
    });

    // ========================================================================
    // QUICK ACTIONS & SHORTCUTS
    // ========================================================================

    Route::prefix('quick')->name('quick.')->group(function () {

        // Quick Employee Actions
        Route::post('add-employee', [QuickActionsController::class, 'addEmployee'])->name('add-employee');
        Route::post('add-training', [QuickActionsController::class, 'addTraining'])->name('add-training');
        Route::post('renew-certificate', [QuickActionsController::class, 'renewCertificate'])->name('renew-certificate');

        // Quick Bulk Actions
        Route::post('bulk-assign-training', [QuickActionsController::class, 'bulkAssignTraining'])->name('bulk-assign-training');
        Route::post('bulk-extend-certificates', [QuickActionsController::class, 'bulkExtendCertificates'])->name('bulk-extend-certificates');

        // Emergency Actions
        Route::post('emergency-notification', [QuickActionsController::class, 'emergencyNotification'])->name('emergency-notification');
        Route::post('suspend-employee', [QuickActionsController::class, 'suspendEmployee'])->name('suspend-employee');
    });

     Route::prefix('training')->name('training.')->group(function () {

        // Main pages
        Route::get('/', [App\Http\Controllers\TrainingController::class, 'index'])->name('index');
        Route::get('/employees', [App\Http\Controllers\TrainingController::class, 'employees'])->name('employees');
        Route::get('/dashboard', [App\Http\Controllers\TrainingController::class, 'dashboard'])->name('dashboard');

        // CRUD Training Records
        Route::get('/create', [App\Http\Controllers\TrainingController::class, 'create'])->name('create');
        Route::post('/', [App\Http\Controllers\TrainingController::class, 'store'])->name('store');
        Route::get('/{training}/edit', [App\Http\Controllers\TrainingController::class, 'edit'])->name('edit');
        Route::put('/{training}', [App\Http\Controllers\TrainingController::class, 'update'])->name('update');
        Route::delete('/{training}', [App\Http\Controllers\TrainingController::class, 'destroy'])->name('destroy');

        // Import/Export MPGA
        Route::get('/import', [App\Http\Controllers\TrainingController::class, 'importForm'])->name('import');
        Route::post('/import', [App\Http\Controllers\TrainingController::class, 'importData'])->name('import.process');
        Route::get('/export', [App\Http\Controllers\TrainingController::class, 'export'])->name('export');

        // API endpoints for AJAX
        Route::get('/api/statistics', [App\Http\Controllers\TrainingController::class, 'getStatistics'])->name('api.statistics');
        Route::get('/api/employee/{nip}/trainings', [App\Http\Controllers\TrainingController::class, 'getEmployeeTrainings'])->name('api.employee.trainings');
    });

    Route::prefix('employees')->name('employees.')->group(function () {
        Route::get('/', [App\Http\Controllers\EmployeeController::class, 'index'])->name('index');
        Route::get('/create', [App\Http\Controllers\EmployeeController::class, 'create'])->name('create');
        Route::post('/', [App\Http\Controllers\EmployeeController::class, 'store'])->name('store');
        Route::get('/{employee}/edit', [App\Http\Controllers\EmployeeController::class, 'edit'])->name('edit');
        Route::put('/{employee}', [App\Http\Controllers\EmployeeController::class, 'update'])->name('update');
        Route::delete('/{employee}', [App\Http\Controllers\EmployeeController::class, 'destroy'])->name('destroy');
        Route::get('/export', [App\Http\Controllers\EmployeeController::class, 'export'])->name('export');
    });

});

// ============================================================================
// PROFILE MANAGEMENT ROUTES
// ============================================================================

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// ============================================================================
// PUBLIC CERTIFICATE VERIFICATION
// ============================================================================

Route::prefix('verify')->name('verify.')->group(function () {
    Route::get('certificate/{certificateNumber}', [CertificateController::class, 'publicVerify'])->name('certificate');
    Route::get('employee/{nik}', [EmployeeController::class, 'publicProfile'])->name('employee');
});

// ============================================================================
// AUTHENTICATION ROUTES
// ============================================================================

require __DIR__.'/auth.php';
