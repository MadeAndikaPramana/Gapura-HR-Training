<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class BackgroundCheck extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'employee_id',
        'check_type',
        'status',
        'issue_date',
        'expiry_date',
        'authority',
        'reference_number',
        'notes',
        'document_path',
    ];

    protected $casts = [
        'issue_date' => 'datetime',
        'expiry_date' => 'datetime',
    ];

    // Constants for check types
    const CHECK_TYPES = [
        'SECURITY_CLEARANCE' => 'Security Clearance',
        'CRIMINAL_BACKGROUND' => 'Criminal Background Check',
        'MEDICAL_CLEARANCE' => 'Medical Clearance',
        'REFERENCE_CHECK' => 'Reference Check',
        'EDUCATION_VERIFICATION' => 'Education Verification',
    ];

    // Constants for status
    const STATUSES = [
        'PENDING' => 'Pending',
        'IN_PROGRESS' => 'In Progress',
        'APPROVED' => 'Approved',
        'REJECTED' => 'Rejected',
        'EXPIRED' => 'Expired',
        'SUSPENDED' => 'Suspended',
    ];

    // Relationships
    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    // Scopes
    public function scopeValid($query)
    {
        return $query->where('expiry_date', '>', Carbon::now())
                    ->where('status', 'APPROVED');
    }

    public function scopeExpired($query)
    {
        return $query->where('expiry_date', '<=', Carbon::now());
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'APPROVED');
    }

    public function scopeByType($query, $type)
    {
        return $query->where('check_type', $type);
    }

    public function scopeByAuthority($query, $authority)
    {
        return $query->where('authority', $authority);
    }

    // Accessors
    public function getIsValidAttribute()
    {
        return $this->expiry_date > Carbon::now() && $this->status === 'APPROVED';
    }

    public function getIsExpiredAttribute()
    {
        return $this->expiry_date <= Carbon::now();
    }

    public function getStatusLabelAttribute()
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function getCheckTypeLabelAttribute()
    {
        return self::CHECK_TYPES[$this->check_type] ?? $this->check_type;
    }

    public function getDaysUntilExpiryAttribute()
    {
        return Carbon::now()->diffInDays($this->expiry_date, false);
    }

    // Methods
    public function isExpiringSoon($days = 30)
    {
        return $this->expiry_date <= Carbon::now()->addDays($days) &&
               $this->expiry_date > Carbon::now();
    }

    public function getStatusBadgeClass()
    {
        switch ($this->status) {
            case 'APPROVED':
                return 'badge-gapura-green';
            case 'PENDING':
            case 'IN_PROGRESS':
                return 'badge-gapura-yellow';
            case 'REJECTED':
            case 'EXPIRED':
            case 'SUSPENDED':
                return 'badge-gapura-red';
            default:
                return 'badge-gapura-blue';
        }
    }

    // Boot method
    protected static function boot()
    {
        parent::boot();

        static::updating(function ($model) {
            // Auto-expire if past expiry date
            if ($model->expiry_date <= Carbon::now() && $model->status === 'APPROVED') {
                $model->status = 'EXPIRED';
            }
        });
    }
}
