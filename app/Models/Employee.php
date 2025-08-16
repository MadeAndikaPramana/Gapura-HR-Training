<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;

class Employee extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * GAPURA TRAINING SYSTEM - EMPLOYEE MODEL
     * Phase 1: CRUD Employee dengan struktur MPGA Excel
     *
     * STANDARDIZED VERSION:
     * - Menggunakan Laravel standard auto-increment ID sebagai primary key
     * - NIP sebagai unique identifier untuk business logic
     * - Support untuk MPGA Excel structure (department sheets)
     * - Ready untuk Phase 2 (Training Records relationships)
     */

    protected $table = 'employees';

    // Standard Laravel auto-increment primary key
    protected $primaryKey = 'id';
    public $incrementing = true;
    protected $keyType = 'int';

    /**
     * FILLABLE FIELDS - Sesuai dengan MPGA Excel structure dan form requirements
     */
    protected $fillable = [
        // MPGA Core Fields (dari Excel struktur)
        'nip',              // NIPP dari MPGA Excel - UNIQUE, business identifier
        'nik',              // NIK - NULLABLE, bisa auto-generate
        'nama_lengkap',     // NAMA dari MPGA Excel - REQUIRED
        'department',       // Sheet name (DEDICATED, LOADING, etc.) - REQUIRED
        'unit_organisasi',  // Dept/Unit dari MPGA Excel - REQUIRED

        // Personal Information
        'jenis_kelamin',    // L/P
        'tempat_lahir',
        'tanggal_lahir',
        'usia',            // Calculated field

        // Work Information
        'jabatan',         // Job position
        'status_pegawai',  // PEGAWAI TETAP, PKWT, TAD PAKET SDM, TAD PAKET PEKERJAAN
        'status_kerja',    // Aktif/Tidak Aktif
        'lokasi_kerja',
        'cabang',
        'provider',        // Provider/Vendor info

        // Contact Information
        'handphone',
        'email',
        'alamat',

        // System Fields
        'is_active',       // Boolean flag for soft delete alternative

        // Future expansion fields (untuk Phase selanjutnya)
        'kode_organisasi',
        'nama_organisasi',
        'kelompok_jabatan',
        'unit_kerja_kontrak',

        // Employment dates (untuk Phase selanjutnya)
        'tmt_mulai_kerja',
        'tmt_mulai_jabatan',
        'tmt_berakhir_jabatan',
        'tmt_berakhir_kerja',
        'masa_kerja_bulan',
        'masa_kerja_tahun',
    ];

    /**
     * FIELD CASTING
     */
    protected $casts = [
        'tanggal_lahir' => 'date',
        'tmt_mulai_kerja' => 'date',
        'tmt_mulai_jabatan' => 'date',
        'tmt_berakhir_jabatan' => 'date',
        'tmt_berakhir_kerja' => 'date',
        'is_active' => 'boolean',
        'usia' => 'integer',
        'masa_kerja_bulan' => 'integer',
        'masa_kerja_tahun' => 'integer',
    ];

    /**
     * HIDDEN FIELDS - untuk API responses
     */
    protected $hidden = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    /**
     * APPENDED ATTRIBUTES - computed fields
     */
    protected $appends = [
        'full_department_name',
        'calculated_age',
        'status_label',
    ];

    // =====================================================
    // RELATIONSHIPS (untuk Phase 2+)
    // =====================================================

    /**
     * Training Records relationship (Phase 2)
     */
    public function trainingRecords()
    {
        // Uncomment when TrainingRecord model is ready in Phase 2
        // return $this->hasMany(TrainingRecord::class, 'employee_id');

        // Temporary return empty collection
        return collect([]);
    }

    /**
     * Background Checks relationship (Phase 2)
     */
    public function backgroundChecks()
    {
        // Uncomment when BackgroundCheck model is ready in Phase 2
        // return $this->hasMany(BackgroundCheck::class, 'employee_id');

        // Temporary return empty collection
        return collect([]);
    }

    // =====================================================
    // QUERY SCOPES
    // =====================================================

    /**
     * Scope untuk active employees
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope untuk inactive employees
     */
    public function scopeInactive(Builder $query): Builder
    {
        return $query->where('is_active', false);
    }

    /**
     * Scope by department (MPGA sheets)
     */
    public function scopeByDepartment(Builder $query, string $department): Builder
    {
        return $query->where('department', $department);
    }

    /**
     * Scope by unit organisasi
     */
    public function scopeByUnit(Builder $query, string $unit): Builder
    {
        return $query->where('unit_organisasi', $unit);
    }

    /**
     * Scope by status pegawai
     */
    public function scopeByStatus(Builder $query, string $status): Builder
    {
        return $query->where('status_pegawai', $status);
    }

    /**
     * Scope untuk search (nama, NIP, NIK)
     */
    public function scopeSearch(Builder $query, string $search): Builder
    {
        return $query->where(function($q) use ($search) {
            $q->where('nama_lengkap', 'like', "%{$search}%")
              ->orWhere('nip', 'like', "%{$search}%")
              ->orWhere('nik', 'like', "%{$search}%")
              ->orWhere('jabatan', 'like', "%{$search}%")
              ->orWhere('unit_organisasi', 'like', "%{$search}%");
        });
    }

    // =====================================================
    // ACCESSOR METHODS (Computed Attributes)
    // =====================================================

    /**
     * Get full department name with unit
     */
    public function getFullDepartmentNameAttribute(): string
    {
        if ($this->unit_organisasi && $this->department) {
            return "{$this->department} - {$this->unit_organisasi}";
        }
        return $this->department ?? 'Unknown Department';
    }

    /**
     * Calculate current age from birth date
     */
    public function getCalculatedAgeAttribute(): ?int
    {
        if (!$this->tanggal_lahir) {
            return null;
        }

        return Carbon::parse($this->tanggal_lahir)->age;
    }

    /**
     * Get human readable status label
     */
    public function getStatusLabelAttribute(): string
    {
        if (!$this->is_active) {
            return 'Tidak Aktif';
        }

        return match($this->status_pegawai) {
            'PEGAWAI TETAP' => 'Pegawai Tetap',
            'PKWT' => 'Kontrak',
            'TAD PAKET SDM' => 'TAD SDM',
            'TAD PAKET PEKERJAAN' => 'TAD Pekerjaan',
            default => $this->status_pegawai ?? 'Unknown'
        };
    }

    // =====================================================
    // MUTATOR METHODS (Data Formatting)
    // =====================================================

    /**
     * Auto-uppercase nama lengkap
     */
    public function setNamaLengkapAttribute($value): void
    {
        $this->attributes['nama_lengkap'] = strtoupper($value);
    }

    /**
     * Auto-uppercase department
     */
    public function setDepartmentAttribute($value): void
    {
        $this->attributes['department'] = strtoupper($value);
    }

    /**
     * Auto-uppercase unit organisasi
     */
    public function setUnitOrganisasiAttribute($value): void
    {
        $this->attributes['unit_organisasi'] = strtoupper($value);
    }

    /**
     * Format phone number
     */
    public function setHandphoneAttribute($value): void
    {
        if ($value) {
            // Remove any non-numeric characters except +
            $cleaned = preg_replace('/[^0-9+]/', '', $value);
            $this->attributes['handphone'] = $cleaned;
        } else {
            $this->attributes['handphone'] = null;
        }
    }

    /**
     * Auto-calculate age when birth date is set
     */
    public function setTanggalLahirAttribute($value): void
    {
        if ($value) {
            $this->attributes['tanggal_lahir'] = $value;
            $this->attributes['usia'] = Carbon::parse($value)->age;
        } else {
            $this->attributes['tanggal_lahir'] = null;
            $this->attributes['usia'] = null;
        }
    }

    // =====================================================
    // BUSINESS LOGIC METHODS
    // =====================================================

    /**
     * Check if employee has valid training records (Phase 2+)
     */
    public function hasValidTraining(): bool
    {
        // TODO: Implement in Phase 2
        // return $this->trainingRecords()->where('expiry_date', '>', now())->exists();
        return false;
    }

    /**
     * Get expired training count (Phase 2+)
     */
    public function getExpiredTrainingCount(): int
    {
        // TODO: Implement in Phase 2
        // return $this->trainingRecords()->where('expiry_date', '<=', now())->count();
        return 0;
    }

    /**
     * Check if employee is compliant (Phase 2+)
     */
    public function isCompliant(): bool
    {
        // TODO: Implement in Phase 2
        // Check if all mandatory training is up to date
        return true;
    }

    /**
     * Get training compliance percentage (Phase 2+)
     */
    public function getCompliancePercentage(): float
    {
        // TODO: Implement in Phase 2
        return 0.0;
    }

    /**
     * Activate employee
     */
    public function activate(): bool
    {
        return $this->update([
            'is_active' => true,
            'status_kerja' => 'Aktif'
        ]);
    }

    /**
     * Deactivate employee (soft delete alternative)
     */
    public function deactivate(): bool
    {
        return $this->update([
            'is_active' => false,
            'status_kerja' => 'Tidak Aktif'
        ]);
    }

    // =====================================================
    // STATIC HELPER METHODS
    // =====================================================

    /**
     * Get MPGA department options
     */
    public static function getMPGADepartments(): array
    {
        return [
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
    }

    /**
     * Get status pegawai options
     */
    public static function getStatusOptions(): array
    {
        return [
            'PEGAWAI TETAP',
            'PKWT',
            'TAD PAKET SDM',
            'TAD PAKET PEKERJAAN'
        ];
    }

    /**
     * Generate unique NIP
     */
    public static function generateNIP(): string
    {
        do {
            $nip = date('y') . str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);
        } while (self::where('nip', $nip)->exists());

        return $nip;
    }

    /**
     * Generate unique NIK
     */
    public static function generateNIK(): string
    {
        do {
            $nik = date('ym') . str_pad(mt_rand(1, 999999), 6, '0', STR_PAD_LEFT);
        } while (self::where('nik', $nik)->exists());

        return $nik;
    }

    /**
     * Get department statistics
     */
    public static function getDepartmentStats(): array
    {
        return self::active()
                  ->selectRaw('department, COUNT(*) as total')
                  ->groupBy('department')
                  ->orderByDesc('total')
                  ->get()
                  ->pluck('total', 'department')
                  ->toArray();
    }

    /**
     * Get monthly registration stats
     */
    public static function getMonthlyStats(int $months = 6): array
    {
        $stats = [];
        for ($i = $months; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $count = self::whereYear('created_at', $date->year)
                        ->whereMonth('created_at', $date->month)
                        ->count();

            $stats[$date->format('M Y')] = $count;
        }

        return $stats;
    }

    // =====================================================
    // MODEL EVENTS
    // =====================================================

    /**
     * Boot method for model events
     */
    protected static function boot()
    {
        parent::boot();

        // Auto-generate NIK jika kosong saat creating
        static::creating(function ($employee) {
            if (empty($employee->nik)) {
                $employee->nik = self::generateNIK();
            }

            // Default values
            $employee->is_active = $employee->is_active ?? true;
            $employee->status_kerja = $employee->status_kerja ?? 'Aktif';
        });

        // Update age ketika tanggal lahir berubah
        static::updating(function ($employee) {
            if ($employee->isDirty('tanggal_lahir') && $employee->tanggal_lahir) {
                $employee->usia = Carbon::parse($employee->tanggal_lahir)->age;
            }
        });
    }
}
