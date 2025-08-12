<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class BackgroundCheck extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_nip',
        'check_date',
        'check_type',
        'status',
        'valid_until',
        'notes',
        'conducted_by',
        'reference_number',
        'documents',
        'import_batch_id',
        'imported_at',
    ];

    protected $casts = [
        'check_date' => 'date',
        'valid_until' => 'date',
        'imported_at' => 'datetime',
        'documents' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $dates = [
        'check_date',
        'valid_until',
        'imported_at',
        'created_at',
        'updated_at',
    ];

    // Check status constants
    const STATUS_PENDING = 'pending';
    const STATUS_IN_PROGRESS = 'in_progress';
    const STATUS_PASSED = 'passed';
    const STATUS_FAILED = 'failed';
    const STATUS_EXPIRED = 'expired';
    const STATUS_REQUIRES_RENEWAL = 'requires_renewal';

    // Check type constants
    const CHECK_TYPES = [
        'security_clearance' => 'Security Clearance',
        'criminal_background' => 'Criminal Background Check',
        'employment_verification' => 'Employment Verification',
        'reference_check' => 'Reference Check',
        'periodic_review' => 'Periodic Review',
    ];

    // Relationships
    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_nip', 'nip');
    }

    // Scopes
    public function scopePassed($query)
    {
        return $query->where('status', self::STATUS_PASSED);
    }

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeFailed($query)
    {
        return $query->where('status', self::STATUS_FAILED);
    }

    public function scopeExpired($query)
    {
        return $query->where('status', self::STATUS_EXPIRED)
                    ->orWhere('valid_until', '<', Carbon::today());
    }

    public function scopeValid($query)
    {
        return $query->where('status', self::STATUS_PASSED)
                    ->where(function($q) {
                        $q->whereNull('valid_until')
                          ->orWhere('valid_until', '>', Carbon::today());
                    });
    }

    public function scopeExpiringSoon($query, $days = 30)
    {
        $today = Carbon::today();
        return $query->whereBetween('valid_until', [$today, $today->copy()->addDays($days)]);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('check_type', $type);
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    // Accessors
    public function getStatusTextAttribute()
    {
        return match($this->status) {
            self::STATUS_PENDING => 'Pending',
            self::STATUS_IN_PROGRESS => 'In Progress',
            self::STATUS_PASSED => 'Passed',
            self::STATUS_FAILED => 'Failed',
            self::STATUS_EXPIRED => 'Expired',
            self::STATUS_REQUIRES_RENEWAL => 'Requires Renewal',
            default => 'Unknown',
        };
    }

    public function getCheckTypeTextAttribute()
    {
        return self::CHECK_TYPES[$this->check_type] ?? $this->check_type;
    }

    public function getIsValidAttribute()
    {
        if ($this->status !== self::STATUS_PASSED) {
            return false;
        }

        if ($this->valid_until) {
            return Carbon::parse($this->valid_until)->isFuture();
        }

        return true;
    }

    public function getIsExpiringAttribute()
    {
        if (!$this->valid_until || $this->status !== self::STATUS_PASSED) {
            return false;
        }

        $daysUntilExpiry = Carbon::today()->diffInDays(Carbon::parse($this->valid_until), false);
        return $daysUntilExpiry >= 0 && $daysUntilExpiry <= 30;
    }

    public function getDaysUntilExpiryAttribute()
    {
        if (!$this->valid_until) {
            return null;
        }

        return Carbon::today()->diffInDays(Carbon::parse($this->valid_until), false);
    }

    // Helper methods
    public function isValid()
    {
        return $this->is_valid;
    }

    public function isExpiring($days = 30)
    {
        return $this->is_expiring;
    }

    public function hasDocuments()
    {
        return !empty($this->documents);
    }

    public function getDocumentUrls()
    {
        if (!$this->documents) {
            return [];
        }

        return collect($this->documents)->map(function ($doc) {
            return [
                'filename' => $doc['filename'] ?? 'Unknown',
                'url' => asset('storage/' . $doc['path']),
                'path' => $doc['path'],
            ];
        })->toArray();
    }
}
