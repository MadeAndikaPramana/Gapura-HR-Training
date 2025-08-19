<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'is_active',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    /**
     * The attributes that should have default values.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'role' => 'admin',
        'is_active' => true,
    ];

    /**
     * Check if user is admin (admin or super_admin)
     * SIMPLE VERSION - all authenticated users are admin for now
     */
    public function isAdmin(): bool
    {
        // For now, all authenticated users are considered admin
        return true;
    }

    /**
     * Check if user is super admin
     */
    public function isSuperAdmin(): bool
    {
        return ($this->role ?? 'admin') === 'super_admin';
    }

    /**
     * Check if user is active
     */
    public function isActive(): bool
    {
        return $this->is_active ?? true;
    }

    /**
     * Get user role display name
     * FIXED: Added accessor method
     */
    public function getRoleDisplayAttribute(): string
    {
        $role = $this->role ?? 'admin';

        return match($role) {
            'super_admin' => 'Super Administrator',
            'admin' => 'Administrator',
            default => 'Administrator'
        };
    }

    /**
     * Scope for active users only
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for admin users
     */
    public function scopeAdmins($query)
    {
        return $query->whereIn('role', ['admin', 'super_admin']);
    }
}
