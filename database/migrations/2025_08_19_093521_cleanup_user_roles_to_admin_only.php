<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Cleanup user roles - keep only admin and super_admin
     */
    public function up(): void
    {
        // Update users table - change role enum to only admin and super_admin
        Schema::table('users', function (Blueprint $table) {
            // First, update any existing users with invalid roles to admin
            DB::statement("UPDATE users SET role = 'admin' WHERE role NOT IN ('admin', 'super_admin')");

            // Then modify the enum column
            $table->enum('role', ['admin', 'super_admin'])->default('admin')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Restore original enum with all roles
            $table->enum('role', ['super_admin', 'admin', 'manager', 'staff', 'viewer'])
                  ->default('admin')
                  ->change();
        });
    }
};
