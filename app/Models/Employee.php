<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Employee extends Model
{
    use HasFactory;

    protected $fillable = [
        'nip',
        'nik',
        'nama_lengkap',
        'jenis_kelamin',
        'tempat_lahir',
        'tanggal_lahir',
        'usia',
        'kota_domisili',
        'alamat',
        'handphone',
        'email',
        'lokasi_kerja',
        'cabang',
        'status_pegawai',
        'status_kerja',
        'provider',
        'kode_organisasi',
        'unit_organisasi',
        'nama_organisasi',
        'nama_jabatan',
        'jabatan',
        'kelompok_jabatan',
        'unit_kerja_kontrak',
        'tmt_mulai_kerja',
        'tmt_mulai_jabatan',
        'tmt_berakhir_jabatan',
        'masa_kerja_tahun',
        'masa_kerja_bulan',
        'masa_kerja_hari',
        'pendidikan_terakhir',
        'institusi_pendidikan',
        'jurusan',
        'tahun_lulus',
        'jenis_sepatu_pantofel',
        'ukuran_sepatu_pantofel',
        'jenis_sepatu_safety',
        'ukuran_sepatu_safety',
        'no_bpjs_kesehatan',
        'no_bpjs_ketenagakerjaan',
        'tinggi_badan',
        'berat_badan',
    ];

    protected $casts = [
        'tanggal_lahir' => 'date',
        'tmt_mulai_kerja' => 'date',
        'tmt_mulai_jabatan' => 'date',
        'tmt_berakhir_jabatan' => 'date',
        'usia' => 'integer',
        'masa_kerja_tahun' => 'integer',
        'masa_kerja_bulan' => 'integer',
        'masa_kerja_hari' => 'integer',
        'tahun_lulus' => 'integer',
        'ukuran_sepatu_pantofel' => 'integer',
        'ukuran_sepatu_safety' => 'integer',
        'tinggi_badan' => 'integer',
        'berat_badan' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // =========================================================================
    // TRAINING RELATIONSHIPS & METHODS
    // =========================================================================

    /**
     * Training records relationship
     */
    public function trainingRecords()
    {
        return $this->hasMany(TrainingRecord::class);
    }

    /**
     * Training types relationship (many-to-many through training_records)
     */
    public function trainingTypes()
    {
        return $this->belongsToMany(TrainingType::class, 'training_records')
                    ->withPivot('certificate_number', 'issue_date', 'expiry_date', 'completion_status')
                    ->withTimestamps();
    }

    /**
     * Background checks relationship
     */
    public function backgroundChecks()
    {
        return $this->hasMany(BackgroundCheck::class, 'employee_nip', 'nip');
    }

    // =========================================================================
    // TRAINING-RELATED ACCESSORS
    // =========================================================================

    /**
     * Get count of valid (non-expired) training records
     */
    public function getValidTrainingsCountAttribute()
    {
        return $this->trainingRecords()
                    ->where('expiry_date', '>', Carbon::today())
                    ->where('completion_status', 'completed')
                    ->count();
    }

    /**
     * Get count of expired training records
     */
    public function getExpiredTrainingsCountAttribute()
    {
        return $this->trainingRecords()
                    ->where('expiry_date', '<=', Carbon::today())
                    ->count();
    }

    /**
     * Get count of trainings due soon (within 30 days)
     */
    public function getDueSoonTrainingsCountAttribute()
    {
        $today = Carbon::today();
        return $this->trainingRecords()
                    ->whereBetween('expiry_date', [$today, $today->copy()->addDays(30)])
                    ->where('completion_status', 'completed')
                    ->count();
    }

    /**
     * Get total training records count
     */
    public function getTotalTrainingsAttribute()
    {
        return $this->trainingRecords()->count();
    }

    /**
     * Calculate training compliance rate
     */
    public function getTrainingComplianceRateAttribute()
    {
        $total = $this->total_trainings;
        $valid = $this->valid_trainings_count;

        return $total > 0 ? round(($valid / $total) * 100, 2) : 0;
    }

    // =========================================================================
    // TRAINING HELPER METHODS
    // =========================================================================

    /**
     * Check if employee has valid training for specific type
     */
    public function hasValidTraining($trainingTypeId)
    {
        return $this->trainingRecords()
                    ->where('training_type_id', $trainingTypeId)
                    ->where('expiry_date', '>', Carbon::today())
                    ->where('completion_status', 'completed')
                    ->exists();
    }

    /**
     * Get latest training record for specific type
     */
    public function getLatestTraining($trainingTypeId)
    {
        return $this->trainingRecords()
                    ->where('training_type_id', $trainingTypeId)
                    ->latest('issue_date')
                    ->first();
    }

    /**
     * Get trainings expiring within specified days
     */
    public function getExpiringTrainings($days = 30)
    {
        $today = Carbon::today();
        return $this->trainingRecords()
                    ->whereBetween('expiry_date', [$today, $today->copy()->addDays($days)])
                    ->where('completion_status', 'completed')
                    ->with('trainingType')
                    ->get();
    }

    /**
     * Get missing mandatory trainings for this employee
     */
    public function getMissingMandatoryTrainings()
    {
        $mandatoryTrainingTypes = TrainingType::where('is_mandatory', true)
                                            ->where('is_active', true)
                                            ->get();

        $employeeTrainingTypes = $this->trainingRecords()
                                    ->where('expiry_date', '>', Carbon::today())
                                    ->where('completion_status', 'completed')
                                    ->pluck('training_type_id');

        return $mandatoryTrainingTypes->whereNotIn('id', $employeeTrainingTypes);
    }

    /**
     * Get training summary for dashboard
     */
    public function getTrainingSummary()
    {
        return [
            'total' => $this->total_trainings,
            'valid' => $this->valid_trainings_count,
            'expired' => $this->expired_trainings_count,
            'due_soon' => $this->due_soon_trainings_count,
            'compliance_rate' => $this->training_compliance_rate,
            'missing_mandatory' => $this->getMissingMandatoryTrainings()->count(),
        ];
    }

    // =========================================================================
    // SCOPES FOR TRAINING QUERIES
    // =========================================================================

    /**
     * Scope: Employees with training records
     */
    public function scopeWithTrainings($query)
    {
        return $query->has('trainingRecords');
    }

    /**
     * Scope: Employees with valid trainings
     */
    public function scopeWithValidTrainings($query)
    {
        return $query->whereHas('trainingRecords', function($q) {
            $q->where('expiry_date', '>', Carbon::today())
              ->where('completion_status', 'completed');
        });
    }

    /**
     * Scope: Employees with expired trainings
     */
    public function scopeWithExpiredTrainings($query)
    {
        return $query->whereHas('trainingRecords', function($q) {
            $q->where('expiry_date', '<=', Carbon::today());
        });
    }

    /**
     * Scope: Employees by department
     */
    public function scopeByDepartment($query, $department)
    {
        return $query->where('unit_organisasi', $department);
    }

    /**
     * Scope: Active employees only
     */
    public function scopeActive($query)
    {
        return $query->where('status_kerja', 'Aktif');
    }

    // =========================================================================
    // EXISTING EMPLOYEE METHODS (if any)
    // =========================================================================

    // Add any existing employee methods here...
}
