<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TrainingType extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'description',
        'duration_months',
        'certificate_format',
        'requires_background_check',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'requires_background_check' => 'boolean',
        'is_active' => 'boolean',
        'duration_months' => 'integer',
        'sort_order' => 'integer',
    ];

    // Relationships
    public function trainingRecords()
    {
        return $this->hasMany(TrainingRecord::class);
    }

    public function activeTrainingRecords()
    {
        return $this->trainingRecords()->where('status', 'active');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    public function scopeByDuration($query, $months)
    {
        return $query->where('duration_months', $months);
    }

    // Accessors
    public function getDurationTextAttribute()
    {
        return $this->duration_months . ' bulan';
    }

    public function getFormattedNameAttribute()
    {
        return $this->name . ' (' . $this->duration_text . ')';
    }

    public function getShortCodeAttribute()
    {
        // Generate short code from name for certificate format
        $words = explode(' ', $this->name);
        return strtoupper(substr($words[0], 0, 3) . (isset($words[1]) ? substr($words[1], 0, 2) : ''));
    }

    // Helper methods
    public function getRecordCount()
    {
        return $this->trainingRecords()->count();
    }

    public function getActiveRecordCount()
    {
        return $this->activeTrainingRecords()->count();
    }

    public function getExpiredRecordCount()
    {
        return $this->trainingRecords()->where('status', 'expired')->count();
    }

    public function getExpiringSoonRecordCount($days = 30)
    {
        return $this->trainingRecords()
                    ->where('valid_until', '<=', now()->addDays($days)->toDateString())
                    ->where('valid_until', '>=', now()->toDateString())
                    ->count();
    }

    // Static methods
    public static function getTrainingTypeStats()
    {
        return self::active()
                   ->withCount([
                       'trainingRecords',
                       'trainingRecords as active_count' => function($query) {
                           $query->where('status', 'active');
                       },
                       'trainingRecords as expired_count' => function($query) {
                           $query->where('status', 'expired');
                       }
                   ])
                   ->get();
    }
}
