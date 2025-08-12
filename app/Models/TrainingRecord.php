<?php

// app/Models/TrainingRecord.php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class TrainingRecord extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'employee_id',
        'training_type_id',
        'certificate_number',
        'training_provider',
        'issue_date',
        'expiry_date',
        'validity_period',
        'training_location',
        'training_duration',
        'instructor_name',
        'completion_status',
        'training_cost',
        'internal_external',
        'batch_id',
        'notes',
        'compliance_requirements',
        'renewal_required',
        'notification_before_expiry',
        'certificate_file',
        'supporting_documents',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'issue_date' => 'date',
        'expiry_date' => 'date',
        'training_cost' => 'decimal:2',
        'renewal_required' => 'boolean',
        'notification_before_expiry' => 'integer',
        'supporting_documents' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    protected $dates = [
        'issue_date',
        'expiry_date',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    // Relationships
    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function trainingType()
    {
        return $this->belongsTo(TrainingType::class);
    }

    // Scopes
    public function scopeValid($query)
    {
        return $query->where('expiry_date', '>', Carbon::today());
    }

    public function scopeExpired($query)
    {
        return $query->where('expiry_date', '<=', Carbon::today());
    }

    public function scopeDueSoon($query, $days = 30)
    {
        $today = Carbon::today();
        return $query->whereBetween('expiry_date', [$today, $today->copy()->addDays($days)]);
    }

    public function scopeByTrainingType($query, $trainingTypeId)
    {
        return $query->where('training_type_id', $trainingTypeId);
    }

    public function scopeByEmployee($query, $employeeId)
    {
        return $query->where('employee_id', $employeeId);
    }

    public function scopeCompleted($query)
    {
        return $query->where('completion_status', 'completed');
    }

    public function scopeInternal($query)
    {
        return $query->where('internal_external', 'internal');
    }

    public function scopeExternal($query)
    {
        return $query->where('internal_external', 'external');
    }

    // Accessors & Mutators
    public function getStatusAttribute()
    {
        $today = Carbon::today();
        $expiryDate = Carbon::parse($this->expiry_date);
        $daysUntilExpiry = $today->diffInDays($expiryDate, false);

        if ($daysUntilExpiry < 0) {
            return 'expired';
        } elseif ($daysUntilExpiry <= 30) {
            return 'due_soon';
        } else {
            return 'valid';
        }
    }

    public function getStatusTextAttribute()
    {
        switch ($this->status) {
            case 'expired':
                return 'Expired';
            case 'due_soon':
                return 'Due Soon';
            case 'valid':
                return 'Valid';
            default:
                return 'Unknown';
        }
    }

    public function getDaysUntilExpiryAttribute()
    {
        $today = Carbon::today();
        $expiryDate = Carbon::parse($this->expiry_date);
        return $today->diffInDays($expiryDate, false);
    }

    public function getIsExpiredAttribute()
    {
        return Carbon::parse($this->expiry_date)->isPast();
    }

    public function getIsDueSoonAttribute()
    {
        $daysUntilExpiry = $this->days_until_expiry;
        return $daysUntilExpiry >= 0 && $daysUntilExpiry <= ($this->notification_before_expiry ?? 30);
    }

    public function getCertificateFileUrlAttribute()
    {
        return $this->certificate_file ? asset('storage/' . $this->certificate_file) : null;
    }

    public function getSupportingDocumentsUrlsAttribute()
    {
        if (!$this->supporting_documents) {
            return [];
        }

        return collect($this->supporting_documents)->map(function ($doc) {
            return [
                'filename' => $doc['filename'],
                'url' => asset('storage/' . $doc['path']),
                'path' => $doc['path'],
            ];
        })->toArray();
    }

    // Helper methods
    public function shouldSendExpiryNotification()
    {
        if (!$this->renewal_required) {
            return false;
        }

        $daysUntilExpiry = $this->days_until_expiry;
        $notificationDays = $this->notification_before_expiry ?? 30;

        return $daysUntilExpiry >= 0 && $daysUntilExpiry <= $notificationDays;
    }

    public function canBeRenewed()
    {
        return $this->renewal_required && $this->completion_status === 'completed';
    }

    public function calculateExpiryDate($issueDate = null, $validityPeriod = null)
    {
        $issue = $issueDate ? Carbon::parse($issueDate) : Carbon::parse($this->issue_date);
        $validity = $validityPeriod ?? $this->validity_period ?? $this->trainingType->validity_period ?? 24;

        return $issue->addMonths($validity);
    }

    // Boot method for model events
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            // Auto-calculate expiry date if not provided
            if (!$model->expiry_date && $model->issue_date && $model->validity_period) {
                $model->expiry_date = $model->calculateExpiryDate($model->issue_date, $model->validity_period);
            }
        });

        static::updating(function ($model) {
            // Recalculate expiry date if issue date or validity period changed
            if ($model->isDirty(['issue_date', 'validity_period']) && $model->issue_date && $model->validity_period) {
                $model->expiry_date = $model->calculateExpiryDate($model->issue_date, $model->validity_period);
            }
        });
    }
}
