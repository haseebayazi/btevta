<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class NextOfKin extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The table associated with the model.
     */
    protected $table = 'next_of_kins';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name',
        'relationship',
        'cnic',
        'phone',
        'email',
        'address',
        'occupation',
        'monthly_income',
        'emergency_contact',
        'created_by',
        'updated_by'
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'monthly_income' => 'decimal:2',
        'created_by' => 'integer',
        'updated_by' => 'integer',
        'deleted_at' => 'datetime',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * SECURITY: Hide sensitive personal information and PII
     */
    protected $hidden = [
        'cnic',
        'emergency_contact',
        'address',
    ];

    /**
     * Relationship types
     */
    const RELATIONSHIP_FATHER = 'father';
    const RELATIONSHIP_MOTHER = 'mother';
    const RELATIONSHIP_SPOUSE = 'spouse';
    const RELATIONSHIP_SIBLING = 'sibling';
    const RELATIONSHIP_CHILD = 'child';
    const RELATIONSHIP_OTHER = 'other';

    /**
     * Get all relationship types
     */
    public static function getRelationshipTypes()
    {
        return [
            self::RELATIONSHIP_FATHER => 'Father',
            self::RELATIONSHIP_MOTHER => 'Mother',
            self::RELATIONSHIP_SPOUSE => 'Spouse',
            self::RELATIONSHIP_SIBLING => 'Sibling',
            self::RELATIONSHIP_CHILD => 'Child',
            self::RELATIONSHIP_OTHER => 'Other',
        ];
    }

    // ==================== RELATIONSHIPS ====================

    /**
     * Get all candidates associated with this next of kin.
     */
    public function candidates()
    {
        return $this->hasMany(Candidate::class, 'next_of_kin_id');
    }

    /**
     * Get the user who created this record.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated this record.
     */
    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    // ==================== SCOPES ====================

    /**
     * Scope to search by name or CNIC.
     */
    public function scopeSearch($query, $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
              ->orWhere('cnic', 'like', "%{$search}%")
              ->orWhere('phone', 'like', "%{$search}%");
        });
    }

    /**
     * Scope to filter by relationship type.
     */
    public function scopeByRelationship($query, $relationship)
    {
        return $query->where('relationship', $relationship);
    }

    // ==================== ACCESSORS & MUTATORS ====================

    /**
     * Get formatted CNIC.
     */
    public function getFormattedCnicAttribute()
    {
        if ($this->cnic && strlen($this->cnic) == 13) {
            return substr($this->cnic, 0, 5) . '-' . 
                   substr($this->cnic, 5, 7) . '-' . 
                   substr($this->cnic, 12, 1);
        }
        return $this->cnic;
    }

    /**
     * Get complete contact information.
     */
    public function getContactInfoAttribute()
    {
        return [
            'phone' => $this->phone,
            'email' => $this->email,
            'address' => $this->address,
            'emergency_contact' => $this->emergency_contact
        ];
    }

    /**
     * Get relationship label.
     */
    public function getRelationshipLabelAttribute()
    {
        return self::getRelationshipTypes()[$this->relationship] ?? 'Unknown';
    }

    /**
     * Get full address as string.
     */
    public function getFullAddressAttribute()
    {
        return $this->address;
    }

    // ==================== HELPER METHODS ====================

    /**
     * Check if this person can be contacted.
     */
    public function isContactable()
    {
        return !empty($this->phone) || !empty($this->email) || !empty($this->emergency_contact);
    }

    /**
     * Get primary contact method.
     */
    public function getPrimaryContact()
    {
        if ($this->phone) {
            return ['type' => 'phone', 'value' => $this->phone];
        }
        
        if ($this->email) {
            return ['type' => 'email', 'value' => $this->email];
        }
        
        if ($this->emergency_contact) {
            return ['type' => 'emergency', 'value' => $this->emergency_contact];
        }
        
        return null;
    }

    /**
     * Validate CNIC format.
     */
    public function validateCnic()
    {
        if (!$this->cnic) {
            return false;
        }
        
        // Remove any non-numeric characters
        $cnic = preg_replace('/[^0-9]/', '', $this->cnic);
        
        // Check if it's exactly 13 digits
        return strlen($cnic) === 13;
    }

    /**
     * Check if this is a primary guardian.
     */
    public function isPrimaryGuardian()
    {
        return in_array($this->relationship, [
            self::RELATIONSHIP_FATHER,
            self::RELATIONSHIP_MOTHER,
            self::RELATIONSHIP_SPOUSE
        ]);
    }

    /**
     * Boot method to handle model events.
     */
    protected static function boot()
    {
        parent::boot();

        // Track who created the record
        static::creating(function ($nextOfKin) {
            if (auth()->check()) {
                $nextOfKin->created_by = auth()->id();
            }
        });

        // Track who updated the record
        static::updating(function ($nextOfKin) {
            if (auth()->check()) {
                $nextOfKin->updated_by = auth()->id();
            }
        });
    }
}