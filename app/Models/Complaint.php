<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Carbon\Carbon;

class Complaint extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'candidate_id',
        'campus_id',
        'oep_id',
        'complaint_number',
        'complaint_category',
        'subject',
        'title',
        'description',
        'status',
        'priority',
        'sla_days',
        'sla_due_date',
        'escalation_level',
        'assigned_to',
        'resolution_details',
        'resolved_at',
        'user_id',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'sla_due_date' => 'datetime',
        'resolved_at' => 'datetime',
        'escalation_level' => 'integer',
        'sla_days' => 'integer',
    ];

    protected $attributes = [
        'status' => 'open',
        'escalation_level' => 1,
        'priority' => 'normal',
    ];

    // Status constants
    const STATUS_OPEN = 'open';
    const STATUS_ASSIGNED = 'assigned';
    const STATUS_IN_PROGRESS = 'in_progress';
    const STATUS_RESOLVED = 'resolved';
    const STATUS_CLOSED = 'closed';

    // Category constants
    const CATEGORY_TRAINING = 'training';
    const CATEGORY_SALARY = 'salary';
    const CATEGORY_CONDUCT = 'conduct';
    const CATEGORY_VISA = 'visa';
    const CATEGORY_ACCOMMODATION = 'accommodation';
    const CATEGORY_OTHER = 'other';

    // Priority constants
    const PRIORITY_LOW = 'low';
    const PRIORITY_NORMAL = 'normal';
    const PRIORITY_HIGH = 'high';
    const PRIORITY_URGENT = 'urgent';

    public static function getStatuses()
    {
        return [
            self::STATUS_OPEN => 'Open',
            self::STATUS_ASSIGNED => 'Assigned',
            self::STATUS_IN_PROGRESS => 'In Progress',
            self::STATUS_RESOLVED => 'Resolved',
            self::STATUS_CLOSED => 'Closed',
        ];
    }

    public static function getCategories()
    {
        return [
            self::CATEGORY_TRAINING => 'Training',
            self::CATEGORY_SALARY => 'Salary',
            self::CATEGORY_CONDUCT => 'Conduct',
            self::CATEGORY_VISA => 'Visa',
            self::CATEGORY_ACCOMMODATION => 'Accommodation',
            self::CATEGORY_OTHER => 'Other',
        ];
    }

    public static function getPriorities()
    {
        return [
            self::PRIORITY_LOW => 'Low',
            self::PRIORITY_NORMAL => 'Normal',
            self::PRIORITY_HIGH => 'High',
            self::PRIORITY_URGENT => 'Urgent',
        ];
    }

    // Relationships
    public function candidate()
    {
        return $this->belongsTo(Candidate::class);
    }

    public function complainant()
    {
        return $this->belongsTo(Candidate::class, 'candidate_id');
    }

    public function assignee()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function registeredBy()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function updates()
    {
        return $this->hasMany(ComplaintUpdate::class)->orderBy('created_at', 'desc');
    }

    public function evidence()
    {
        return $this->hasMany(ComplaintEvidence::class);
    }

    public function campus()
    {
        return $this->belongsTo(Campus::class);
    }

    public function oep()
    {
        return $this->belongsTo(OEP::class);
    }

    // Scopes
    public function scopeOverdue($query)
    {
        return $query->whereNotIn('status', [self::STATUS_RESOLVED, self::STATUS_CLOSED])
                     ->where('sla_due_date', '<', now());
    }

    public function scopeOpen($query)
    {
        return $query->where('status', self::STATUS_OPEN);
    }

    public function scopeByCategory($query, $category)
    {
        return $query->where('complaint_category', $category);
    }

    // Accessors
    public function getPriorityColorAttribute()
    {
        $colors = [
            self::PRIORITY_LOW => 'secondary',
            self::PRIORITY_NORMAL => 'primary',
            self::PRIORITY_HIGH => 'warning',
            self::PRIORITY_URGENT => 'danger',
        ];

        return $colors[$this->priority] ?? 'secondary';
    }

    public function getStatusColorAttribute()
    {
        $colors = [
            self::STATUS_OPEN => 'info',
            self::STATUS_ASSIGNED => 'primary',
            self::STATUS_IN_PROGRESS => 'warning',
            self::STATUS_RESOLVED => 'success',
            self::STATUS_CLOSED => 'secondary',
        ];

        return $colors[$this->status] ?? 'secondary';
    }

    public function getPriorityBorderColorAttribute()
    {
        $colors = [
            self::PRIORITY_LOW => 'border-secondary',
            self::PRIORITY_NORMAL => 'border-primary',
            self::PRIORITY_HIGH => 'border-warning',
            self::PRIORITY_URGENT => 'border-danger',
        ];

        return $colors[$this->priority] ?? 'border-secondary';
    }

    // Helper Methods
    public function isOverdue()
    {
        return $this->sla_due_date &&
               $this->sla_due_date < now() &&
               !in_array($this->status, [self::STATUS_RESOLVED, self::STATUS_CLOSED]);
    }

    public function escalate()
    {
        $this->escalation_level++;
        $this->save();
        
        // Trigger escalation notifications
        // Notification logic here
        
        return true;
    }

    public function resolve($resolutionDetails)
    {
        $this->status = self::STATUS_RESOLVED;
        $this->resolution_details = $resolutionDetails;
        $this->resolved_at = now();
        $this->save();
        
        return true;
    }

    public function calculateSLADueDate()
    {
        $categorySlaDays = [
            self::CATEGORY_TRAINING => 5,
            self::CATEGORY_SALARY => 3,
            self::CATEGORY_CONDUCT => 7,
            self::CATEGORY_VISA => 10,
            self::CATEGORY_ACCOMMODATION => 5,
            self::CATEGORY_OTHER => 7,
        ];
        
        $slaDays = $categorySlaDays[$this->complaint_category] ?? 7;
        $this->sla_days = $slaDays;
        $this->sla_due_date = Carbon::now()->addDays($slaDays);
        
        return $this->sla_due_date;
    }

    public static function generateComplaintNumber()
    {
        $year = date('Y');
        $lastComplaint = self::whereYear('created_at', $year)
                             ->orderBy('complaint_number', 'desc')
                             ->first();
        
        if ($lastComplaint && $lastComplaint->complaint_number) {
            $lastNumber = intval(substr($lastComplaint->complaint_number, -6));
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }
        
        return 'COMP' . $year . str_pad($newNumber, 6, '0', STR_PAD_LEFT);
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($complaint) {
            if (empty($complaint->complaint_number)) {
                $complaint->complaint_number = self::generateComplaintNumber();
            }
            
            if (empty($complaint->sla_due_date)) {
                $complaint->calculateSLADueDate();
            }
            
            if (auth()->check()) {
                $complaint->created_by = auth()->id();
                $complaint->user_id = $complaint->user_id ?? auth()->id();
            }
        });

        static::updating(function ($complaint) {
            if (auth()->check()) {
                $complaint->updated_by = auth()->id();
            }
        });
    }
}