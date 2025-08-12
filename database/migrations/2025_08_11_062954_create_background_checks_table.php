<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// File: database/migrations/2025_08_11_062954_create_background_checks_table.php

return new class extends Migration
{
    /**
     * Run the migrations.
     * CREATE background_checks table for GAPURA ANGKASA Training System
     * FIXED: Proper CREATE TABLE with correct employee relationship
     */
    public function up(): void
    {
        Schema::create('background_checks', function (Blueprint $table) {
            $table->id();

            // Foreign key to employee - using nip for legacy compatibility
            $table->string('employee_nip', 20);

            // Background check details
            $table->date('check_date'); // e.g., "2024-10-03" from "3 Oktober 2024"
            $table->enum('check_type', [
                'security_clearance',
                'criminal_background',
                'employment_verification',
                'reference_check',
                'periodic_review'
            ])->default('security_clearance');

            $table->enum('status', [
                'pending',
                'in_progress',
                'passed',
                'failed',
                'expired',
                'requires_renewal'
            ])->default('pending');

            $table->date('valid_until')->nullable(); // Expiry date if applicable
            $table->text('notes')->nullable(); // Additional remarks
            $table->string('conducted_by')->nullable(); // Who conducted the check
            $table->string('reference_number')->nullable(); // Internal reference

            // Attachments and documentation
            $table->json('documents')->nullable(); // Store file paths/references

            // Import tracking
            $table->string('import_batch_id')->nullable();
            $table->timestamp('imported_at')->nullable();

            $table->timestamps();

            // Foreign key constraint - references employees.nip
            $table->foreign('employee_nip')->references('nip')->on('employees')->onDelete('cascade');

            // Indexes
            $table->index(['employee_nip', 'status']);
            $table->index(['check_date', 'status']);
            $table->index('valid_until');
            $table->index('import_batch_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('background_checks');
    }
};
