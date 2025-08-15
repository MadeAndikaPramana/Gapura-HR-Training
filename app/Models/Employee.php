<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;

class Employee extends Model
{
    use HasFactory;

    /**
     * UPDATED FOR MPGA: Field mapping sesuai Excel structure
     * - NAMA -> nama_lengkap
     * - NIPP -> nip
     * - Dept/Unit -> unit_organisasi
     * - Sheet name -> department
     */

    protected $table = 'employees';

    /**
     * MPGA PRIMARY KEY: Gunakan auto-increment ID, tapi keep NIK unique
     * Berubah dari NIK primary key ke standard Laravel ID untuk compatibility
     */
    protected $primaryKey = 'id';
    public $incrementing = true;
    protected $keyType = 'int';

    /**
     * UPDATED FILLABLE: Field sesuai MPGA Excel + existing fields
     */
    protected $fillable = [
        // MPGA Core Fields (dari Excel)
        'nip',              // NIPP dari Excel - REQUIRED
        'nama_lengkap',     // NAMA dari Excel - REQUIRED
        'unit_organisasi',  // Dept/Unit dari Excel - REQUIRED
        'department',       // Sheet name (DEDICATED, LOADING, etc.) - REQUIRED
        'unit_kerja',

        // Identity fields
        'nik',              // NIK - OPTIONAL, bisa auto-generate

        // Contact & Personal Info
        'jenis_kelamin',
        'tempat_lahir',
        'tanggal_lahir',
        'usia',
        'kota_domisili',
        'alamat',
        'handphone',
        'email',

        // Work Information
        'lokasi_kerja',
        'cabang',
        'status_pegawai',
        'status_kerja',
        'provider',

        // Organizational Structure
        'kode_organisasi',
        'nama_organisasi',
        'nama_jabatan',
        'jabatan',
        'kelompok_jabatan',
        'unit_kerja_kontrak',

        // Employment Dates
        'tmt_mulai_kerja',
        'tmt_mulai_jabatan',
        'tmt_berakhir_jabatan',
        'tmt_berakhir_kerja',
        'masa_kerja_bulan',
        'masa_kerja_tahun',

        // Education
        'pendidikan',
        'pendidikan_terakhir',
        'instansi_pendidikan',
        'jurusan',
        'remarks_pendidikan',
        'tahun_lulus',

        // Equipment & Benefits
        'jenis_sepatu',
        'ukuran_sepatu',
        'seragam',
        'no_bpjs_kesehatan',
        'no_bpjs_ketenagakerjaan',
        'grade',
        'kategori_karyawan',
        'tmt_pensiun',

        // Physical Data
        'weight',
        'height',

        // System Fields
        'organization_id',
        'status'
    ];

    /**
     * CAST ATTRIBUTES
     */
    protected $casts = [
        'tmt_mulai_kerja' => 'date',
        'tmt_mulai_jabatan' => 'date',
        'tmt_berakhir_jabatan' => 'date',
        'tmt_berakhir_kerja' => 'date',
        'tanggal_lahir' => 'date',
        'tmt_pensiun' => 'date',
        'tahun_lulus' => 'integer',
        'usia' => 'integer',
        'weight' => 'integer',
        'height' => 'integer',
        'masa_kerja_bulan' => 'integer',
        'masa_kerja_tahun' => 'integer',
    ];

    /**
     * MPGA CONSTANTS: Department options dari Excel sheets
     */
    const MPGA_DEPARTMENTS = [
        'DEDICATED',
        'LOADING',
        'RAMP',
        'LOCO',
        'ULD',
        'LOST & FOUND',
        'CARGO',
        'ARRIVAL',
        'GSE OPERATOR',
        'FLOP',
        'AVSEC',
        'PORTER'
    ];

    const STATUS_PEGAWAI_OPTIONS = [
        'PEGAWAI TETAP',
        'PKWT',
        'TAD PAKET SDM',
        'TAD PAKET PEKERJAAN'
    ];

    /**
     * MPGA SCOPES: Query helpers untuk MPGA data
     */
    public function scopeByDepartment($query, $department)
    {
        return $query->where('department', $department);
    }

    public function scopeByUnit($query, $unit)
    {
        return $query->where('unit_organisasi', $unit);
    }

    public function scopeActive($query)
    {
        return $query->where('status_kerja', 'Aktif')
                    ->where('status', 'active');
    }

    public function scopeMpgaEmployees($query)
    {
        return $query->whereIn('department', self::MPGA_DEPARTMENTS);
    }

    /**
     * RELATIONSHIPS: Training system relationships
     */
    public function trainingRecords()
    {
        return $this->hasMany(TrainingRecord::class);
    }

    public function backgroundChecks()
    {
        return $this->hasMany(BackgroundCheck::class, 'employee_nip', 'nip');
    }

    /**
     * ACCESSORS: Computed properties
     */
    public function getFullIdentityAttribute()
    {
        return "{$this->nama_lengkap} (NIP: {$this->nip})";
    }

    public function getDepartmentUnitAttribute()
    {
        return "{$this->department} - {$this->unit_organisasi}";
    }

    /**
     * MUTATORS: Auto-processing
     */
    public function setNipAttribute($value)
    {
        $this->attributes['nip'] = str_pad($value, 7, '0', STR_PAD_LEFT);
    }

    public function setNamaLengkapAttribute($value)
    {
        $this->attributes['nama_lengkap'] = strtoupper(trim($value));
    }

    /**
     * MPGA HELPER METHODS
     */

    /**
     * Generate NIK if not provided
     */
    public static function generateNik($nip, $department = null)
    {
        $prefix = $department ? substr($department, 0, 3) : 'EMP';
        return strtoupper($prefix) . str_pad($nip, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Get department statistics
     */
    public static function getDepartmentStats()
    {
        return self::selectRaw('department, COUNT(*) as total')
                  ->whereIn('department', self::MPGA_DEPARTMENTS)
                  ->where('status', 'active')
                  ->groupBy('department')
                  ->orderBy('total', 'desc')
                  ->get();
    }

    /**
     * Get unit statistics per department
     */
    public function getUnitStats($department = null)
    {
        $query = self::selectRaw('department, unit_organisasi, COUNT(*) as total')
                    ->where('status', 'active');

        if ($department) {
            $query->where('department', $department);
        }

        return $query->groupBy('department', 'unit_organisasi')
                    ->orderBy('department')
                    ->orderBy('total', 'desc')
                    ->get();
    }

    /**
     * BOOT METHOD: Auto-processing saat create/update
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($employee) {
            // Auto-generate NIK jika kosong
            if (empty($employee->nik) && !empty($employee->nip)) {
                $employee->nik = self::generateNik($employee->nip, $employee->department);
            }

            // Set default status
            if (empty($employee->status)) {
                $employee->status = 'active';
            }

            if (empty($employee->status_kerja)) {
                $employee->status_kerja = 'Aktif';
            }
        });

        static::updating(function ($employee) {
            // Update NIK jika NIP berubah
            if ($employee->isDirty('nip') && empty($employee->nik)) {
                $employee->nik = self::generateNik($employee->nip, $employee->department);
            }
        });
    }
}
