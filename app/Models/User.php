<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

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
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'is_active' => 'boolean',
    ];

    // Constants for roles
    const ROLES = [
        'super_admin' => 'Super Admin',
        'admin' => 'Admin',
        'staff' => 'Staff',
        'user' => 'User',
    ];

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByRole($query, $role)
    {
        return $query->where('role', $role);
    }

    // Accessors
    public function getRoleLabelAttribute()
    {
        return self::ROLES[$this->role] ?? $this->role;
    }

    // Helper methods
    public function isSuperAdmin()
    {
        return $this->role === 'super_admin';
    }

    public function isAdmin()
    {
        return in_array($this->role, ['super_admin', 'admin']);
    }

    public function isStaff()
    {
        return in_array($this->role, ['super_admin', 'admin', 'staff']);
    }

    public function canManageEmployees()
    {
        return $this->isAdmin();
    }

    public function canManageTraining()
    {
        return $this->isStaff();
    }

    public function canViewReports()
    {
        return $this->isStaff();
    }

    public function canExportData()
    {
        return $this->isAdmin();
    }

    public function canImportData()
    {
        return $this->isAdmin();
    }

    public function canManageSettings()
    {
        return $this->isSuperAdmin();
    }
}
