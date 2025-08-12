<?php

// 1. DATABASE MIGRATION - EMPLOYEES TABLE (SIMPLIFIED)
// File: database/migrations/create_employees_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->id();

            // Data utama dari MPGA (sesuai Excel)
            $table->string('nip', 20)->unique(); // NIPP dari Excel
            $table->string('nama_lengkap'); // NAMA dari Excel
            $table->string('unit_kerja'); // Dept/Unit dari Excel
            $table->string('department')->nullable(); // Sheet name (DEDICATED, LOADING, etc.)

            // Data tambahan (opsional, untuk masa depan)
            $table->string('nik', 20)->nullable(); // Bisa ditambah kemudian
            $table->enum('jenis_kelamin', ['L', 'P'])->nullable();
            $table->string('email')->nullable();
            $table->string('handphone')->nullable();
            $table->enum('status_pegawai', [
                'PEGAWAI TETAP',
                'PKWT',
                'TAD PAKET SDM',
                'TAD PAKET PEKERJAAN'
            ])->default('PEGAWAI TETAP');
            $table->string('status_kerja')->default('Aktif');

            $table->timestamps();

            // Indexes
            $table->index(['nip', 'status_kerja']);
            $table->index(['department', 'unit_kerja']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
