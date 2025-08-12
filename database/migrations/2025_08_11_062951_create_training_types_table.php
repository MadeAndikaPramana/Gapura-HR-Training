<?php
// File: database/migrations/2024_01_01_000001_create_training_types_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('training_types', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('code')->unique()->nullable();
            $table->string('category')->nullable();
            $table->text('description')->nullable();
            $table->integer('validity_period')->default(12); // in months
            $table->boolean('is_mandatory')->default(false);
            $table->boolean('is_active')->default(true);
            $table->enum('compliance_level', ['LOW', 'MEDIUM', 'HIGH', 'CRITICAL'])->default('MEDIUM');
            $table->string('training_provider_default')->nullable();
            $table->decimal('cost_estimate', 10, 2)->nullable();
            $table->json('requirements')->nullable();
            $table->boolean('renewal_required')->default(true);
            $table->integer('notification_days')->default(30);
            $table->string('created_by')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('training_types');
    }
};
