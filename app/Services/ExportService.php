<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\TrainingRecord;
use App\Models\TrainingType;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class ExportService
{
    /**
     * Export all employees with their training data
     */
    public function exportAllEmployees(): string
    {
        $employees = Employee::with(['trainingRecords.trainingType'])
                           ->orderBy('name')
                           ->get();

        $spreadsheet = new Spreadsheet();
        $this->createEmployeesSheet($spreadsheet, $employees);
        $this->createTrainingRecordsSheet($spreadsheet, $employees);
        $this->createSummarySheet($spreadsheet, $employees);

        return $this->saveSpreadsheet($spreadsheet, 'all_employees_training_data');
    }

    /**
     * Export specific employees by IDs
     */
    public function exportEmployeesByIds(array $employeeIds): string
    {
        $employees = Employee::whereIn('id', $employeeIds)
                           ->with(['trainingRecords.trainingType'])
                           ->orderBy('name')
                           ->get();

        $spreadsheet = new Spreadsheet();
        $this->createEmployeesSheet($spreadsheet, $employees);
        $this->createTrainingRecordsSheet($spreadsheet, $employees);

        return $this->saveSpreadsheet($spreadsheet, 'selected_employees_training_data');
    }

    /**
     * Export single employee detailed report
     */
    public function exportSingleEmployee(Employee $employee): string
    {
        $employee->load(['trainingRecords.trainingType']);

        $spreadsheet = new Spreadsheet();
        $this->createSingleEmployeeSheet($spreadsheet, $employee);

        return $this->saveSpreadsheet($spreadsheet, 'employee_' . $employee->nik . '_training_report');
    }

    /**
     * Export training records by filters
     */
    public function exportTrainingRecords(array $filters = []): string
    {
        $query = TrainingRecord::with(['employee', 'trainingType']);

        // Apply filters
        if (!empty($filters['department'])) {
            $query->whereHas('employee', function($q) use ($filters) {
                $q->where('department', $filters['department']);
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
                    $query->whereBetween('expiry_date', [Carbon::now(), Carbon::now()->addDays(30)]);
                    break;
            }
        }

        if (!empty($filters['date_from'])) {
            $query->where('issue_date', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->where('issue_date', '<=', $filters['date_to']);
        }

        $trainingRecords = $query->orderBy('issue_date', 'desc')->get();

        $spreadsheet = new Spreadsheet();
        $this->createTrainingRecordsOnlySheet($spreadsheet, $trainingRecords);

        return $this->saveSpreadsheet($spreadsheet, 'training_records_report');
    }

    /**
     * Export compliance report
     */
    public function exportComplianceReport(): string
    {
        $employees = Employee::with(['trainingRecords.trainingType'])->get();
        $mandatoryTrainingTypes = TrainingType::where('is_mandatory', true)->get();

        $spreadsheet = new Spreadsheet();
        $this->createComplianceSheet($spreadsheet, $employees, $mandatoryTrainingTypes);

        return $this->saveSpreadsheet($spreadsheet, 'compliance_report');
    }

    /**
     * Create employees sheet
     */
    private function createEmployeesSheet(Spreadsheet $spreadsheet, Collection $employees)
    {
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Employees');

        // Headers
        $headers = [
            'A1' => 'NIK',
            'B1' => 'NIP',
            'C1' => 'Name',
            'D1' => 'Email',
            'E1' => 'Phone',
            'F1' => 'Department',
            'G1' => 'Position',
            'H1' => 'Hire Date',
            'I1' => 'Birth Date',
            'J1' => 'Status',
            'K1' => 'Total Trainings',
            'L1' => 'Valid Trainings',
            'M1' => 'Expired Trainings',
            'N1' => 'Expiring Soon',
            'O1' => 'Compliance Rate (%)'
        ];

        foreach ($headers as $cell => $header) {
            $sheet->setCellValue($cell, $header);
        }

        // Style headers
        $this->styleHeaders($sheet, 'A1:O1');

        // Data
        $row = 2;
        foreach ($employees as $employee) {
            $totalTrainings = $employee->trainingRecords->count();
            $validTrainings = $employee->trainingRecords->filter(function($record) {
                return $record->expiry_date > Carbon::now();
            })->count();
            $expiredTrainings = $employee->trainingRecords->filter(function($record) {
                return $record->expiry_date <= Carbon::now();
            })->count();
            $expiringSoon = $employee->trainingRecords->filter(function($record) {
                return $record->expiry_date <= Carbon::now()->addDays(30) &&
                       $record->expiry_date > Carbon::now();
            })->count();

            $complianceRate = $totalTrainings > 0 ? round(($validTrainings / $totalTrainings) * 100, 2) : 0;

            $sheet->setCellValue('A' . $row, $employee->nik);
            $sheet->setCellValue('B' . $row, $employee->nip);
            $sheet->setCellValue('C' . $row, $employee->name);
            $sheet->setCellValue('D' . $row, $employee->email);
            $sheet->setCellValue('E' . $row, $employee->phone);
            $sheet->setCellValue('F' . $row, $employee->department);
            $sheet->setCellValue('G' . $row, $employee->position);
            $sheet->setCellValue('H' . $row, $employee->hire_date ? $employee->hire_date->format('Y-m-d') : '');
            $sheet->setCellValue('I' . $row, $employee->birth_date ? $employee->birth_date->format('Y-m-d') : '');
            $sheet->setCellValue('J' . $row, $employee->is_active ? 'Active' : 'Inactive');
            $sheet->setCellValue('K' . $row, $totalTrainings);
            $sheet->setCellValue('L' . $row, $validTrainings);
            $sheet->setCellValue('M' . $row, $expiredTrainings);
            $sheet->setCellValue('N' . $row, $expiringSoon);
            $sheet->setCellValue('O' . $row, $complianceRate);

            $row++;
        }

        // Auto-size columns
        foreach (range('A', 'O') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }
    }

    /**
     * Create training records sheet
     */
    private function createTrainingRecordsSheet(Spreadsheet $spreadsheet, Collection $employees)
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('Training Records');

        // Headers
        $headers = [
            'A1' => 'Employee NIK',
            'B1' => 'Employee Name',
            'C1' => 'Department',
            'D1' => 'Training Type',
            'E1' => 'Training Category',
            'F1' => 'Certificate Number',
            'G1' => 'Issue Date',
            'H1' => 'Expiry Date',
            'I1' => 'Training Provider',
            'J1' => 'Cost',
            'K1' => 'Status',
            'L1' => 'Days Until Expiry',
            'M1' => 'Notes'
        ];

        foreach ($headers as $cell => $header) {
            $sheet->setCellValue($cell, $header);
        }

        // Style headers
        $this->styleHeaders($sheet, 'A1:M1');

        // Data
        $row = 2;
        foreach ($employees as $employee) {
            foreach ($employee->trainingRecords as $record) {
                $daysUntilExpiry = Carbon::now()->diffInDays($record->expiry_date, false);
                $status = $daysUntilExpiry < 0 ? 'Expired' :
                         ($daysUntilExpiry <= 30 ? 'Expiring Soon' : 'Valid');

                $sheet->setCellValue('A' . $row, $employee->nik);
                $sheet->setCellValue('B' . $row, $employee->name);
                $sheet->setCellValue('C' . $row, $employee->department);
                $sheet->setCellValue('D' . $row, $record->trainingType->name);
                $sheet->setCellValue('E' . $row, $record->trainingType->category);
                $sheet->setCellValue('F' . $row, $record->certificate_number);
                $sheet->setCellValue('G' . $row, $record->issue_date->format('Y-m-d'));
                $sheet->setCellValue('H' . $row, $record->expiry_date->format('Y-m-d'));
                $sheet->setCellValue('I' . $row, $record->training_provider);
                $sheet->setCellValue('J' . $row, $record->cost);
                $sheet->setCellValue('K' . $row, $status);
                $sheet->setCellValue('L' . $row, $daysUntilExpiry);
                $sheet->setCellValue('M' . $row, $record->notes);

                // Color code based on status
                if ($status === 'Expired') {
                    $sheet->getStyle('K' . $row)->getFill()
                          ->setFillType(Fill::FILL_SOLID)
                          ->getStartColor()->setRGB('FFEBEE');
                } elseif ($status === 'Expiring Soon') {
                    $sheet->getStyle('K' . $row)->getFill()
                          ->setFillType(Fill::FILL_SOLID)
                          ->getStartColor()->setRGB('FFF3E0');
                }

                $row++;
            }
        }

        // Auto-size columns
        foreach (range('A', 'M') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }
    }

    /**
     * Create summary sheet
     */
    private function createSummarySheet(Spreadsheet $spreadsheet, Collection $employees)
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('Summary');

        // Title
        $sheet->setCellValue('A1', 'GAPURA ANGKASA - Training Summary Report');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
        $sheet->mergeCells('A1:E1');

        // Generation info
        $sheet->setCellValue('A3', 'Generated on: ' . Carbon::now()->format('Y-m-d H:i:s'));
        $sheet->setCellValue('A4', 'Total Employees: ' . $employees->count());

        // Statistics
        $totalTrainings = $employees->sum(function($employee) {
            return $employee->trainingRecords->count();
        });

        $validTrainings = $employees->sum(function($employee) {
            return $employee->trainingRecords->filter(function($record) {
                return $record->expiry_date > Carbon::now();
            })->count();
        });

        $expiredTrainings = $employees->sum(function($employee) {
            return $employee->trainingRecords->filter(function($record) {
                return $record->expiry_date <= Carbon::now();
            })->count();
        });

        $sheet->setCellValue('A6', 'Training Statistics:');
        $sheet->getStyle('A6')->getFont()->setBold(true);

        $sheet->setCellValue('A7', 'Total Training Records: ' . $totalTrainings);
        $sheet->setCellValue('A8', 'Valid Training Records: ' . $validTrainings);
        $sheet->setCellValue('A9', 'Expired Training Records: ' . $expiredTrainings);

        // Department breakdown
        $departmentStats = $employees->groupBy('department')->map(function($group, $department) {
            return [
                'count' => $group->count(),
                'trainings' => $group->sum(function($employee) {
                    return $employee->trainingRecords->count();
                })
            ];
        });

        $sheet->setCellValue('A11', 'Department Breakdown:');
        $sheet->getStyle('A11')->getFont()->setBold(true);

        $row = 12;
        foreach ($departmentStats as $department => $stats) {
            $sheet->setCellValue('A' . $row, $department . ': ' . $stats['count'] . ' employees, ' . $stats['trainings'] . ' trainings');
            $row++;
        }
    }

    /**
     * Create single employee detailed sheet
     */
    private function createSingleEmployeeSheet(Spreadsheet $spreadsheet, Employee $employee)
    {
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Employee Details');

        // Employee Info
        $sheet->setCellValue('A1', 'EMPLOYEE TRAINING REPORT');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
        $sheet->mergeCells('A1:E1');

        $sheet->setCellValue('A3', 'Employee Information:');
        $sheet->getStyle('A3')->getFont()->setBold(true);

        $employeeInfo = [
            'A4' => 'Name: ' . $employee->name,
            'A5' => 'NIK: ' . $employee->nik,
            'A6' => 'NIP: ' . $employee->nip,
            'A7' => 'Email: ' . $employee->email,
            'A8' => 'Phone: ' . $employee->phone,
            'A9' => 'Department: ' . $employee->department,
            'A10' => 'Position: ' . $employee->position,
            'A11' => 'Hire Date: ' . ($employee->hire_date ? $employee->hire_date->format('Y-m-d') : 'N/A'),
        ];

        foreach ($employeeInfo as $cell => $info) {
            $sheet->setCellValue($cell, $info);
        }

        // Training Records
        $sheet->setCellValue('A13', 'Training Records:');
        $sheet->getStyle('A13')->getFont()->setBold(true);

        $headers = [
            'A14' => 'Training Type',
            'B14' => 'Certificate Number',
            'C14' => 'Issue Date',
            'D14' => 'Expiry Date',
            'E14' => 'Status',
            'F14' => 'Provider',
            'G14' => 'Cost',
            'H14' => 'Notes'
        ];

        foreach ($headers as $cell => $header) {
            $sheet->setCellValue($cell, $header);
        }

        $this->styleHeaders($sheet, 'A14:H14');

        $row = 15;
        foreach ($employee->trainingRecords->sortBy('expiry_date') as $record) {
            $daysUntilExpiry = Carbon::now()->diffInDays($record->expiry_date, false);
            $status = $daysUntilExpiry < 0 ? 'Expired' :
                     ($daysUntilExpiry <= 30 ? 'Expiring Soon' : 'Valid');

            $sheet->setCellValue('A' . $row, $record->trainingType->name);
            $sheet->setCellValue('B' . $row, $record->certificate_number);
            $sheet->setCellValue('C' . $row, $record->issue_date->format('Y-m-d'));
            $sheet->setCellValue('D' . $row, $record->expiry_date->format('Y-m-d'));
            $sheet->setCellValue('E' . $row, $status);
            $sheet->setCellValue('F' . $row, $record->training_provider);
            $sheet->setCellValue('G' . $row, $record->cost);
            $sheet->setCellValue('H' . $row, $record->notes);

            $row++;
        }

        // Auto-size columns
        foreach (range('A', 'H') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }
    }

    /**
     * Create training records only sheet
     */
    private function createTrainingRecordsOnlySheet(Spreadsheet $spreadsheet, Collection $trainingRecords)
    {
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Training Records');

        // Headers
        $headers = [
            'A1' => 'Employee NIK',
            'B1' => 'Employee Name',
            'C1' => 'Department',
            'D1' => 'Training Type',
            'E1' => 'Certificate Number',
            'F1' => 'Issue Date',
            'G1' => 'Expiry Date',
            'H1' => 'Status',
            'I1' => 'Provider',
            'J1' => 'Cost',
            'K1' => 'Notes'
        ];

        foreach ($headers as $cell => $header) {
            $sheet->setCellValue($cell, $header);
        }

        $this->styleHeaders($sheet, 'A1:K1');

        // Data
        $row = 2;
        foreach ($trainingRecords as $record) {
            $daysUntilExpiry = Carbon::now()->diffInDays($record->expiry_date, false);
            $status = $daysUntilExpiry < 0 ? 'Expired' :
                     ($daysUntilExpiry <= 30 ? 'Expiring Soon' : 'Valid');

            $sheet->setCellValue('A' . $row, $record->employee->nik);
            $sheet->setCellValue('B' . $row, $record->employee->name);
            $sheet->setCellValue('C' . $row, $record->employee->department);
            $sheet->setCellValue('D' . $row, $record->trainingType->name);
            $sheet->setCellValue('E' . $row, $record->certificate_number);
            $sheet->setCellValue('F' . $row, $record->issue_date->format('Y-m-d'));
            $sheet->setCellValue('G' . $row, $record->expiry_date->format('Y-m-d'));
            $sheet->setCellValue('H' . $row, $status);
            $sheet->setCellValue('I' . $row, $record->training_provider);
            $sheet->setCellValue('J' . $row, $record->cost);
            $sheet->setCellValue('K' . $row, $record->notes);

            $row++;
        }

        // Auto-size columns
        foreach (range('A', 'K') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }
    }

    /**
     * Create compliance sheet
     */
    private function createComplianceSheet(Spreadsheet $spreadsheet, Collection $employees, Collection $mandatoryTrainingTypes)
    {
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Compliance Report');

        // Headers
        $headers = ['A1' => 'NIK', 'B1' => 'Name', 'C1' => 'Department'];
        $col = 'D';
        foreach ($mandatoryTrainingTypes as $trainingType) {
            $headers[$col . '1'] = $trainingType->name;
            $col++;
        }
        $headers[$col . '1'] = 'Compliance Rate (%)';

        foreach ($headers as $cell => $header) {
            $sheet->setCellValue($cell, $header);
        }

        $this->styleHeaders($sheet, 'A1:' . $col . '1');

        // Data
        $row = 2;
        foreach ($employees as $employee) {
            $sheet->setCellValue('A' . $row, $employee->nik);
            $sheet->setCellValue('B' . $row, $employee->name);
            $sheet->setCellValue('C' . $row, $employee->department);

            $compliantCount = 0;
            $col = 'D';

            foreach ($mandatoryTrainingTypes as $trainingType) {
                $validRecord = $employee->trainingRecords
                    ->where('training_type_id', $trainingType->id)
                    ->filter(function($record) {
                        return $record->expiry_date > Carbon::now();
                    })
                    ->first();

                if ($validRecord) {
                    $sheet->setCellValue($col . $row, 'Compliant');
                    $sheet->getStyle($col . $row)->getFill()
                          ->setFillType(Fill::FILL_SOLID)
                          ->getStartColor()->setRGB('E8F5E8');
                    $compliantCount++;
                } else {
                    $sheet->setCellValue($col . $row, 'Non-Compliant');
                    $sheet->getStyle($col . $row)->getFill()
                          ->setFillType(Fill::FILL_SOLID)
                          ->getStartColor()->setRGB('FFEBEE');
                }
                $col++;
            }

            $complianceRate = round(($compliantCount / $mandatoryTrainingTypes->count()) * 100, 2);
            $sheet->setCellValue($col . $row, $complianceRate);

            $row++;
        }

        // Auto-size columns
        foreach (range('A', $col) as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }
    }

    /**
     * Style headers
     */
    private function styleHeaders($sheet, $range)
    {
        $style = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '439454'] // GAPURA green
            ],
            'borders' => [
                'allBorders' => ['borderStyle' => Border::BORDER_THIN]
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER
            ]
        ];

        $sheet->getStyle($range)->applyFromArray($style);
    }

    /**
     * Save spreadsheet to storage
     */
    private function saveSpreadsheet(Spreadsheet $spreadsheet, string $filename): string
    {
        $writer = new Xlsx($spreadsheet);
        $timestamp = Carbon::now()->format('Y-m-d_H-i-s');
        $filename = $filename . '_' . $timestamp . '.xlsx';
        $path = storage_path('app/public/exports/' . $filename);

        // Ensure directory exists
        if (!file_exists(dirname($path))) {
            mkdir(dirname($path), 0755, true);
        }

        $writer->save($path);

        return 'exports/' . $filename;
    }

    /**
     * Get export template for imports
     */
    public function getImportTemplate(): string
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Employee Training Template');

        // Headers
        $headers = [
            'A1' => 'NIK',
            'B1' => 'NIP',
            'C1' => 'Name',
            'D1' => 'Email',
            'E1' => 'Phone',
            'F1' => 'Department',
            'G1' => 'Position',
            'H1' => 'Training Type',
            'I1' => 'Issue Date (YYYY-MM-DD)',
            'J1' => 'Expiry Date (YYYY-MM-DD)',
            'K1' => 'Training Provider',
            'L1' => 'Cost',
            'M1' => 'Notes'
        ];

        foreach ($headers as $cell => $header) {
            $sheet->setCellValue($cell, $header);
        }

        $this->styleHeaders($sheet, 'A1:M1');

        // Sample data
        $sheet->setCellValue('A2', '1234567890123456');
        $sheet->setCellValue('B2', 'GAP/2025/001');
        $sheet->setCellValue('C2', 'John Doe');
        $sheet->setCellValue('D2', 'john.doe@gapura.com');
        $sheet->setCellValue('E2', '081234567890');
        $sheet->setCellValue('F2', 'Ground Operations');
        $sheet->setCellValue('G2', 'Ground Handler');
        $sheet->setCellValue('H2', 'Ground Safety Training');
        $sheet->setCellValue('I2', '2025-01-01');
        $sheet->setCellValue('J2', '2026-01-01');
        $sheet->setCellValue('K2', 'GAPURA Training Center');
        $sheet->setCellValue('L2', '500000');
        $sheet->setCellValue('M2', 'Initial training completed');

        // Auto-size columns
        foreach (range('A', 'M') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        return $this->saveSpreadsheet($spreadsheet, 'import_template');
    }
}
