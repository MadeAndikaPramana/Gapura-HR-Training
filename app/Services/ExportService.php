<?php

namespace App\Services;

use App\Models\TrainingRecord;
use App\Models\Employee;
use App\Models\TrainingType;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ExportService
{
    /**
     * Export training records based on filters
     */
    public function exportTrainingRecords(array $filters = []): string
    {
        try {
            $query = TrainingRecord::with(['employee', 'trainingType']);

            // Apply filters
            if (!empty($filters['department'])) {
                $query->whereHas('employee', function($q) use ($filters) {
                    $q->where('unit_organisasi', $filters['department']);
                });
            }

            if (!empty($filters['training_type'])) {
                $query->where('training_type_id', $filters['training_type']);
            }

            if (!empty($filters['status'])) {
                switch ($filters['status']) {
                    case 'valid':
                        $query->where('expiry_date', '>', Carbon::now());
                        break;
                    case 'expired':
                        $query->where('expiry_date', '<=', Carbon::now());
                        break;
                    case 'expiring':
                        $query->where('expiry_date', '>', Carbon::now())
                              ->where('expiry_date', '<=', Carbon::now()->addDays(30));
                        break;
                }
            }

            if (!empty($filters['date_from'])) {
                $query->where('issue_date', '>=', $filters['date_from']);
            }

            if (!empty($filters['date_to'])) {
                $query->where('issue_date', '<=', $filters['date_to']);
            }

            $trainingRecords = $query->get();

            return $this->generateCSVFile($trainingRecords, 'training_records_export');

        } catch (\Exception $e) {
            Log::error('Export training records failed', [
                'filters' => $filters,
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    /**
     * Export specific training records
     */
    public function exportTrainingRecordsOnly(Collection $trainingRecords): string
    {
        try {
            return $this->generateCSVFile($trainingRecords, 'selected_training_records');

        } catch (\Exception $e) {
            Log::error('Export selected training records failed', [
                'count' => $trainingRecords->count(),
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    /**
     * Get import template file
     */
    public function getImportTemplate(): string
    {
        try {
            $templateData = collect([
                [
                    'employee_nip' => '12345',
                    'training_type_name' => 'Aviation Safety Training',
                    'issue_date' => '2024-08-01',
                    'expiry_date' => '2025-08-01',
                    'certificate_number' => 'GLC/AVS-001/08/2024',
                    'training_provider' => 'Training Provider Inc.',
                    'cost' => '1500000',
                    'completion_status' => 'COMPLETED',
                    'notes' => 'Training completed successfully'
                ]
            ]);

            return $this->generateCSVFile($templateData, 'training_import_template');

        } catch (\Exception $e) {
            Log::error('Generate import template failed', [
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    /**
     * Generate CSV file from data
     */
    private function generateCSVFile(Collection $data, string $filename): string
    {
        try {
            $timestamp = Carbon::now()->format('Y-m-d_H-i-s');
            $fileName = "{$filename}_{$timestamp}.csv";
            $filePath = "exports/{$fileName}";

            // Ensure exports directory exists
            Storage::disk('public')->makeDirectory('exports');

            // Prepare CSV content
            $csvContent = '';

            if ($data->isNotEmpty()) {
                $firstItem = $data->first();

                // Determine headers based on data type
                if ($firstItem instanceof TrainingRecord) {
                    $headers = [
                        'Employee NIP',
                        'Employee Name',
                        'Department',
                        'Training Type',
                        'Certificate Number',
                        'Issue Date',
                        'Expiry Date',
                        'Status',
                        'Training Provider',
                        'Cost',
                        'Notes'
                    ];

                    $csvContent = implode(',', $headers) . "\n";

                    foreach ($data as $record) {
                        $status = $record->expiry_date > Carbon::now() ? 'Valid' : 'Expired';

                        $row = [
                            $record->employee->nip ?? '',
                            '"' . ($record->employee->nama_lengkap ?? $record->employee->name ?? '') . '"',
                            '"' . ($record->employee->unit_organisasi ?? '') . '"',
                            '"' . ($record->trainingType->name ?? '') . '"',
                            $record->certificate_number ?? '',
                            $record->issue_date ? $record->issue_date->format('Y-m-d') : '',
                            $record->expiry_date ? $record->expiry_date->format('Y-m-d') : '',
                            $status,
                            '"' . ($record->training_provider ?? '') . '"',
                            $record->cost ?? '0',
                            '"' . ($record->notes ?? '') . '"'
                        ];

                        $csvContent .= implode(',', $row) . "\n";
                    }
                } else {
                    // Handle array data (like template)
                    $headers = array_keys($firstItem);
                    $csvContent = implode(',', $headers) . "\n";

                    foreach ($data as $item) {
                        $row = array_map(function($value) {
                            return '"' . $value . '"';
                        }, array_values($item));

                        $csvContent .= implode(',', $row) . "\n";
                    }
                }
            } else {
                $csvContent = "No data available\n";
            }

            // Save file
            Storage::disk('public')->put($filePath, $csvContent);

            Log::info('CSV file generated successfully', [
                'filename' => $fileName,
                'records_count' => $data->count()
            ]);

            return $filePath;

        } catch (\Exception $e) {
            Log::error('CSV file generation failed', [
                'filename' => $filename,
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    /**
     * Export employees data
     */
    public function exportEmployees(array $filters = []): string
    {
        try {
            $query = Employee::query();

            // Apply filters
            if (!empty($filters['department'])) {
                $query->where('unit_organisasi', $filters['department']);
            }

            if (!empty($filters['status'])) {
                $query->where('status_kerja', $filters['status']);
            }

            $employees = $query->get();

            return $this->generateEmployeeCSV($employees);

        } catch (\Exception $e) {
            Log::error('Export employees failed', [
                'filters' => $filters,
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    /**
     * Generate employee CSV file
     */
    private function generateEmployeeCSV(Collection $employees): string
    {
        try {
            $timestamp = Carbon::now()->format('Y-m-d_H-i-s');
            $fileName = "employees_export_{$timestamp}.csv";
            $filePath = "exports/{$fileName}";

            Storage::disk('public')->makeDirectory('exports');

            $headers = ['NIP', 'Name', 'Department', 'Position', 'Status', 'Join Date'];
            $csvContent = implode(',', $headers) . "\n";

            foreach ($employees as $employee) {
                $row = [
                    $employee->nip ?? '',
                    '"' . ($employee->nama_lengkap ?? $employee->name ?? '') . '"',
                    '"' . ($employee->unit_organisasi ?? '') . '"',
                    '"' . ($employee->jabatan ?? '') . '"',
                    $employee->status_kerja ?? '',
                    $employee->tanggal_masuk ? Carbon::parse($employee->tanggal_masuk)->format('Y-m-d') : ''
                ];

                $csvContent .= implode(',', $row) . "\n";
            }

            Storage::disk('public')->put($filePath, $csvContent);

            Log::info('Employee CSV file generated successfully', [
                'filename' => $fileName,
                'employees_count' => $employees->count()
            ]);

            return $filePath;

        } catch (\Exception $e) {
            Log::error('Employee CSV file generation failed', [
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }
}
