<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\TrainingController;
use App\Http\Controllers\TrainingDashboardController;
use App\Http\Controllers\EmployeeController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

/*
|--------------------------------------------------------------------------
| Web Routes - GAPURA ANGKASA Training Management System
|--------------------------------------------------------------------------
| CLEAN VERSION - EMPLOYEE FOCUS
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
    // EMPLOYEE MANAGEMENT ROUTES - WORKING VERSION
    // ========================================================================

    // Employee CRUD - RESTful Resource
    Route::resource('employees', EmployeeController::class)->except(['show']);

    // Employee show with explicit route (to avoid conflicts)
    Route::get('/employees/{employee}', [EmployeeController::class, 'show'])->name('employees.show');

    // Employee additional operations
    Route::prefix('employees')->name('employees.')->group(function () {
        Route::get('/export', [EmployeeController::class, 'export'])->name('export');
        Route::get('/api/statistics', [EmployeeController::class, 'getStatistics'])->name('api.statistics');
    });

    // ========================================================================
    // TRAINING ROUTES (BASIC - FOR PHASE 2+)
    // ========================================================================

    Route::prefix('training')->name('training.')->group(function () {
        Route::get('/', [TrainingController::class, 'index'])->name('index');
        Route::get('/dashboard', [TrainingController::class, 'dashboard'])->name('dashboard');
        Route::get('/employees', [TrainingController::class, 'employees'])->name('employees');
    });

    // ========================================================================
    // PROFILE ROUTES
    // ========================================================================

    Route::prefix('profile')->name('profile.')->group(function () {
        Route::get('/', [ProfileController::class, 'edit'])->name('edit');
        Route::patch('/', [ProfileController::class, 'update'])->name('update');
        Route::delete('/', [ProfileController::class, 'destroy'])->name('destroy');
    });
});

// ============================================================================
// AUTHENTICATION ROUTES
// ============================================================================

require __DIR__.'/auth.php';
