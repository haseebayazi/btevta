<?php
// File: app/Models/User.php
// Replace the default Laravel User model with this

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'campus_id',
        'oep_id',
        'is_active',
        'phone',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'is_active' => 'boolean',
    ];

    // Relationships
    public function campus()
    {
        return $this->belongsTo(Campus::class);
    }

    public function oep()
    {
        return $this->belongsTo(Oep::class);
    }

    // Check if user has specific role
    public function hasRole($role)
    {
        return $this->role === $role;
    }

    // Check if user has any of the given roles
    public function hasAnyRole(array $roles)
    {
        return in_array($this->role, $roles);
    }

    // Check if user is admin
    public function isAdmin()
    {
        return $this->role === 'admin';
    }

    // Check if user is campus admin
    public function isCampusAdmin()
    {
        return $this->role === 'campus_admin';
    }

    // Scope for active users
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // Scope for specific role
    public function scopeRole($query, $role)
    {
        return $query->where('role', $role);
    }
}