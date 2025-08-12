<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\TrainingController;
use App\Http\Controllers\TrainingTypeController;
use App\Http\Controllers\TrainingDashboardController;
use App\Http\Controllers\TrainingReportController;
use App\Http\Controllers\BackgroundCheckController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

/*
|--------------------------------------------------------------------------
| Web Routes - GAPURA ANGKASA Training Management System
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group.
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

    // Dashboard
    Route::get('/dashboard', function () {
        return Inertia::render('Dashboard');
    })->name('dashboard');

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

        // File Management
        Route::get('{training}/certificate/download', [TrainingController::class, 'downloadCertificate'])
              ->name('certificate.download');
        Route::get('{training}/documents/{document}/download', [TrainingController::class, 'downloadDocument'])
              ->name('document.download');

        // Bulk Operations
        Route::post('bulk-update', [TrainingController::class, 'bulkUpdate'])->name('bulk.update');
        Route::post('bulk-delete', [TrainingController::class, 'bulkDelete'])->name('bulk.delete');
        Route::post('bulk-extend', [TrainingController::class, 'bulkExtend'])->name('bulk.extend');

        // Import/Export Operations
        Route::get('export', [TrainingController::class, 'export'])->name('export');
        Route::post('import', [TrainingController::class, 'import'])->name('import');
        Route::get('import-template', [TrainingController::class, 'downloadImportTemplate'])
              ->name('import.template');

        // Training Management Actions
        Route::post('{training}/renew', [TrainingController::class, 'renew'])->name('renew');
        Route::post('{training}/extend', [TrainingController::class, 'extend'])->name('extend');
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
    });

    // ========================================================================
    // CERTIFICATES MANAGEMENT
    // ========================================================================

    Route::prefix('certificates')->name('certificates.')->group(function () {
        Route::get('/', [TrainingController::class, 'certificates'])->name('index');
        Route::get('expiring', [TrainingController::class, 'expiringCertificates'])->name('expiring');
        Route::get('expired', [TrainingController::class, 'expiredCertificates'])->name('expired');
        Route::post('bulk-renew', [TrainingController::class, 'bulkRenew'])->name('bulk.renew');
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

        // Export Operations
        Route::prefix('export')->name('export.')->group(function () {
            Route::post('compliance', [TrainingReportController::class, 'exportCompliance'])->name('compliance');
            Route::post('expiry', [TrainingReportController::class, 'exportExpiry'])->name('expiry');
            Route::post('employee/{employee}', [TrainingReportController::class, 'exportEmployee'])->name('employee');
            Route::post('department/{department}', [TrainingReportController::class, 'exportDepartment'])->name('department');
        });
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
// AUTHENTICATION ROUTES
// ============================================================================

require __DIR__.'/auth.php';
