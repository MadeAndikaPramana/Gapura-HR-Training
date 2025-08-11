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
        'tahun_lulus' => 'integer',
        'usia' => 'integer',
        'masa_kerja_tahun' => 'integer',
        'masa_kerja_bulan' => 'integer',
        'masa_kerja_hari' => 'integer',
        'tinggi_badan' => 'integer',
        'berat_badan' => 'integer',
        'ukuran_sepatu_pantofel' => 'integer',
        'ukuran_sepatu_safety' => 'integer',
    ];

    // Use NIP as primary key for training relationships
    protected $primaryKey = 'id';
    public $incrementing = true;

    // ===============================================
    // TRAINING SYSTEM RELATIONSHIPS
    // ===============================================

    public function trainingRecords()
    {
        return $this->hasMany(TrainingRecord::class, 'employee_nip', 'nip');
    }

    public function backgroundChecks()
    {
        return $this->hasMany(BackgroundCheck::class, 'employee_nip', 'nip');
    }

    public function activeTrainingRecords()
    {
        return $this->trainingRecords()->where('status', 'active');
    }

    public function expiredTrainingRecords()
    {
        return $this->trainingRecords()->expired();
    }

    public function expiringSoonTrainingRecords($days = 30)
    {
        return $this->trainingRecords()->expiringSoon($days);
    }

    public function latestBackgroundCheck()
    {
        return $this->hasOne(BackgroundCheck::class, 'employee_nip', 'nip')
                    ->latest('check_date');
    }

    public function validBackgroundChecks()
    {
        return $this->backgroundChecks()->passed()->where(function($query) {
            $query->whereNull('valid_until')
                  ->orWhere('valid_until', '>=', now()->toDateString());
        });
    }

    // ===============================================
    // SCOPES
    // ===============================================

    public function scopeActive($query)
    {
        return $query->where('status_kerja', 'Aktif');
    }

    public function scopeByDepartment($query, $department)
    {
        return $query->where('unit_organisasi', $department);
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status_pegawai', $status);
    }

    public function scopeByJobTitle($query, $jobTitle)
    {
        return $query->where('jabatan', $jobTitle);
    }

    public function scopeWithTrainingCompliance($query)
    {
        return $query->with([
            'trainingRecords.trainingType',
            'latestBackgroundCheck'
        ]);
    }

    public function scopeNeedsTraining($query)
    {
        return $query->whereDoesntHave('trainingRecords')
                     ->orWhereHas('trainingRecords', function($q) {
                         $q->where('status', '!=', 'active');
                     });
    }

    public function scopeTrainingExpiringSoon($query, $days = 30)
    {
        return $query->whereHas('trainingRecords', function($q) use ($days) {
            $q->expiringSoon($days);
        });
    }

    public function scopeCompliantEmployees($query)
    {
        $totalTrainingTypes = \App\Models\TrainingType::active()->count();

        return $query->whereHas('trainingRecords', function($q) use ($totalTrainingTypes) {
            $q->where('status', 'active');
        }, '>=', $totalTrainingTypes);
    }

    public function scopeWithValidBackgroundCheck($query)
    {
        return $query->whereHas('backgroundChecks', function($q) {
            $q->where('status', 'passed')
              ->where(function($subQ) {
                  $subQ->whereNull('valid_until')
                       ->orWhere('valid_until', '>=', now()->toDateString());
              });
        });
    }

    // ===============================================
    // ACCESSORS & MUTATORS
    // ===============================================

    public function getFullNameAttribute()
    {
        return $this->nama_lengkap;
    }

    public function getAgeCalculatedAttribute()
    {
        return $this->tanggal_lahir ? $this->tanggal_lahir->age : null;
    }

    public function getYearsOfServiceAttribute()
    {
        return $this->tmt_mulai_kerja ? $this->tmt_mulai_kerja->diffInYears(now()) : 0;
    }

    public function getTrainingComplianceStatusAttribute()
    {
        $totalTrainingTypes = \App\Models\TrainingType::active()->count();
        $activeTrainingCount = $this->activeTrainingRecords()->count();

        if ($activeTrainingCount === 0) return 'no_training';
        if ($activeTrainingCount < $totalTrainingTypes) return 'partial_compliance';

        // Check if any training is expiring soon
        if ($this->expiringSoonTrainingRecords()->exists()) return 'expiring_soon';

        return 'compliant';
    }

    public function getTrainingCompliancePercentageAttribute()
    {
        $totalTrainingTypes = \App\Models\TrainingType::active()->count();
        if ($totalTrainingTypes === 0) return 100;

        $activeTrainingCount = $this->activeTrainingRecords()->count();
        return round(($activeTrainingCount / $totalTrainingTypes) * 100);
    }

    public function getBackgroundCheckStatusAttribute()
    {
        $latestCheck = $this->latestBackgroundCheck;

        if (!$latestCheck) return 'no_check';
        if ($latestCheck->status !== 'passed') return 'failed';
        if ($latestCheck->valid_until && $latestCheck->valid_until->isPast()) return 'expired';
        if ($latestCheck->valid_until && $latestCheck->valid_until->diffInDays(now()) <= 60) return 'expiring_soon';

        return 'valid';
    }

    public function getTrainingComplianceColorAttribute()
    {
        return match($this->training_compliance_status) {
            'compliant' => 'green',
            'expiring_soon' => 'yellow',
            'partial_compliance' => 'orange',
            'no_training' => 'red',
            default => 'gray',
        };
    }

    public function getBackgroundCheckColorAttribute()
    {
        return match($this->background_check_status) {
            'valid' => 'green',
            'expiring_soon' => 'yellow',
            'expired' => 'red',
            'failed' => 'red',
            'no_check' => 'gray',
            default => 'gray',
        };
    }

    public function getInitialsAttribute()
    {
        $words = explode(' ', $this->nama_lengkap);
        $initials = '';
        foreach ($words as $word) {
            $initials .= strtoupper(substr($word, 0, 1));
        }
        return substr($initials, 0, 2); // Max 2 characters
    }

    // Auto-calculate age on save
    protected static function booted()
    {
        static::saving(function ($employee) {
            if ($employee->tanggal_lahir) {
                $employee->usia = Carbon::parse($employee->tanggal_lahir)->age;
            }

            // Calculate work experience
            if ($employee->tmt_mulai_kerja) {
                $workStart = Carbon::parse($employee->tmt_mulai_kerja);
                $now = now();

                $employee->masa_kerja_tahun = $workStart->diffInYears($now);
                $employee->masa_kerja_bulan = $workStart->diffInMonths($now) % 12;
                $employee->masa_kerja_hari = $workStart->copy()->addYears($employee->masa_kerja_tahun)->addMonths($employee->masa_kerja_bulan)->diffInDays($now);
            }
        });
    }

    // ===============================================
    // TRAINING HELPER METHODS
    // ===============================================

    public function hasValidTraining($trainingTypeCode = null)
    {
        $query = $this->activeTrainingRecords();

        if ($trainingTypeCode) {
            $query->whereHas('trainingType', function($q) use ($trainingTypeCode) {
                $q->where('code', $trainingTypeCode);
            });
        }

        return $query->exists();
    }

    public function getTrainingRecord($trainingTypeCode)
    {
        return $this->trainingRecords()
                    ->whereHas('trainingType', function($q) use ($trainingTypeCode) {
                        $q->where('code', $trainingTypeCode);
                    })
                    ->latest('valid_until')
                    ->first();
    }

    public function getMissingTrainingTypes()
    {
        $allTrainingTypes = \App\Models\TrainingType::active()->get();
        $employeeTrainingTypes = $this->activeTrainingRecords()
                                      ->with('trainingType')
                                      ->get()
                                      ->pluck('trainingType.id');

        return $allTrainingTypes->whereNotIn('id', $employeeTrainingTypes);
    }

    public function getTrainingMatrix()
    {
        $trainingTypes = \App\Models\TrainingType::active()->ordered()->get();
        $matrix = [];

        foreach ($trainingTypes as $type) {
            $record = $this->getTrainingRecord($type->code);
            $matrix[] = [
                'training_type' => $type,
                'record' => $record,
                'status' => $record ? $record->status_text : 'Not Completed',
                'color' => $record ? $record->status_badge_color : 'gray',
                'expires_at' => $record ? $record->valid_until : null,
                'days_until_expiry' => $record ? $record->days_until_expiry : null,
            ];
        }

        return $matrix;
    }

    public function canPerformDuty($requiredTrainingCodes = [])
    {
        // Check background check
        if ($this->background_check_status !== 'valid') {
            return false;
        }

        // Check required trainings
        foreach ($requiredTrainingCodes as $code) {
            if (!$this->hasValidTraining($code)) {
                return false;
            }
        }

        return true;
    }

    public function getTrainingGaps()
    {
        $missingTrainings = $this->getMissingTrainingTypes();
        $expiringTrainings = $this->expiringSoonTrainingRecords()->with('trainingType')->get();
        $expiredTrainings = $this->expiredTrainingRecords()->with('trainingType')->get();

        return [
            'missing' => $missingTrainings,
            'expiring' => $expiringTrainings,
            'expired' => $expiredTrainings,
            'total_gaps' => $missingTrainings->count() + $expiringTrainings->count() + $expiredTrainings->count(),
        ];
    }

    public function addTrainingRecord($trainingTypeId, $certificateNumber, $validFrom, $validUntil, $additionalData = [])
    {
        return $this->trainingRecords()->create(array_merge([
            'training_type_id' => $trainingTypeId,
            'certificate_number' => $certificateNumber,
            'valid_from' => $validFrom,
            'valid_until' => $validUntil,
            'status' => 'active',
        ], $additionalData));
    }

    public function addBackgroundCheck($checkDate, $checkType = 'security_clearance', $status = 'passed', $validUntil = null, $additionalData = [])
    {
        return $this->backgroundChecks()->create(array_merge([
            'check_date' => $checkDate,
            'check_type' => $checkType,
            'status' => $status,
            'valid_until' => $validUntil,
        ], $additionalData));
    }

    // ===============================================
    // STATIC METHODS
    // ===============================================

    public static function getTrainingComplianceReport($departmentFilter = null)
    {
        $query = self::query();

        if ($departmentFilter) {
            $query->byDepartment($departmentFilter);
        }

        $employees = $query->withTrainingCompliance()->get();

        $report = [
            'total_employees' => $employees->count(),
            'compliant' => $employees->where('training_compliance_status', 'compliant')->count(),
            'partial_compliance' => $employees->where('training_compliance_status', 'partial_compliance')->count(),
            'expiring_soon' => $employees->where('training_compliance_status', 'expiring_soon')->count(),
            'no_training' => $employees->where('training_compliance_status', 'no_training')->count(),
            'valid_background_checks' => $employees->where('background_check_status', 'valid')->count(),
        ];

        $report['compliance_percentage'] = $report['total_employees'] > 0
            ? round(($report['compliant'] / $report['total_employees']) * 100, 2)
            : 0;

        return $report;
    }

    public static function getDepartmentStats()
    {
        return self::selectRaw('unit_organisasi, COUNT(*) as total')
                   ->groupBy('unit_organisasi')
                   ->orderBy('total', 'desc')
                   ->get();
    }

    public static function getStatusDistribution()
    {
        return self::selectRaw('status_pegawai, COUNT(*) as total')
                   ->groupBy('status_pegawai')
                   ->orderBy('total', 'desc')
                   ->get();
    }
}
