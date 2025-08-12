<?php

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
        'issue_date',
        'expiry_date',
        'completion_status',
        'training_provider',
        'cost',
        'notes',
        'certificate_path',
        'previous_training_id',
    ];

    protected $casts = [
        'issue_date' => 'datetime',
        'expiry_date' => 'datetime',
        'cost' => 'decimal:2',
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

    public function previousTraining()
    {
        return $this->belongsTo(TrainingRecord::class, 'previous_training_id');
    }

    public function renewals()
    {
        return $this->hasMany(TrainingRecord::class, 'previous_training_id');
    }

    // Scopes
    public function scopeValid($query)
    {
        return $query->where('expiry_date', '>', Carbon::now());
    }

    public function scopeExpired($query)
    {
        return $query->where('expiry_date', '<=', Carbon::now());
    }

    public function scopeDueSoon($query, $days = 30)
    {
        return $query->whereBetween('expiry_date', [
            Carbon::now(),
            Carbon::now()->addDays($days)
        ]);
    }

    public function scopeCompleted($query)
    {
        return $query->where('completion_status', 'COMPLETED');
    }

    public function scopeByDepartment($query, $department)
    {
        return $query->whereHas('employee', function($q) use ($department) {
            $q->where('department', $department);
        });
    }

    public function scopeByTrainingType($query, $trainingTypeId)
    {
        return $query->where('training_type_id', $trainingTypeId);
    }

    // Accessors
    public function getIsValidAttribute()
    {
        return $this->expiry_date > Carbon::now();
    }

    public function getIsExpiredAttribute()
    {
        return $this->expiry_date <= Carbon::now();
    }

    public function getIsExpiringSoonAttribute()
    {
        $notificationDays = $this->trainingType->notification_days ?? 30;
        return $this->expiry_date <= Carbon::now()->addDays($notificationDays) &&
               $this->expiry_date > Carbon::now();
    }

    public function getDaysUntilExpiryAttribute()
    {
        return Carbon::now()->diffInDays($this->expiry_date, false);
    }

    public function getStatusAttribute()
    {
        if ($this->is_expired) {
            return 'Expired';
        } elseif ($this->is_expiring_soon) {
            return 'Expiring Soon';
        } else {
            return 'Valid';
        }
    }

    public function getFormattedCostAttribute()
    {
        if (!$this->cost) {
            return 'N/A';
        }

        return 'Rp ' . number_format($this->cost, 0, ',', '.');
    }

    // Methods
    public function isRenewalRequired()
    {
        return $this->trainingType->renewal_required && $this->is_expiring_soon;
    }

    public function canBeRenewed()
    {
        return $this->completion_status === 'COMPLETED' &&
               $this->expiry_date > Carbon::now()->subDays(90);
    }

    public function getRenewalDate()
    {
        return $this->expiry_date->subDays($this->trainingType->notification_days ?? 30);
    }

    public function getValidityDuration()
    {
        return $this->issue_date->diffInDays($this->expiry_date);
    }

    // Boot method
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            // Auto-generate certificate number if not provided
            if (!$model->certificate_number && $model->trainingType) {
                $model->certificate_number = $model->generateCertificateNumber();
            }
        });
    }

    /**
     * Generate certificate number in GAPURA format
     */
    private function generateCertificateNumber()
    {
        $year = $this->issue_date->format('Y');
        $month = $this->issue_date->format('m');

        // Get sequence number for this type and month
        $sequence = self::where('training_type_id', $this->training_type_id)
            ->whereYear('issue_date', $year)
            ->whereMonth('issue_date', $month)
            ->whereNotNull('certificate_number')
            ->count() + 1;

        // Format: GLC/OPR-{sequence}/{month}/{year}
        return sprintf('GLC/OPR-%06d/%02d/%s', $sequence, $month, $year);
    }
}
