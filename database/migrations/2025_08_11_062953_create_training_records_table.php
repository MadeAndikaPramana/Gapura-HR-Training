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
            $table->string('certificate_number')->unique()->nullable();
            $table->datetime('issue_date');
            $table->datetime('expiry_date');
            $table->enum('completion_status', ['PENDING', 'IN_PROGRESS', 'COMPLETED', 'FAILED'])->default('COMPLETED');
            $table->string('training_provider')->nullable();
            $table->decimal('cost', 10, 2)->nullable();
            $table->text('notes')->nullable();
            $table->string('certificate_path')->nullable();
            $table->foreignId('previous_training_id')->nullable()->constrained('training_records')->onDelete('set null');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('training_records');
    }
};
