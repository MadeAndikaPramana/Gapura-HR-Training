// File: database/migrations/2024_01_01_000003_create_training_records_table.php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('training_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->onDelete('cascade');
            $table->foreignId('training_type_id')->constrained()->onDelete('cascade');

            // Certificate info (dari Excel MPGA)
            $table->string('certificate_number')->nullable(); // GLC/OPR-001129/OCT/2024
            $table->date('valid_from')->nullable(); // FROM column
            $table->date('valid_until')->nullable(); // UNTIL column
            $table->date('issued_date')->nullable();

            // Training status
            $table->enum('status', ['active', 'expired', 'expiring_soon', 'pending'])->default('active');
            $table->text('remarks')->nullable(); // KETERANGAN dari Excel

            // Background check date (dari kolom BACKGROUND CHECK)
            $table->date('background_check_date')->nullable();

            // Import tracking
            $table->string('import_batch_id')->nullable();
            $table->timestamp('imported_at')->nullable();

            $table->timestamps();

            // Indexes
            $table->index(['employee_id', 'training_type_id']);
            $table->index(['status', 'valid_until']);
            $table->index('import_batch_id');
        });
    }
};
