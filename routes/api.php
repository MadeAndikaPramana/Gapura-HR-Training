<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TrainingController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\TrainingTypeController;
use App\Http\Controllers\TrainingRecordController;
use App\Http\Controllers\TrainingDashboardController;
use App\Http\Controllers\BackgroundCheckController;
use App\Http\Controllers\CertificateController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// GAPURA Training System API Routes
Route::prefix('v1')->middleware('auth:sanctum')->group(function () {

    // Training Records API
    Route::apiResource('training-records', TrainingRecordController::class);

    // Employees API
    Route::apiResource('employees', EmployeeController::class);

    // Training Types API
    Route::apiResource('training-types', TrainingTypeController::class);

    // Training Management API
    Route::prefix('training')->group(function () {
        Route::get('/', [TrainingController::class, 'index']);
        Route::post('/', [TrainingController::class, 'store']);
        Route::get('{training}', [TrainingController::class, 'show']);
        Route::put('{training}', [TrainingController::class, 'update']);
        Route::delete('{training}', [TrainingController::class, 'destroy']);

        // Training specific actions
        Route::post('{training}/certificate', [TrainingController::class, 'generateCertificate']);
        Route::get('{training}/certificate/download', [TrainingController::class, 'downloadCertificate']);
        Route::post('export', [TrainingController::class, 'export']);
        Route::post('send-expiry-notifications', [TrainingController::class, 'sendExpiryNotifications']);
    });

    // Dashboard & Statistics API
    Route::prefix('dashboard')->group(function () {
        Route::get('/', [TrainingDashboardController::class, 'index']);
        Route::get('stats', [TrainingDashboardController::class, 'getStats']);
        Route::get('compliance-stats', [TrainingDashboardController::class, 'complianceStats']);
        Route::get('expiry-trends', [TrainingDashboardController::class, 'expiryTrends']);
    });

    // Background Checks API
    Route::apiResource('background-checks', BackgroundCheckController::class);
});

// Public API Routes (No Authentication Required)
Route::prefix('public')->group(function () {

    // Public Certificate Verification
    Route::get('verify/certificate/{certificateNumber}', function($certificateNumber) {
        try {
            // Simple certificate verification logic
            $training = \App\Models\TrainingRecord::where('certificate_number', $certificateNumber)
                                                 ->with(['employee:id,nip,nama_lengkap', 'trainingType:id,name'])
                                                 ->first();

            if (!$training) {
                return response()->json([
                    'valid' => false,
                    'message' => 'Certificate not found'
                ], 404);
            }

            $isValid = $training->expiry_date > now();

            return response()->json([
                'valid' => $isValid,
                'certificate_number' => $training->certificate_number,
                'employee_name' => $training->employee->nama_lengkap,
                'training_type' => $training->trainingType->name,
                'issue_date' => $training->issue_date ? $training->issue_date->format('Y-m-d') : null,
                'expiry_date' => $training->expiry_date ? $training->expiry_date->format('Y-m-d') : null,
                'status' => $isValid ? 'Valid' : 'Expired',
                'message' => $isValid ? 'Certificate is valid' : 'Certificate has expired'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'valid' => false,
                'message' => 'Error verifying certificate'
            ], 500);
        }
    });

    // Public Employee Profile (Limited Data)
    Route::get('employee/{nip}/profile', function($nip) {
        try {
            $employee = \App\Models\Employee::where('nip', $nip)
                                          ->with(['trainingRecords' => function($query) {
                                              $query->where('expiry_date', '>', now())
                                                    ->with('trainingType:id,name');
                                          }])
                                          ->first(['id', 'nip', 'nama_lengkap', 'unit_organisasi', 'jabatan']);

            if (!$employee) {
                return response()->json([
                    'found' => false,
                    'message' => 'Employee not found'
                ], 404);
            }

            return response()->json([
                'found' => true,
                'employee' => [
                    'nip' => $employee->nip,
                    'name' => $employee->nama_lengkap,
                    'department' => $employee->unit_organisasi,
                    'position' => $employee->jabatan,
                    'valid_trainings_count' => $employee->trainingRecords->count(),
                    'trainings' => $employee->trainingRecords->map(function($training) {
                        return [
                            'type' => $training->trainingType->name,
                            'certificate_number' => $training->certificate_number,
                            'expiry_date' => $training->expiry_date->format('Y-m-d')
                        ];
                    })
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'found' => false,
                'message' => 'Error retrieving employee profile'
            ], 500);
        }
    });

    // System Health Check
    Route::get('health', function () {
        return response()->json([
            'status' => 'ok',
            'message' => 'GAPURA Training System API is running',
            'timestamp' => now()->toISOString(),
            'version' => '1.0.0',
            'database' => 'connected'
        ]);
    });

    // API Documentation endpoint
    Route::get('docs', function () {
        return response()->json([
            'name' => 'GAPURA Training System API',
            'version' => '1.0.0',
            'endpoints' => [
                'GET /api/public/health' => 'Health check',
                'GET /api/public/verify/certificate/{number}' => 'Verify certificate',
                'GET /api/public/employee/{nip}/profile' => 'Get employee profile',
                'GET /api/v1/training-records' => 'List training records (Auth required)',
                'GET /api/v1/employees' => 'List employees (Auth required)',
                'GET /api/v1/dashboard' => 'Dashboard data (Auth required)'
            ]
        ]);
    });
});
