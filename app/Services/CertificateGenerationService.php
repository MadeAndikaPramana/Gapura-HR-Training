<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\TrainingType;
use App\Models\TrainingRecord;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image;
use TCPDF;

class CertificateGenerationService
{
    /**
     * Generate certificate for a training record
     */
    public function generateCertificate(TrainingRecord $trainingRecord): array
    {
        $employee = $trainingRecord->employee;
        $trainingType = $trainingRecord->trainingType;

        // Generate certificate number if not exists
        if (!$trainingRecord->certificate_number) {
            $trainingRecord->certificate_number = $this->generateCertificateNumber($trainingType, $trainingRecord);
            $trainingRecord->save();
        }

        // Create certificate PDF
        $certificatePath = $this->createCertificatePDF($trainingRecord);

        return [
            'success' => true,
            'certificate_number' => $trainingRecord->certificate_number,
            'certificate_path' => $certificatePath,
            'employee_name' => $employee->name,
            'training_type' => $trainingType->name,
            'issue_date' => $trainingRecord->issue_date->format('d F Y'),
            'expiry_date' => $trainingRecord->expiry_date->format('d F Y'),
        ];
    }

    /**
     * Generate unique certificate number
     */
    public function generateCertificateNumber(TrainingType $trainingType, TrainingRecord $trainingRecord): string
    {
        $year = $trainingRecord->issue_date->format('Y');
        $month = $trainingRecord->issue_date->format('m');

        // Get sequence number for this type and month
        $sequence = TrainingRecord::where('training_type_id', $trainingType->id)
            ->whereYear('issue_date', $year)
            ->whereMonth('issue_date', $month)
            ->whereNotNull('certificate_number')
            ->count() + 1;

        // Format: GLC/OPR-{sequence}/{month}/{year}
        // Example: GLC/OPR-001/08/2025
        return sprintf('GLC/OPR-%03d/%02d/%s', $sequence, $month, $year);
    }

    /**
     * Create certificate PDF
     */
    private function createCertificatePDF(TrainingRecord $trainingRecord): string
    {
        $employee = $trainingRecord->employee;
        $trainingType = $trainingRecord->trainingType;

        // Create new PDF document
        $pdf = new TCPDF('L', 'mm', 'A4', true, 'UTF-8', false);

        // Set document information
        $pdf->SetCreator('GAPURA ANGKASA Training System');
        $pdf->SetAuthor('PT Gapura Angkasa');
        $pdf->SetTitle('Training Certificate - ' . $employee->name);
        $pdf->SetSubject($trainingType->name . ' Certificate');

        // Remove default header/footer
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);

        // Set margins
        $pdf->SetMargins(20, 20, 20);

        // Add a page
        $pdf->AddPage();

        // Create certificate content
        $this->addCertificateContent($pdf, $trainingRecord);

        // Define file path
        $fileName = 'certificate_' . $trainingRecord->certificate_number . '_' . time() . '.pdf';
        $filePath = 'certificates/' . $fileName;

        // Save to storage
        $output = $pdf->Output('', 'S');
        Storage::disk('public')->put($filePath, $output);

