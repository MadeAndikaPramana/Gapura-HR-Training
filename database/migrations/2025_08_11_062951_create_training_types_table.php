<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Create training_types table for GAPURA ANGKASA Training System
     * Based on Excel analysis: 5 training types with different durations
     */
    public function up(): void
    {
        Schema::create('training_types', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // e.g., "PAX & BAGGAGE HANDLING"
            $table->string('code')->unique(); // e.g., "PAX_HANDLING"
            $table->text('description')->nullable();
            $table->integer('duration_months'); // 36, 24, or 12 months
            $table->string('certificate_format')->nullable(); // e.g., "GLC/OPR-{number}/{month}/{year}"
            $table->boolean('requires_background_check')->default(false);
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0); // For ordering in UI
            $table->timestamps();

            // Indexes
            $table->index(['is_active', 'sort_order']);
            $table->index('code');
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
