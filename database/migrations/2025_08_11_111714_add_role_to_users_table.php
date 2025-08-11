<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Add role and employee relationship to users table
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Add role system
            $table->enum('role', ['super_admin', 'admin', 'manager', 'staff', 'viewer'])
                  ->default('staff')
                  ->after('email_verified_at');

            // Link to employee table
            $table->string('employee_nip', 20)->nullable()->after('role');
            $table->foreign('employee_nip')->references('nip')->on('employees')->onDelete('set null');

            // User status and tracking
            $table->boolean('is_active')->default(true)->after('employee_nip');
            $table->timestamp('last_login_at')->nullable()->after('is_active');

            // Indexes for performance
            $table->index(['role', 'is_active']);
            $table->index('employee_nip');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['employee_nip']);
            $table->dropIndex(['role', 'is_active']);
            $table->dropIndex(['employee_nip']);
            $table->dropColumn(['role', 'employee_nip', 'is_active', 'last_login_at']);
        });
    }
};