        return $filePath;
    }

    /**
     * Add certificate content to PDF
     */
    private function addCertificateContent(TCPDF $pdf, TrainingRecord $trainingRecord)
    {
        $employee = $trainingRecord->employee;
        $trainingType = $trainingRecord->trainingType;

        // Set font
        $pdf->SetFont('helvetica', '', 12);

        // Add border
        $pdf->Rect(10, 10, 277, 190, 'D', array('all' => array('width' => 2, 'color' => array(67, 148, 84))));
        $pdf->Rect(15, 15, 267, 180, 'D', array('all' => array('width' => 1, 'color' => array(67, 148, 84))));

        // Company Logo and Header
        $pdf->SetY(25);
        $pdf->SetFont('helvetica', 'B', 24);
        $pdf->SetTextColor(67, 148, 84);
        $pdf->Cell(0, 10, 'PT GAPURA ANGKASA', 0, 1, 'C');

        $pdf->SetFont('helvetica', '', 14);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->Cell(0, 8, 'TRAINING CERTIFICATE', 0, 1, 'C');

        // Certificate Title
        $pdf->SetY(50);
        $pdf->SetFont('helvetica', 'B', 18);
        $pdf->SetTextColor(67, 148, 84);
        $pdf->Cell(0, 10, 'CERTIFICATE OF COMPLETION', 0, 1, 'C');

        // Decorative line
        $pdf->SetY(65);
        $pdf->SetDrawColor(67, 148, 84);
        $pdf->Line(80, 65, 217, 65);

        // Certificate content
        $pdf->SetY(80);
        $pdf->SetFont('helvetica', '', 12);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->Cell(0, 8, 'This is to certify that', 0, 1, 'C');

        // Employee name
        $pdf->SetY(95);
        $pdf->SetFont('helvetica', 'B', 16);
        $pdf->SetTextColor(67, 148, 84);
        $pdf->Cell(0, 10, strtoupper($employee->name), 0, 1, 'C');

        // Employee details
        $pdf->SetY(110);
        $pdf->SetFont('helvetica', '', 11);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->Cell(0, 6, 'NIK: ' . $employee->nik . ' | NIP: ' . $employee->nip, 0, 1, 'C');

        // Training completion text
        $pdf->SetY(125);
        $pdf->SetFont('helvetica', '', 12);
        $pdf->Cell(0, 8, 'has successfully completed the training program', 0, 1, 'C');

        // Training type
        $pdf->SetY(140);
        $pdf->SetFont('helvetica', 'B', 14);
        $pdf->SetTextColor(67, 148, 84);
        $pdf->Cell(0, 10, '"' . strtoupper($trainingType->name) . '"', 0, 1, 'C');

        // Validity period
        $pdf->SetY(155);
        $pdf->SetFont('helvetica', '', 11);
        $pdf->SetTextColor(0, 0, 0);
        $validFrom = $trainingRecord->issue_date->format('d F Y');
        $validUntil = $trainingRecord->expiry_date->format('d F Y');
        $pdf->Cell(0, 6, 'Valid from ' . $validFrom . ' to ' . $validUntil, 0, 1, 'C');

        // Certificate number and issue date
        $pdf->SetY(175);
        $pdf->SetFont('helvetica', '', 10);
        $pdf->Cell(130, 6, 'Certificate No: ' . $trainingRecord->certificate_number, 0, 0, 'L');
        $pdf->Cell(137, 6, 'Issue Date: ' . $trainingRecord->issue_date->format('d F Y'), 0, 1, 'R');

        // Signature section
        $pdf->SetY(185);
        $pdf->SetFont('helvetica', '', 10);
        $pdf->Cell(130, 6, 'Authorized by: HR Training Department', 0, 0, 'L');
        $pdf->Cell(137, 6, 'PT Gapura Angkasa', 0, 1, 'R');
    }

    /**
     * Generate bulk certificates for multiple training records
     */
    public function generateBulkCertificates(array $trainingRecordIds): array
    {
        $results = [];
        $successful = 0;
        $failed = 0;

        foreach ($trainingRecordIds as $recordId) {
            try {
                $trainingRecord = TrainingRecord::findOrFail($recordId);
                $result = $this->generateCertificate($trainingRecord);

                $results[] = $result;
                $successful++;
            } catch (\Exception $e) {
                $results[] = [
                    'success' => false,
                    'error' => $e->getMessage(),
                    'training_record_id' => $recordId
                ];
                $failed++;
            }
        }

        return [
            'total_processed' => count($trainingRecordIds),
            'successful' => $successful,
            'failed' => $failed,
            'results' => $results
        ];
    }

    /**
     * Validate certificate format
     */
    public function validateCertificateNumber(string $certificateNumber): bool
    {
        // Format: GLC/OPR-XXX/MM/YYYY
        $pattern = '/^GLC\/OPR-\d{3}\/\d{2}\/\d{4}$/';
        return preg_match($pattern, $certificateNumber) === 1;
    }

    /**
     * Get certificate by number
     */
    public function getCertificateByNumber(string $certificateNumber): ?TrainingRecord
    {
        return TrainingRecord::where('certificate_number', $certificateNumber)
                            ->with(['employee', 'trainingType'])
                            ->first();
    }

    /**
     * Check certificate validity
     */
    public function isCertificateValid(TrainingRecord $trainingRecord): bool
    {
        return $trainingRecord->expiry_date->isFuture() &&
               $trainingRecord->completion_status === 'COMPLETED';
    }

    /**
     * Get expiring certificates
     */
    public function getExpiringCertificates(int $days = 30): \Illuminate\Database\Eloquent\Collection
    {
        $expiryDate = Carbon::now()->addDays($days);

        return TrainingRecord::where('expiry_date', '<=', $expiryDate)
                            ->where('expiry_date', '>', Carbon::now())
                            ->where('completion_status', 'COMPLETED')
                            ->with(['employee', 'trainingType'])
                            ->orderBy('expiry_date')
                            ->get();
    }
}
