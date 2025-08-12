<?php

namespace App\Services;

use App\Models\TrainingRecord;
use App\Models\TrainingType;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class CertificateGenerationService
{
    /**
     * Generate certificate for a training record
     */
    public function generateCertificate(TrainingRecord $trainingRecord): array
    {
        try {
            $employee = $trainingRecord->employee;
            $trainingType = $trainingRecord->trainingType;

            // Generate certificate number if not exists
            if (!$trainingRecord->certificate_number) {
                $trainingRecord->certificate_number = $this->generateCertificateNumber($trainingType, $trainingRecord);
                $trainingRecord->save();
            }

            // For now, just return success without generating actual PDF
            // TODO: Implement actual PDF generation later

            Log::info('Certificate generated for training record', [
                'training_record_id' => $trainingRecord->id,
                'certificate_number' => $trainingRecord->certificate_number,
                'employee' => $employee->nama_lengkap ?? $employee->name,
                'training_type' => $trainingType->name
            ]);

            return [
                'success' => true,
                'certificate_number' => $trainingRecord->certificate_number,
                'certificate_path' => 'certificates/' . $trainingRecord->certificate_number . '.pdf',
                'employee_name' => $employee->nama_lengkap ?? $employee->name,
                'training_type' => $trainingType->name,
                'issue_date' => $trainingRecord->issue_date ? $trainingRecord->issue_date->format('d F Y') : Carbon::now()->format('d F Y'),
                'expiry_date' => $trainingRecord->expiry_date ? $trainingRecord->expiry_date->format('d F Y') : Carbon::now()->addYear()->format('d F Y'),
            ];

        } catch (\Exception $e) {
            Log::error('Certificate generation failed', [
                'training_record_id' => $trainingRecord->id,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Generate unique certificate number
     */
    public function generateCertificateNumber(TrainingType $trainingType, TrainingRecord $trainingRecord): string
    {
        $year = Carbon::now()->format('Y');
        $month = Carbon::now()->format('m');

        // Get sequence number for this type and month
        $sequence = TrainingRecord::where('training_type_id', $trainingType->id)
            ->whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->whereNotNull('certificate_number')
            ->count() + 1;

        // Format: GLC/OPR-{sequence}/{month}/{year}
        // Example: GLC/OPR-001/08/2025
        return sprintf('GLC/%s-%03d/%02d/%s',
            strtoupper(substr($trainingType->name ?? 'TRN', 0, 3)),
            $sequence,
            $month,
            $year
        );
    }

    /**
     * Check if certificate is valid
     */
    public function isCertificateValid(TrainingRecord $trainingRecord): bool
    {
        if (!$trainingRecord->expiry_date) {
            return false;
        }

        return $trainingRecord->expiry_date > Carbon::now();
    }

    /**
     * Generate bulk certificates
     */
    public function generateBulkCertificates(array $trainingRecordIds): array
    {
        $successful = 0;
        $failed = 0;
        $errors = [];

        foreach ($trainingRecordIds as $id) {
            try {
                $trainingRecord = TrainingRecord::with(['employee', 'trainingType'])->find($id);

                if (!$trainingRecord) {
                    $failed++;
                    $errors[] = "Training record ID {$id} not found";
                    continue;
                }

                $result = $this->generateCertificate($trainingRecord);

                if ($result['success']) {
                    $successful++;
                } else {
                    $failed++;
                    $errors[] = "Failed to generate certificate for ID {$id}: " . ($result['error'] ?? 'Unknown error');
                }

            } catch (\Exception $e) {
                $failed++;
                $errors[] = "Exception for ID {$id}: " . $e->getMessage();
                Log::error('Bulk certificate generation error', [
                    'training_record_id' => $id,
                    'error' => $e->getMessage()
                ]);
            }
        }

        return [
            'successful' => $successful,
            'failed' => $failed,
            'errors' => $errors,
            'total' => count($trainingRecordIds)
        ];
    }
}
