<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Employee extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'nama_lengkap',
        'nip',
        'nik',
        'unit_organisasi',
        'jabatan',
        'status_kerja',
        'tanggal_masuk',
        'email',
        'handphone',
        'alamat',
        'tempat_lahir',
        'tanggal_lahir',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'tanggal_masuk' => 'date',
        'tanggal_lahir' => 'date',
    ];

    /**
     * Default attributes
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status_kerja' => 'Aktif',
        'unit_organisasi' => 'GAPURA ANGKASA',
        'jabatan' => 'Staff',
    ];

    // =========================================================================
    // RELATIONSHIPS
    // =========================================================================

    /**
     * Get the training records for the employee
     */
    public function trainingRecords()
    {
        return $this->hasMany(TrainingRecord::class);
    }

    /**
     * Get the background checks for the employee
     */
    public function backgroundChecks()
    {
        return $this->hasMany(BackgroundCheck::class);
    }

    // =========================================================================
    // SCOPES
    // =========================================================================

    /**
     * Scope: Active employees only
     */
    public function scopeActive($query)
    {
        return $query->where('status_kerja', 'Aktif');
    }

    /**
     * Scope: Employees by department
     */
    public function scopeByDepartment($query, $department)
    {
        return $query->where('unit_organisasi', $department);
    }

    /**
     * Scope: Search employees
     */
    public function scopeSearch($query, $search)
    {
        return $query->where(function($q) use ($search) {
            $q->where('nama_lengkap', 'like', "%{$search}%")
              ->orWhere('nip', 'like', "%{$search}%")
              ->orWhere('nik', 'like', "%{$search}%");
        });
    }

    // =========================================================================
    // ACCESSORS & MUTATORS
    // =========================================================================

    /**
     * Get the employee's full display name
     */
    public function getDisplayNameAttribute()
    {
        return $this->nama_lengkap . ' (' . $this->nip . ')';
    }

    /**
     * Get the employee's work duration in years
     */
    public function getWorkDurationAttribute()
    {
        if (!$this->tanggal_masuk) {
            return 0;
        }

        return Carbon::parse($this->tanggal_masuk)->diffInYears(Carbon::now());
    }

    /**
     * Format NIP automatically
     */
    public function setNipAttribute($value)
    {
        $this->attributes['nip'] = strtoupper(trim($value));
    }

    /**
     * Format NIK automatically
     */
    public function setNikAttribute($value)
    {
        $this->attributes['nik'] = trim($value);
    }

    /**
     * Format nama_lengkap automatically
     */
    public function setNamaLengkapAttribute($value)
    {
        $this->attributes['nama_lengkap'] = ucwords(strtolower(trim($value)));
    }

    // =========================================================================
    // TRAINING RELATED METHODS (Simple)
    // =========================================================================

    /**
     * Get count of valid training records
     */
    public function getValidTrainingsCountAttribute()
    {
        return $this->trainingRecords()
                   ->where('expiry_date', '>', Carbon::now())
                   ->count();
    }

    /**
     * Get count of expired training records
     */
    public function getExpiredTrainingsCountAttribute()
    {
        return $this->trainingRecords()
                   ->where('expiry_date', '<=', Carbon::now())
                   ->count();
    }

    /**
     * Get count of total training records
     */
    public function getTotalTrainingsCountAttribute()
    {
        return $this->trainingRecords()->count();
    }

    /**
     * Check if employee has any training
     */
    public function hasTraining()
    {
        return $this->trainingRecords()->exists();
    }

    /**
     * Get training summary
     */
    public function getTrainingSummary()
    {
        return [
            'total' => $this->total_trainings_count,
            'valid' => $this->valid_trainings_count,
            'expired' => $this->expired_trainings_count,
        ];
    }

    // =========================================================================
    // STATIC METHODS
    // =========================================================================

    /**
     * Get employee by NIP
     */
    public static function findByNip($nip)
    {
        return static::where('nip', $nip)->first();
    }

    /**
     * Get employee by NIK
     */
    public static function findByNik($nik)
    {
        return static::where('nik', $nik)->first();
    }

    /**
     * Get all departments
     */
    public static function getAllDepartments()
    {
        return static::whereNotNull('unit_organisasi')
                    ->distinct()
                    ->pluck('unit_organisasi')
                    ->filter()
                    ->sort()
                    ->values();
    }

    /**
     * Get all positions
     */
    public static function getAllPositions()
    {
        return static::whereNotNull('jabatan')
                    ->distinct()
                    ->pluck('jabatan')
                    ->filter()
                    ->sort()
                    ->values();
    }

    // =========================================================================
    // SERIALIZATION
    // =========================================================================

    /**
     * Get the array representation with minimal data
     */
    public function toMinimalArray()
    {
        return [
            'id' => $this->id,
            'nama_lengkap' => $this->nama_lengkap,
            'nip' => $this->nip,
            'nik' => $this->nik,
            'display_name' => $this->display_name,
        ];
    }
}
