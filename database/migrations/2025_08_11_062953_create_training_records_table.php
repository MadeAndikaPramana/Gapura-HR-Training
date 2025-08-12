<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// File: database/migrations/2025_08_11_062953_create_training_records_table.php

return new class extends Migration
{
    /**
     * Run the migrations.
     * CREATE training_records table for GAPURA ANGKASA Training System
     * FIXED: Proper CREATE TABLE, not ALTER TABLE
     */
    public function up(): void
    {
        Schema::create('training_records', function (Blueprint $table) {
            $table->id();

            // Foreign keys
            $table->foreignId('employee_id')->constrained('employees')->onDelete('cascade');
            $table->foreignId('training_type_id')->constrained('training_types')->onDelete('cascade');

            // Basic certificate information
            $table->string('certificate_number')->unique();
            $table->string('training_provider');
            $table->date('issue_date');
            $table->date('expiry_date');
            $table->integer('validity_period')->nullable(); // Months

            // Enhanced training information
            $table->string('training_location')->nullable();
            $table->string('training_duration')->nullable(); // e.g., "3 days", "40 hours"
            $table->string('instructor_name')->nullable();
            $table->enum('completion_status', ['completed', 'in_progress', 'failed', 'cancelled'])->default('completed');
            $table->decimal('training_cost', 12, 2)->nullable();
            $table->enum('internal_external', ['internal', 'external'])->default('external');
            $table->string('batch_id')->nullable();

            // Notes and compliance
            $table->text('notes')->nullable();
            $table->text('compliance_requirements')->nullable();
            $table->boolean('renewal_required')->default(true);
            $table->integer('notification_before_expiry')->default(30); // Days

            // File management
            $table->string('certificate_file')->nullable(); // File path
            $table->json('supporting_documents')->nullable(); // Array of file info

            // Audit fields
            $table->string('created_by')->nullable();
            $table->string('updated_by')->nullable();

            // Legacy fields for Excel import compatibility
            $table->string('import_batch_id')->nullable();
            $table->timestamp('imported_at')->nullable();

            $table->timestamps();
            $table->softDeletes(); // Enable soft deletes

            // Indexes for performance
            $table->index(['employee_id', 'training_type_id']);
            $table->index(['expiry_date', 'completion_status']);
            $table->index('certificate_number');
            $table->index('expiry_date'); // For expiry tracking
            $table->index('completion_status');
            $table->index('internal_external');
            $table->index('import_batch_id');

            // Composite indexes for common queries
            $table->index(['employee_id', 'training_type_id', 'expiry_date'], 'employee_training_expiry_idx');
            $table->index(['completion_status', 'expiry_date'], 'status_expiry_idx');
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
