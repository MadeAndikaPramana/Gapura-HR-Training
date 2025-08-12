<?php

namespace App\Console\Commands;

use App\Models\TrainingRecord;
use App\Services\CertificateGenerationService;
use Illuminate\Console\Command;

class GenerateTrainingCertificates extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'training:generate-certificates
                            {--missing : Only generate for records without certificates}
                            {--employee= : Generate for specific employee ID}
                            {--training-type= : Generate for specific training type ID}
                            {--batch-size=50 : Number of certificates to generate per batch}';

    /**
     * The console command description.
     */
    protected $description = 'Generate training certificates in bulk';

    /**
     * Certificate service instance
     */
    protected $certificateService;

    /**
     * Create a new command instance.
     */
    public function __construct(CertificateGenerationService $certificateService)
    {
        parent::__construct();
        $this->certificateService = $certificateService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🎓 GAPURA Training Certificate Generator');
        $this->info('=====================================');

        $query = TrainingRecord::with(['employee', 'trainingType'])
                              ->where('completion_status', 'COMPLETED');

        // Apply filters
        if ($this->option('missing')) {
            $query->whereNull('certificate_number');
            $this->info('🔍 Generating certificates for records without certificate numbers...');
        }

        if ($this->option('employee')) {
            $query->where('employee_id', $this->option('employee'));
            $this->info('👤 Filtering by employee ID: ' . $this->option('employee'));
        }

        if ($this->option('training-type')) {
            $query->where('training_type_id', $this->option('training-type'));
            $this->info('📚 Filtering by training type ID: ' . $this->option('training-type'));
        }

        $records = $query->get();
        $total = $records->count();

        if ($total === 0) {
            $this->warn('📭 No training records found matching the criteria.');
            return 0;
        }

        $this->info("📊 Found {$total} training records to process.");
        $this->newLine();

        $batchSize = (int) $this->option('batch-size');
        $successful = 0;
        $failed = 0;
        $errors = [];

        $progressBar = $this->output->createProgressBar($total);
        $progressBar->start();

        foreach ($records->chunk($batchSize) as $batch) {
            $batchIds = $batch->pluck('id')->toArray();

            try {
                $results = $this->certificateService->generateBulkCertificates($batchIds);
                $successful += $results['successful'];
                $failed += $results['failed'];

                foreach ($results['results'] as $result) {
                    if (!$result['success'] && isset($result['error'])) {
                        $errors[] = $result['error'];
                    }
                }

            } catch (\Exception $e) {
                $failed += count($batchIds);
                $errors[] = $e->getMessage();
            }

            $progressBar->advance(count($batch));
        }

        $progressBar->finish();
        $this->newLine(2);

        // Display results
        $this->info('📈 CERTIFICATE GENERATION SUMMARY');
        $this->info('=================================');
        $this->line("✅ Successful: {$successful}");
        $this->line("❌ Failed: {$failed}");
        $this->line("📊 Total Processed: {$total}");
        $this->line("🎯 Success Rate: " . round(($successful / $total) * 100, 2) . "%");

        if (!empty($errors)) {
            $this->newLine();
            $this->warn('⚠️  ERRORS ENCOUNTERED:');
            foreach (array_unique($errors) as $error) {
                $this->error("   • {$error}");
            }
        }

        return $failed > 0 ? 1 : 0;
    }
}
