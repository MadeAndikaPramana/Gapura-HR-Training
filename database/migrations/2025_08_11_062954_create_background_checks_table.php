// File: database/migrations/2024_01_01_000004_create_background_checks_table.php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('background_checks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->onDelete('cascade');
            $table->enum('check_type', ['SECURITY_CLEARANCE', 'CRIMINAL_BACKGROUND', 'MEDICAL_CLEARANCE', 'REFERENCE_CHECK', 'EDUCATION_VERIFICATION']);
            $table->enum('status', ['PENDING', 'IN_PROGRESS', 'APPROVED', 'REJECTED', 'EXPIRED', 'SUSPENDED'])->default('PENDING');
            $table->datetime('issue_date');
            $table->datetime('expiry_date');
            $table->string('authority');
            $table->string('reference_number')->unique();
            $table->text('notes')->nullable();
            $table->string('document_path')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('background_checks');
    }
};
