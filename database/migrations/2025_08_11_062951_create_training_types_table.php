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
            $table->string('name'); // PAX & BAGGAGE HANDLING, SAFETY TRAINING, etc.
            $table->string('code')->unique(); // PAX_BAGGAGE, SAFETY_SMS, etc.
            $table->text('description')->nullable();
            $table->integer('validity_months'); // 36, 24, 12 months
            $table->string('category')->default('OPERATIONAL'); // OPERATIONAL, SAFETY, SECURITY
            $table->boolean('is_mandatory')->default(true);
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }
};
