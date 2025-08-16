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

            // MPGA Core Fields - HANYA SATU INDEX PER FIELD
            $table->string('nip', 20)->unique(); // unique sudah include index
            $table->string('nik', 20)->nullable()->unique();

            // JANGAN tambah ->index() jika sudah ada unique()
            $table->string('nama_lengkap'); // NO ->index() here
            $table->string('department', 50);
            $table->string('unit_organisasi');

            // Personal Information
            $table->enum('jenis_kelamin', ['L', 'P'])->nullable();
            $table->string('tempat_lahir')->nullable();
            $table->date('tanggal_lahir')->nullable();
            $table->integer('usia')->nullable();

            // Work Information
            $table->string('jabatan')->nullable();
            $table->enum('status_pegawai', [
                'PEGAWAI TETAP',
                'PKWT',
                'TAD PAKET SDM',
                'TAD PAKET PEKERJAAN'
            ])->default('PEGAWAI TETAP');
            $table->string('status_kerja', 50)->default('Aktif');
            $table->string('lokasi_kerja')->nullable();
            $table->string('cabang', 100)->nullable();
            $table->string('provider')->nullable();

            // Contact Information
            $table->string('handphone', 20)->nullable();
            $table->string('email')->nullable()->unique();
            $table->text('alamat')->nullable();

            // System Fields
            $table->boolean('is_active')->default(true);

            // Future expansion fields
            $table->string('kode_organisasi')->nullable();
            $table->string('nama_organisasi')->nullable();
            $table->string('kelompok_jabatan')->nullable();
            $table->string('unit_kerja_kontrak')->nullable();

            // Employment dates
            $table->date('tmt_mulai_kerja')->nullable();
            $table->date('tmt_mulai_jabatan')->nullable();
            $table->date('tmt_berakhir_jabatan')->nullable();
            $table->date('tmt_berakhir_kerja')->nullable();
            $table->integer('masa_kerja_bulan')->nullable();
            $table->integer('masa_kerja_tahun')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // INDEXES HANYA DI SINI - SATU KALI SAJA
            $table->index('nama_lengkap'); // HANYA di sini
            $table->index('department');
            $table->index('unit_organisasi');
            $table->index(['is_active', 'department']);
            $table->index(['department', 'unit_organisasi']);
            $table->index(['status_pegawai', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
