<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class TrainingRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_nip',
        'training_type_id',
        'certificate_number',
        'issued_date',
        'valid_from',
        'valid_until',
        'status',
        'notes',
        'issuing_authority',
        'training_location',
        'instructor',
        'import_batch_id',
        'imported_at',
    ];

    protected $casts = [
        'issued_date' => 'date',
        'valid_from' => 'date',
        'valid_until' => 'date',
        'imported_at' => 'datetime',
    ];

    // Relationships
    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_nip', 'nip');
    }

    public function trainingType()
    {
        return $this->belongsTo(TrainingType::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeExpired($query)
    {
        return $query->where('valid_until', '<', now()->toDateString())
                     ->orWhere('status', 'expired');
    }

    public function scopeExpiringSoon($query, $days = 30)
    {
        return $query->where('valid_until', '<=', now()->addDays($days)->toDateString())
                     ->where('valid_until', '>=', now()->toDateString())
                     ->where('status', '!=', 'expired');
    }

    public function scopeByDepartment($query, $department)
    {
        return $query->whereHas('employee', function ($q) use ($department) {
            $q->where('unit_organisasi', $department);
        });
    }

    public function scopeByTrainingType($query, $trainingTypeId)
    {
        return $query->where('training_type_id', $trainingTypeId);
    }

    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('valid_from', [$startDate, $endDate]);
    }

    public function scopeValidCertificates($query)
    {
        return $query->where('status', 'active')
                     ->where('valid_until', '>=', now()->toDateString());
    }

    // Accessors
    public function getIsExpiredAttribute()
    {
        return $this->valid_until && $this->valid_until->isPast();
    }

    public function getIsExpiringSoonAttribute()
    {
        if (!$this->valid_until) return false;
        return $this->valid_until->diffInDays(now()) <= 30 && !$this->is_expired;
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
        if ($this->is_expiring_soon) return 'yellow';

        return match($this->status) {
            'active' => 'green',
            'expired' => 'red',
            'expiring_soon' => 'yellow',
            'suspended' => 'gray',
            default => 'gray',
        };
    }

    public function getStatusTextAttribute()
    {
        if ($this->is_expired) return 'Expired';
        if ($this->is_expiring_soon) return 'Expiring Soon';

        return match($this->status) {
            'active' => 'Active',
            'expired' => 'Expired',
            'expiring_soon' => 'Expiring Soon',
            'suspended' => 'Suspended',
            default => ucfirst($this->status),
        };
    }

    public function getValidityPeriodAttribute()
    {
        if (!$this->valid_from || !$this->valid_until) return null;

        return $this->valid_from->format('d M Y') . ' - ' . $this->valid_until->format('d M Y');
    }

    public function getEmployeeNameAttribute()
    {
        return $this->employee ? $this->employee->nama_lengkap : 'Unknown';
    }

    public function getTrainingTypeNameAttribute()
    {
        return $this->trainingType ? $this->trainingType->name : 'Unknown';
    }

    // Auto-update status based on dates
    protected static function booted()
    {
        static::saving(function ($trainingRecord) {
            if ($trainingRecord->valid_until) {
                $now = now()->toDateString();
                $expiryWarningDate = now()->addDays(30)->toDateString();

                if ($trainingRecord->valid_until < $now) {
                    $trainingRecord->status = 'expired';
                } elseif ($trainingRecord->valid_until <= $expiryWarningDate) {
                    $trainingRecord->status = 'expiring_soon';
                } else {
                    $trainingRecord->status = 'active';
                }
            }
        });

        // Update all records periodically (can be called via scheduled job)
        static::updated(function ($trainingRecord) {
            // Log status changes if needed
        });
    }

    // Helper methods
    public function renewCertificate($newValidUntil, $newCertificateNumber = null)
    {
        $this->update([
            'valid_until' => $newValidUntil,
            'certificate_number' => $newCertificateNumber ?? $this->certificate_number,
            'status' => 'active',
        ]);
    }

    public function suspend($reason = null)
    {
        $this->update([
            'status' => 'suspended',
            'notes' => $reason ? ($this->notes . "\nSuspended: " . $reason) : $this->notes,
        ]);
    }

    public function reactivate()
    {
        // Auto-determine status based on expiry date
        $this->save(); // This will trigger the booted() method to set correct status
    }

    // Static helper methods
    public static function getComplianceStats($departmentFilter = null)
    {
        $query = self::query();

        if ($departmentFilter) {
            $query->byDepartment($departmentFilter);
        }

        return [
            'total' => $query->count(),
            'active' => $query->clone()->active()->count(),
            'expired' => $query->clone()->expired()->count(),
            'expiring_soon' => $query->clone()->expiringSoon()->count(),
            'compliance_rate' => $query->count() > 0
                ? round(($query->clone()->active()->count() / $query->count()) * 100, 2)
                : 0
        ];
    }

    public static function getExpiringCertificates($days = 30)
    {
        return self::with(['employee', 'trainingType'])
                   ->expiringSoon($days)
                   ->orderBy('valid_until')
                   ->get();
    }

    public static function getExpiredCertificates()
    {
        return self::with(['employee', 'trainingType'])
                   ->expired()
                   ->orderBy('valid_until', 'desc')
                   ->get();
    }
}
