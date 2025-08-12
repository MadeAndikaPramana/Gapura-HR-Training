<?php
// app/Models/TrainingType.php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TrainingType extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'category',
        'description',
        'validity_period',
        'is_mandatory',
        'is_active',
        'compliance_level',
        'training_provider_default',
        'cost_estimate',
        'requirements',
        'renewal_required',
        'notification_days',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'validity_period' => 'integer',
        'is_mandatory' => 'boolean',
        'is_active' => 'boolean',
        'renewal_required' => 'boolean',
        'cost_estimate' => 'decimal:2',
        'notification_days' => 'integer',
        'requirements' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    // Training categories constants
    const CATEGORIES = [
        'SAFETY' => 'Safety & Security',
        'OPERATIONAL' => 'Operational',
        'TECHNICAL' => 'Technical',
        'REGULATORY' => 'Regulatory Compliance',
        'MANAGEMENT' => 'Management',
        'SPECIALIZED' => 'Specialized Skills',
        'RECURRENT' => 'Recurrent Training',
    ];

    // Compliance levels
    const COMPLIANCE_LEVELS = [
        'CRITICAL' => 'Critical - Must have',
        'HIGH' => 'High Priority',
        'MEDIUM' => 'Medium Priority',
        'LOW' => 'Low Priority',
        'OPTIONAL' => 'Optional',
    ];

    // Relationships
    public function trainingRecords()
    {
        return $this->hasMany(TrainingRecord::class);
    }

    public function employees()
    {
        return $this->belongsToMany(Employee::class, 'training_records')
                    ->withPivot('certificate_number', 'issue_date', 'expiry_date', 'completion_status')
                    ->withTimestamps();
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeMandatory($query)
    {
        return $query->where('is_mandatory', true);
    }

    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    public function scopeByCriticality($query, $level)
    {
        return $query->where('compliance_level', $level);
    }

    // Accessors
    public function getCategoryNameAttribute()
    {
        return self::CATEGORIES[$this->category] ?? $this->category;
    }

    public function getComplianceLevelNameAttribute()
    {
        return self::COMPLIANCE_LEVELS[$this->compliance_level] ?? $this->compliance_level;
    }

    public function getActiveRecordsCountAttribute()
    {
        return $this->trainingRecords()->valid()->count();
    }

    public function getExpiredRecordsCountAttribute()
    {
        return $this->trainingRecords()->expired()->count();
    }

    public function getDueSoonRecordsCountAttribute()
    {
        return $this->trainingRecords()->dueSoon($this->notification_days ?? 30)->count();
    }

    public function getTotalRecordsCountAttribute()
    {
        return $this->trainingRecords()->count();
    }

    // Helper methods
    public function getComplianceRate()
    {
        $total = $this->total_records_count;
        $valid = $this->active_records_count;

        return $total > 0 ? round(($valid / $total) * 100, 2) : 0;
    }

    public function getEmployeesRequiringTraining($departmentFilter = null)
    {
        $query = Employee::where('status_kerja', 'Aktif');

        if ($departmentFilter) {
            $query->where('unit_organisasi', $departmentFilter);
        }

        // Get employees who don't have this training or have expired certificates
        return $query->whereDoesntHave('trainingRecords', function ($q) {
            $q->where('training_type_id', $this->id)->valid();
        })->get();
    }

    public function getUpcomingExpirations($days = null)
    {
        $notificationDays = $days ?? $this->notification_days ?? 30;

        return $this->trainingRecords()
                    ->dueSoon($notificationDays)
                    ->with('employee')
                    ->get();
    }

    // Boot method
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (!$model->notification_days) {
                $model->notification_days = 30; // Default notification period
            }
        });
    }
}

// Update app/Models/Employee.php to add training relationships
namespace App\Models;

// Add this method to the existing Employee model
class Employee extends Model
{
    // ... existing code ...

    // Training relationships
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
}
