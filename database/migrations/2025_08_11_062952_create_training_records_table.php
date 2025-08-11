<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Create training_records table for GAPURA ANGKASA Training System
     * Connects with existing employees table and training_types
     */
    public function up(): void
    {
        Schema::create('training_records', function (Blueprint $table) {
            $table->id();

            // Foreign keys
            $table->string('employee_nip', 20); // Connect to employees.nip
            $table->foreignId('training_type_id')->constrained('training_types')->onDelete('cascade');

            // Certificate details (based on Excel analysis)
            $table->string('certificate_number')->nullable(); // e.g., "GLC / OPR - 001129 / OCT / 2024"
            $table->date('issued_date')->nullable(); // When certificate was issued
            $table->date('valid_from'); // Start date of validity
            $table->date('valid_until'); // End date of validity

            // Status tracking
            $table->enum('status', ['active', 'expired', 'expiring_soon', 'suspended'])->default('active');
            $table->text('notes')->nullable();

            // Metadata
            $table->string('issuing_authority')->nullable(); // e.g., "GLC"
            $table->string('training_location')->nullable();
            $table->string('instructor')->nullable();

            // Import tracking (for Excel import history)
            $table->string('import_batch_id')->nullable();
            $table->timestamp('imported_at')->nullable();

            $table->timestamps();

            // Foreign key constraints
            $table->foreign('employee_nip')->references('nip')->on('employees')->onDelete('cascade');

            // Indexes
            $table->index(['employee_nip', 'training_type_id']);
            $table->index(['status', 'valid_until']);
            $table->index('certificate_number');
            $table->index('valid_until'); // For expiry tracking
            $table->index('import_batch_id');

            // Unique constraint to prevent duplicate certificates for same employee-training type
            $table->unique(['employee_nip', 'training_type_id', 'certificate_number'], 'unique_employee_training_cert');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('training_records');
    }
};
