<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
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
        'documents' => 'array',
        'imported_at' => 'datetime',
    ];

    // Constants for check types and statuses
    const CHECK_TYPES = [
        'security_clearance' => 'Security Clearance',
        'criminal_background' => 'Criminal Background',
        'employment_verification' => 'Employment Verification',
        'reference_check' => 'Reference Check',
        'periodic_review' => 'Periodic Review',
    ];

    const STATUSES = [
        'pending' => 'Pending',
        'in_progress' => 'In Progress',
        'passed' => 'Passed',
        'failed' => 'Failed',
        'expired' => 'Expired',
        'requires_renewal' => 'Requires Renewal',
    ];

    // Relationships
    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_nip', 'nip');
    }

    // Scopes
    public function scopePassed($query)
    {
        return $query->where('status', 'passed');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    public function scopePending($query)
    {
        return $query->whereIn('status', ['pending', 'in_progress']);
    }

    public function scopeExpired($query)
    {
        return $query->where('valid_until', '<', now()->toDateString())
                     ->orWhere('status', 'expired');
    }

    public function scopeRequiresRenewal($query, $days = 60)
    {
        return $query->where('valid_until', '<=', now()->addDays($days)->toDateString())
                     ->where('valid_until', '>=', now()->toDateString())
                     ->where('status', 'passed');
    }

    public function scopeValid($query)
    {
        return $query->where('status', 'passed')
                     ->where(function($q) {
                         $q->whereNull('valid_until')
                           ->orWhere('valid_until', '>=', now()->toDateString());
                     });
    }

    public function scopeByCheckType($query, $type)
    {
        return $query->where('check_type', $type);
    }

    public function scopeByDepartment($query, $department)
    {
        return $query->whereHas('employee', function ($q) use ($department) {
            $q->where('unit_organisasi', $department);
        });
    }

    public function scopeRecent($query, $days = 30)
    {
        return $query->where('check_date', '>=', now()->subDays($days)->toDateString());
    }

    // Accessors
    public function getIsValidAttribute()
    {
        return $this->status === 'passed' &&
               (!$this->valid_until || $this->valid_until->isFuture());
    }

    public function getIsExpiredAttribute()
    {
        return $this->valid_until && $this->valid_until->isPast();
    }

    public function getRequiresRenewalAttribute()
    {
        if (!$this->valid_until || $this->status !== 'passed') return false;
        return $this->valid_until->diffInDays(now()) <= 60 && !$this->is_expired;
    }

    public function getDaysUntilExpiryAttribute()
    {
        if (!$this->valid_until) return null;
        $diff = $this->valid_until->diffInDays(now(), false);
        return $diff < 0 ? 0 : $diff;
    }

    public function getStatusBadgeColorAttribute()
    {
        if ($this->is_expired) return 'red';
        if ($this->requires_renewal) return 'yellow';

        return match($this->status) {
            'passed' => 'green',
            'failed' => 'red',
            'expired' => 'red',
            'requires_renewal' => 'yellow',
            'pending' => 'blue',
            'in_progress' => 'blue',
            default => 'gray',
        };
    }

    public function getStatusTextAttribute()
    {
        if ($this->is_expired) return 'Expired';
        if ($this->requires_renewal) return 'Requires Renewal';

        return self::STATUSES[$this->status] ?? ucfirst(str_replace('_', ' ', $this->status));
    }

    public function getCheckTypeTextAttribute()
    {
        return self::CHECK_TYPES[$this->check_type] ?? ucfirst(str_replace('_', ' ', $this->check_type));
    }

    public function getEmployeeNameAttribute()
    {
        return $this->employee ? $this->employee->nama_lengkap : 'Unknown';
    }

    public function getValidityPeriodAttribute()
    {
        if (!$this->check_date) return null;

        $from = $this->check_date->format('d M Y');
        $until = $this->valid_until ? $this->valid_until->format('d M Y') : 'Permanent';

        return $from . ' - ' . $until;
    }

    // Auto-update status based on dates
    protected static function booted()
    {
        static::saving(function ($backgroundCheck) {
            if ($backgroundCheck->valid_until && $backgroundCheck->status === 'passed') {
                $now = now()->toDateString();
                $renewalWarningDate = now()->addDays(60)->toDateString();

                if ($backgroundCheck->valid_until < $now) {
                    $backgroundCheck->status = 'expired';
                } elseif ($backgroundCheck->valid_until <= $renewalWarningDate) {
                    $backgroundCheck->status = 'requires_renewal';
                }
            }
        });
    }

    // Helper methods
    public function renew($newValidUntil, $notes = null)
    {
        $this->update([
            'valid_until' => $newValidUntil,
            'status' => 'passed',
            'check_date' => now()->toDateString(),
            'notes' => $notes ? ($this->notes . "\nRenewed: " . $notes) : $this->notes,
        ]);
    }

    public function markAsExpired($reason = null)
    {
        $this->update([
            'status' => 'expired',
            'notes' => $reason ? ($this->notes . "\nExpired: " . $reason) : $this->notes,
        ]);
    }

    public function addDocument($documentPath, $documentType = null)
    {
        $documents = $this->documents ?? [];
        $documents[] = [
            'path' => $documentPath,
            'type' => $documentType,
            'uploaded_at' => now()->toISOString(),
        ];

        $this->update(['documents' => $documents]);
    }

    // Static helper methods
    public static function getComplianceStats($departmentFilter = null)
    {
        $query = self::query();

        if ($departmentFilter) {
            $query->byDepartment($departmentFilter);
        }

        $total = $query->count();
        $valid = $query->clone()->valid()->count();
        $expired = $query->clone()->expired()->count();
        $requiresRenewal = $query->clone()->requiresRenewal()->count();
        $pending = $query->clone()->pending()->count();

        return [
            'total' => $total,
            'valid' => $valid,
            'expired' => $expired,
            'requires_renewal' => $requiresRenewal,
            'pending' => $pending,
            'compliance_rate' => $total > 0 ? round(($valid / $total) * 100, 2) : 0
        ];
    }

    public static function getExpiringChecks($days = 60)
    {
        return self::with(['employee'])
                   ->requiresRenewal($days)
                   ->orderBy('valid_until')
                   ->get();
    }

    public static function getExpiredChecks()
    {
        return self::with(['employee'])
                   ->expired()
                   ->orderBy('valid_until', 'desc')
                   ->get();
    }

    public static function getCheckTypeOptions()
    {
        return self::CHECK_TYPES;
    }

    public static function getStatusOptions()
    {
        return self::STATUSES;
    }
}
