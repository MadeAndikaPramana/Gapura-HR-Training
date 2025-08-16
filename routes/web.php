<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\TrainingController;
use App\Http\Controllers\TrainingTypeController;
use App\Http\Controllers\TrainingDashboardController;
use App\Http\Controllers\EmployeeController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

/*
|--------------------------------------------------------------------------
| Web Routes - GAPURA ANGKASA Training Management System
|--------------------------------------------------------------------------
| CLEANED VERSION - Phase 1 Focus: Employee CRUD
| No conflicts, consistent naming, proper structure
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
    // PHASE 1: EMPLOYEE MANAGEMENT ROUTES (MAIN FOCUS)
    // ========================================================================

    // Core Employee CRUD - RESTful Resource Routes
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
        // Search & Filter
        Route::get('search', [EmployeeController::class, 'search'])->name('search');

        // Bulk Operations
        Route::post('bulk-update', [EmployeeController::class, 'bulkUpdate'])->name('bulk.update');
        Route::post('bulk-delete', [EmployeeController::class, 'bulkDelete'])->name('bulk.delete');

        // Import/Export Operations
        Route::get('export', [EmployeeController::class, 'export'])->name('export');
        Route::post('import', [EmployeeController::class, 'import'])->name('import');
        Route::get('import-template', [EmployeeController::class, 'downloadTemplate'])->name('import.template');

        // Statistics API for AJAX calls
        Route::get('api/statistics', [EmployeeController::class, 'getStatistics'])->name('api.statistics');
        Route::get('api/departments', [EmployeeController::class, 'getDepartments'])->name('api.departments');
    });

    // ========================================================================
    // PHASE 2+: TRAINING MANAGEMENT ROUTES (FUTURE)
    // ========================================================================

    Route::prefix('training')->name('training.')->group(function () {
        // Training Dashboard & Main Pages
        Route::get('/', [TrainingController::class, 'index'])->name('index');
        Route::get('/dashboard', [TrainingController::class, 'dashboard'])->name('dashboard');
        Route::get('/employees', [TrainingController::class, 'employees'])->name('employees');

        // Training Records CRUD (Phase 2)
        Route::get('/records', [TrainingController::class, 'records'])->name('records');
        Route::get('/records/create', [TrainingController::class, 'createRecord'])->name('records.create');
        Route::post('/records', [TrainingController::class, 'storeRecord'])->name('records.store');
        Route::get('/records/{record}/edit', [TrainingController::class, 'editRecord'])->name('records.edit');
        Route::put('/records/{record}', [TrainingController::class, 'updateRecord'])->name('records.update');
        Route::delete('/records/{record}', [TrainingController::class, 'destroyRecord'])->name('records.destroy');

        // Import/Export Training Data (Phase 5)
        Route::get('/import', [TrainingController::class, 'importForm'])->name('import');
        Route::post('/import', [TrainingController::class, 'importData'])->name('import.process');
        Route::get('/export', [TrainingController::class, 'export'])->name('export');
    });

    // Training Types Management (Phase 3)
    Route::prefix('training-types')->name('training-types.')->group(function () {
        Route::get('/', [TrainingTypeController::class, 'index'])->name('index');
        Route::get('/create', [TrainingTypeController::class, 'create'])->name('create');
        Route::post('/', [TrainingTypeController::class, 'store'])->name('store');
        Route::get('/{trainingType}/edit', [TrainingTypeController::class, 'edit'])->name('edit');
        Route::put('/{trainingType}', [TrainingTypeController::class, 'update'])->name('update');
        Route::delete('/{trainingType}', [TrainingTypeController::class, 'destroy'])->name('destroy');
    });

    // Certificates Management (Phase 4)
    Route::prefix('certificates')->name('certificates.')->group(function () {
        Route::get('/', [TrainingController::class, 'certificates'])->name('index');
        Route::get('/{certificate}/download', [TrainingController::class, 'downloadCertificate'])->name('download');
    });

    // ========================================================================
    // SYSTEM ROUTES
    // ========================================================================

    // Profile Management
    Route::prefix('profile')->name('profile.')->group(function () {
        Route::get('/', [ProfileController::class, 'edit'])->name('edit');
        Route::patch('/', [ProfileController::class, 'update'])->name('update');
        Route::delete('/', [ProfileController::class, 'destroy'])->name('destroy');
    });
});

// ============================================================================
// PUBLIC VERIFICATION ROUTES (untuk akses certificate verification)
// ============================================================================

Route::prefix('verify')->name('verify.')->group(function () {
    Route::get('employee/{nip}', [EmployeeController::class, 'publicProfile'])->name('employee');
    Route::get('certificate/{certificate}', [TrainingController::class, 'verifyCertificate'])->name('certificate');
});

// ============================================================================
// AUTHENTICATION ROUTES
// ============================================================================

require __DIR__.'/auth.php';
