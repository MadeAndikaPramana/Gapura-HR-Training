// File: database/migrations/2024_01_01_000002_create_employees_table.php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('nik', 16)->unique();
            $table->string('nip', 20)->unique();
            $table->string('email')->unique();
            $table->string('phone')->nullable();
            $table->string('department');
            $table->string('position');
            $table->date('hire_date');
            $table->date('birth_date')->nullable();
            $table->text('address')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
