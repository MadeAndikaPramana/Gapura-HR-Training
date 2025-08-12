<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// File: database/migrations/2025_08_11_062951_create_training_types_table.php

return new class extends Migration
{
    /**
     * Run the migrations.
     * CREATE training_types table for GAPURA ANGKASA Training System
     * Complete version with all fields needed for aviation training
     */
    public function up(): void
    {
        Schema::create('training_types', function (Blueprint $table) {
            $table->id();

            // Basic training information
            $table->string('name'); // e.g., "Dangerous Goods Handling (DGR)"
            $table->string('category')->default('OPERATIONAL'); // SAFETY, OPERATIONAL, TECHNICAL, etc.
            $table->text('description')->nullable();

            // Validity and duration
            $table->integer('validity_period'); // Duration in months (24, 36, etc.)

            // Mandatory and compliance
            $table->boolean('is_mandatory')->default(false);
            $table->boolean('is_active')->default(true);
            $table->enum('compliance_level', ['CRITICAL', 'HIGH', 'MEDIUM', 'LOW', 'OPTIONAL'])->default('MEDIUM');

            // Provider and cost information
            $table->string('training_provider_default')->nullable();
            $table->decimal('cost_estimate', 12, 2)->nullable();

            // Requirements and renewal
            $table->json('requirements')->nullable(); // Array of requirements
            $table->boolean('renewal_required')->default(true);
            $table->integer('notification_days')->default(30); // Days before expiry to notify

            // Audit fields
            $table->string('created_by')->nullable();
            $table->string('updated_by')->nullable();

            $table->timestamps();
            $table->softDeletes(); // Enable soft deletes

            // Indexes for performance
            $table->index(['is_active', 'is_mandatory']);
            $table->index(['category', 'is_active']);
            $table->index('compliance_level');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('training_types');
    }
};
