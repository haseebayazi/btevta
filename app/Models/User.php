<?php
// File: app/Models/User.php
// Replace the default Laravel User model with this

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasFactory, Notifiable, SoftDeletes, HasApiTokens;

    /**
     * Role Constants - As per ICLMS Specification
     * 7 Primary Roles + 2 Additional System Roles
     */
    public const ROLE_SUPER_ADMIN = 'super_admin';
    public const ROLE_ADMIN = 'admin';  // Legacy alias for super_admin
    public const ROLE_PROJECT_DIRECTOR = 'project_director';
    public const ROLE_CAMPUS_ADMIN = 'campus_admin';
    public const ROLE_TRAINER = 'trainer';
    public const ROLE_INSTRUCTOR = 'instructor';  // Legacy alias for trainer
    public const ROLE_OEP = 'oep';
    public const ROLE_VISA_PARTNER = 'visa_partner';
    public const ROLE_CANDIDATE = 'candidate';
    public const ROLE_VIEWER = 'viewer';  // Read-only access
    public const ROLE_STAFF = 'staff';    // General staff access

    /**
     * All available roles for validation
     */
    public const ROLES = [
        self::ROLE_SUPER_ADMIN,
        self::ROLE_ADMIN,
        self::ROLE_PROJECT_DIRECTOR,
        self::ROLE_CAMPUS_ADMIN,
        self::ROLE_TRAINER,
        self::ROLE_INSTRUCTOR,
        self::ROLE_OEP,
        self::ROLE_VISA_PARTNER,
        self::ROLE_CANDIDATE,
        self::ROLE_VIEWER,
        self::ROLE_STAFF,
    ];

    /**
     * Role hierarchy for permission checks
     * Higher index = more permissions
     */
    public const ROLE_HIERARCHY = [
        self::ROLE_CANDIDATE => 0,
        self::ROLE_VIEWER => 1,
        self::ROLE_STAFF => 2,
        self::ROLE_TRAINER => 3,
        self::ROLE_INSTRUCTOR => 3,
        self::ROLE_OEP => 4,
        self::ROLE_VISA_PARTNER => 4,
        self::ROLE_CAMPUS_ADMIN => 5,
        self::ROLE_PROJECT_DIRECTOR => 6,
        self::ROLE_ADMIN => 7,
        self::ROLE_SUPER_ADMIN => 7,
    ];

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'campus_id',
        'oep_id',
        'visa_partner_id',
        'is_active',
        'phone',
        'last_login_at',
        'failed_login_attempts',
        'locked_until',
        'password_changed_at',
        'created_by',
        'updated_by',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'is_active' => 'boolean',
        'last_login_at' => 'datetime',
        'locked_until' => 'datetime',
        'password_changed_at' => 'datetime',
        'failed_login_attempts' => 'integer',
    ];

    // ============================================================
    // RELATIONSHIPS
    // ============================================================

    public function campus()
    {
        return $this->belongsTo(Campus::class);
    }

    public function oep()
    {
        return $this->belongsTo(Oep::class);
    }

    public function visaPartner()
    {
        return $this->belongsTo(VisaPartner::class);
    }

    public function candidate()
    {
        return $this->hasOne(Candidate::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    // ============================================================
    // SECURITY METHODS
    // ============================================================

    /**
     * Check if the user account is currently locked
     */
    public function isLocked(): bool
    {
        return $this->locked_until && $this->locked_until->isFuture();
    }

    /**
     * Get remaining lockout minutes
     */
    public function getLockoutMinutesRemaining(): int
    {
        if (!$this->isLocked()) {
            return 0;
        }
        return now()->diffInMinutes($this->locked_until, false);
    }

    // ============================================================
    // ROLE CHECK METHODS
    // ============================================================

    /**
     * Check if user has specific role
     */
    public function hasRole(string $role): bool
    {
        // Handle aliases
        if ($role === self::ROLE_ADMIN && $this->role === self::ROLE_SUPER_ADMIN) {
            return true;
        }
        if ($role === self::ROLE_INSTRUCTOR && $this->role === self::ROLE_TRAINER) {
            return true;
        }
        if ($role === self::ROLE_TRAINER && $this->role === self::ROLE_INSTRUCTOR) {
            return true;
        }

        return $this->role === $role;
    }

    /**
     * Check if user has any of the given roles
     */
    public function hasAnyRole(array $roles): bool
    {
        foreach ($roles as $role) {
            if ($this->hasRole($role)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Check if user has at least the given role level in hierarchy
     */
    public function hasRoleLevel(string $minimumRole): bool
    {
        $userLevel = self::ROLE_HIERARCHY[$this->role] ?? 0;
        $requiredLevel = self::ROLE_HIERARCHY[$minimumRole] ?? 0;
        return $userLevel >= $requiredLevel;
    }

    // ============================================================
    // ROLE HELPER METHODS - Per Specification
    // ============================================================

    /**
     * Check if user is Super Admin (highest privilege)
     */
    public function isSuperAdmin(): bool
    {
        return in_array($this->role, [self::ROLE_SUPER_ADMIN, self::ROLE_ADMIN]);
    }

    /**
     * Check if user is Admin (alias for isSuperAdmin)
     */
    public function isAdmin(): bool
    {
        return $this->isSuperAdmin();
    }

    /**
     * Check if user is Project Director
     */
    public function isProjectDirector(): bool
    {
        return $this->role === self::ROLE_PROJECT_DIRECTOR || $this->isSuperAdmin();
    }

    /**
     * Check if user is Campus Admin
     */
    public function isCampusAdmin(): bool
    {
        return $this->role === self::ROLE_CAMPUS_ADMIN;
    }

    /**
     * Check if user is Trainer/Instructor
     */
    public function isTrainer(): bool
    {
        return in_array($this->role, [self::ROLE_TRAINER, self::ROLE_INSTRUCTOR]);
    }

    /**
     * Alias for isTrainer()
     */
    public function isInstructor(): bool
    {
        return $this->isTrainer();
    }

    /**
     * Check if user is OEP (Overseas Employment Promoter)
     */
    public function isOep(): bool
    {
        return $this->role === self::ROLE_OEP;
    }

    /**
     * Check if user is Visa Partner
     */
    public function isVisaPartner(): bool
    {
        return $this->role === self::ROLE_VISA_PARTNER;
    }

    /**
     * Check if user is Candidate
     */
    public function isCandidate(): bool
    {
        return $this->role === self::ROLE_CANDIDATE;
    }

    /**
     * Check if user is Viewer (read-only)
     */
    public function isViewer(): bool
    {
        return $this->role === self::ROLE_VIEWER;
    }

    /**
     * Check if user is Staff
     */
    public function isStaff(): bool
    {
        return $this->role === self::ROLE_STAFF;
    }

    // ============================================================
    // ACCESS LEVEL CHECKS
    // ============================================================

    /**
     * Check if user can manage campus operations
     */
    public function canManageCampus(?int $campusId = null): bool
    {
        if ($this->isSuperAdmin() || $this->isProjectDirector()) {
            return true;
        }

        if ($this->isCampusAdmin() && $campusId) {
            return $this->campus_id === $campusId;
        }

        return false;
    }

    /**
     * Check if user can manage OEP operations
     */
    public function canManageOep(?int $oepId = null): bool
    {
        if ($this->isSuperAdmin() || $this->isProjectDirector()) {
            return true;
        }

        if ($this->isOep() && $oepId) {
            return $this->oep_id === $oepId;
        }

        return false;
    }

    /**
     * Check if user can manage visa processing
     */
    public function canManageVisa(): bool
    {
        return $this->hasAnyRole([
            self::ROLE_SUPER_ADMIN,
            self::ROLE_ADMIN,
            self::ROLE_PROJECT_DIRECTOR,
            self::ROLE_OEP,
            self::ROLE_VISA_PARTNER,
        ]);
    }

    /**
     * Check if user can view reports
     */
    public function canViewReports(): bool
    {
        return $this->hasAnyRole([
            self::ROLE_SUPER_ADMIN,
            self::ROLE_ADMIN,
            self::ROLE_PROJECT_DIRECTOR,
            self::ROLE_CAMPUS_ADMIN,
            self::ROLE_VIEWER,
        ]);
    }

    /**
     * Get the role display name
     */
    public function getRoleDisplayName(): string
    {
        $roleNames = [
            self::ROLE_SUPER_ADMIN => 'Super Admin',
            self::ROLE_ADMIN => 'Admin',
            self::ROLE_PROJECT_DIRECTOR => 'Project Director',
            self::ROLE_CAMPUS_ADMIN => 'Campus Admin',
            self::ROLE_TRAINER => 'Trainer',
            self::ROLE_INSTRUCTOR => 'Instructor',
            self::ROLE_OEP => 'OEP',
            self::ROLE_VISA_PARTNER => 'Visa Partner',
            self::ROLE_CANDIDATE => 'Candidate',
            self::ROLE_VIEWER => 'Viewer',
            self::ROLE_STAFF => 'Staff',
        ];

        return $roleNames[$this->role] ?? ucfirst($this->role);
    }

    // ============================================================
    // SCOPES
    // ============================================================

    /**
     * Scope for active users
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for specific role
     */
    public function scopeRole($query, string $role)
    {
        return $query->where('role', $role);
    }

    /**
     * Scope for users with any of the given roles
     */
    public function scopeRoles($query, array $roles)
    {
        return $query->whereIn('role', $roles);
    }

    /**
     * Scope for admin-level users
     */
    public function scopeAdmins($query)
    {
        return $query->whereIn('role', [self::ROLE_SUPER_ADMIN, self::ROLE_ADMIN]);
    }

    /**
     * Scope for users belonging to a campus
     */
    public function scopeForCampus($query, int $campusId)
    {
        return $query->where('campus_id', $campusId);
    }

    /**
     * Scope for users belonging to an OEP
     */
    public function scopeForOep($query, int $oepId)
    {
        return $query->where('oep_id', $oepId);
    }

    // ============================================================
    // VALIDATION HELPERS
    // ============================================================

    /**
     * Check if a role string is valid
     */
    public static function isValidRole(string $role): bool
    {
        return in_array($role, self::ROLES);
    }

    /**
     * Get all roles as options for dropdowns
     */
    public static function getRoleOptions(): array
    {
        return [
            self::ROLE_SUPER_ADMIN => 'Super Admin',
            self::ROLE_PROJECT_DIRECTOR => 'Project Director',
            self::ROLE_CAMPUS_ADMIN => 'Campus Admin',
            self::ROLE_TRAINER => 'Trainer',
            self::ROLE_OEP => 'OEP (Overseas Employment Promoter)',
            self::ROLE_VISA_PARTNER => 'Visa Partner',
            self::ROLE_CANDIDATE => 'Candidate',
            self::ROLE_VIEWER => 'Viewer (Read-Only)',
            self::ROLE_STAFF => 'Staff',
        ];
    }
}