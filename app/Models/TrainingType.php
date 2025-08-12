<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class TrainingType extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'code',
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
        'sort_order',
    ];

    protected $casts = [
        'is_mandatory' => 'boolean',
        'is_active' => 'boolean',
        'renewal_required' => 'boolean',
        'requirements' => 'array',
        'validity_period' => 'integer',
        'notification_days' => 'integer',
        'cost_estimate' => 'decimal:2',
        'sort_order' => 'integer',
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

    public function scopeByComplianceLevel($query, $level)
    {
        return $query->where('compliance_level', $level);
    }

    // Accessors
    public function getValidityPeriodInDaysAttribute()
    {
        return $this->validity_period * 30; // Approximate days
    }

    public function getRequirementsListAttribute()
    {
        return is_array($this->requirements) ? implode(', ', $this->requirements) : $this->requirements;
    }

    public function getFormattedCostAttribute()
    {
        if (!$this->cost_estimate) {
            return 'N/A';
        }

        return 'Rp ' . number_format($this->cost_estimate, 0, ',', '.');
    }

    // Methods
    public function getTotalRecordsCount()
    {
        return $this->trainingRecords()->count();
    }

    public function getValidRecordsCount()
    {
        return $this->trainingRecords()
                    ->where('expiry_date', '>', Carbon::now())
                    ->count();
    }

    public function getExpiredRecordsCount()
    {
        return $this->trainingRecords()
                    ->where('expiry_date', '<=', Carbon::now())
                    ->count();
    }

    public function getExpiringRecordsCount($days = 30)
    {
        return $this->trainingRecords()
                    ->whereBetween('expiry_date', [
                        Carbon::now(),
                        Carbon::now()->addDays($days)
                    ])
                    ->count();
    }

    public function getComplianceRate()
    {
        $total = $this->getTotalRecordsCount();
        $valid = $this->getValidRecordsCount();

        return $total > 0 ? round(($valid / $total) * 100, 2) : 0;
    }

    public function getEmployeesWithValidTraining()
    {
        return $this->employees()
                    ->wherePivot('expiry_date', '>', Carbon::now())
                    ->get();
    }

    public function getEmployeesWithExpiredTraining()
    {
        return $this->employees()
                    ->wherePivot('expiry_date', '<=', Carbon::now())
                    ->get();
    }

    public function getExpiringCertificates($days = null)
    {
        $notificationDays = $days ?: $this->notification_days ?? 30;

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
