<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Create employees table for GAPURA Training System
     */
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->id();

            // Primary identifiers
            $table->string('nip', 20)->unique(); // Employee ID - used for training relationships
            $table->string('nik', 20)->nullable(); // National ID

            // Personal information
            $table->string('nama_lengkap');
            $table->enum('jenis_kelamin', ['L', 'P']); // L = Laki-laki, P = Perempuan
            $table->string('tempat_lahir')->nullable();
            $table->date('tanggal_lahir')->nullable();
            $table->integer('usia')->nullable();
            $table->string('kota_domisili')->nullable();
            $table->text('alamat')->nullable();

            // Contact information
            $table->string('handphone')->nullable();
            $table->string('email')->nullable();

            // Work information
            $table->string('lokasi_kerja')->nullable();
            $table->string('cabang')->nullable();
            $table->enum('status_pegawai', [
                'PEGAWAI TETAP',
                'PKWT',
                'TAD PAKET SDM',
                'TAD PAKET PEKERJAAN'
            ]);
            $table->string('status_kerja')->default('Aktif');
            $table->string('provider')->nullable();

            // Organizational structure
            $table->string('kode_organisasi')->nullable();
            $table->string('unit_organisasi')->nullable(); // Department (DEDICATED, LOADING, etc.)
            $table->string('nama_organisasi')->nullable();
            $table->string('nama_jabatan')->nullable();
            $table->string('jabatan')->nullable();
            $table->enum('kelompok_jabatan', [
                'SUPERVISOR',
                'STAFF',
                'MANAGER',
                'EXECUTIVE GENERAL MANAGER',
                'ACCOUNT EXECUTIVE/AE'
            ])->nullable();

            // Employment dates
            $table->string('unit_kerja_kontrak')->nullable();
            $table->date('tmt_mulai_kerja')->nullable();
            $table->date('tmt_mulai_jabatan')->nullable();
            $table->date('tmt_berakhir_jabatan')->nullable();

            // Work experience (auto-calculated)
            $table->integer('masa_kerja_tahun')->nullable();
            $table->integer('masa_kerja_bulan')->nullable();
            $table->integer('masa_kerja_hari')->nullable();

            // Education background
            $table->string('pendidikan_terakhir')->nullable();
            $table->string('institusi_pendidikan')->nullable();
            $table->string('jurusan')->nullable();
            $table->integer('tahun_lulus')->nullable();

            // Uniform & equipment
            $table->string('jenis_sepatu_pantofel')->nullable();
            $table->integer('ukuran_sepatu_pantofel')->nullable();
            $table->string('jenis_sepatu_safety')->nullable();
            $table->integer('ukuran_sepatu_safety')->nullable();

            // Insurance & benefits
            $table->string('no_bpjs_kesehatan')->nullable();
            $table->string('no_bpjs_ketenagakerjaan')->nullable();

            // Physical data
            $table->integer('tinggi_badan')->nullable(); // cm
            $table->integer('berat_badan')->nullable(); // kg

            $table->timestamps();

            // Indexes for performance
            $table->index(['unit_organisasi', 'status_kerja']); // Department queries
            $table->index(['status_pegawai', 'status_kerja']); // Status queries
            $table->index('jabatan'); // Job title queries
            $table->index('nip'); // Training relationship queries
            $table->index(['nama_lengkap', 'nip']); // Search queries
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
