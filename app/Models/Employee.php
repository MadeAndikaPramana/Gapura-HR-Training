<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class Employee extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'nik',
        'nip',
        'email',
        'phone',
        'department',
        'position',
        'hire_date',
        'birth_date',
        'address',
        'is_active',
    ];

    protected $casts = [
        'hire_date' => 'date',
        'birth_date' => 'date',
        'is_active' => 'boolean',
    ];

    // Relationships
    public function trainingRecords()
    {
        return $this->hasMany(TrainingRecord::class);
    }

    public function trainingTypes()
    {
        return $this->belongsToMany(TrainingType::class, 'training_records')
                    ->withPivot('certificate_number', 'issue_date', 'expiry_date', 'completion_status')
                    ->withTimestamps();
    }

    public function backgroundChecks()
    {
        return $this->hasMany(BackgroundCheck::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByDepartment($query, $department)
    {
        return $query->where('department', $department);
    }

    public function scopeByPosition($query, $position)
    {
        return $query->where('position', $position);
    }

    public function scopeSearch($query, $search)
    {
        return $query->where(function($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
              ->orWhere('nik', 'like', "%{$search}%")
              ->orWhere('nip', 'like', "%{$search}%")
              ->orWhere('email', 'like', "%{$search}%");
        });
    }

    // Training-related accessors
    public function getValidTrainingsCountAttribute()
    {
        return $this->trainingRecords()->valid()->count();
    }

    public function getExpiredTrainingsCountAttribute()
    {
        return $this->trainingRecords()->expired()->count();
    }

    public function getDueSoonTrainingsCountAttribute()
    {
        return $this->trainingRecords()->dueSoon()->count();
    }

    public function getTotalTrainingsAttribute()
    {
        return $this->trainingRecords()->count();
    }

    public function getTrainingComplianceRateAttribute()
    {
        $total = $this->total_trainings;
        $valid = $this->valid_trainings_count;

        return $total > 0 ? round(($valid / $total) * 100, 2) : 0;
    }

    public function getAgeAttribute()
    {
        return $this->birth_date ? $this->birth_date->age : null;
    }

    public function getYearsOfServiceAttribute()
    {
        return $this->hire_date ? $this->hire_date->diffInYears(Carbon::now()) : 0;
    }

    public function getFullNameAttribute()
    {
        return $this->name;
    }

    public function getDisplayNameAttribute()
    {
        return "{$this->name} ({$this->nip})";
    }

    // Helper methods for training
    public function hasValidTraining($trainingTypeId)
    {
        return $this->trainingRecords()
                    ->where('training_type_id', $trainingTypeId)
                    ->valid()
                    ->exists();
    }

    public function getLatestTraining($trainingTypeId)
    {
        return $this->trainingRecords()
                    ->where('training_type_id', $trainingTypeId)
                    ->latest('issue_date')
                    ->first();
    }

    public function getExpiringTrainings($days = 30)
    {
        return $this->trainingRecords()
                    ->dueSoon($days)
                    ->with('trainingType')
                    ->get();
    }

    public function getMissingMandatoryTrainings()
    {
        $mandatoryTrainingTypes = TrainingType::mandatory()->active()->get();
        $employeeTrainingTypes = $this->trainingRecords()->valid()->pluck('training_type_id');

        return $mandatoryTrainingTypes->whereNotIn('id', $employeeTrainingTypes);
    }

    public function getComplianceStatus()
    {
        $missingMandatory = $this->getMissingMandatoryTrainings();
        $expiredTrainings = $this->expired_trainings_count;
        $expiringSoon = $this->due_soon_trainings_count;

        if ($missingMandatory->count() > 0 || $expiredTrainings > 0) {
            return 'non-compliant';
        } elseif ($expiringSoon > 0) {
            return 'expiring-soon';
        } else {
            return 'compliant';
        }
    }

    public function getTrainingsByCategory()
    {
        return $this->trainingRecords()
                    ->with('trainingType')
                    ->get()
                    ->groupBy('trainingType.category');
    }

    public function getUpcomingRenewals($days = 60)
    {
        return $this->trainingRecords()
                    ->where('expiry_date', '<=', Carbon::now()->addDays($days))
                    ->where('expiry_date', '>', Carbon::now())
                    ->with('trainingType')
                    ->orderBy('expiry_date')
                    ->get();
    }

    public function getTotalTrainingCost()
    {
        return $this->trainingRecords()->sum('cost') ?? 0;
    }

    public function getFormattedTotalTrainingCostAttribute()
    {
        $total = $this->getTotalTrainingCost();
        return 'Rp ' . number_format($total, 0, ',', '.');
    }

    // Validation methods
    public function isCompliantWithMandatoryTraining()
    {
        return $this->getMissingMandatoryTrainings()->count() === 0;
    }

    public function hasExpiredTraining()
    {
        return $this->expired_trainings_count > 0;
    }

    public function hasExpiringSoonTraining($days = 30)
    {
        return $this->trainingRecords()->dueSoon($days)->count() > 0;
    }

    // Boot method
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            // Auto-generate email if not provided
            if (!$model->email && $model->name) {
                $name = strtolower(str_replace(' ', '.', $model->name));
                $model->email = $name . '@gapura.com';
            }
        });
    }
}
