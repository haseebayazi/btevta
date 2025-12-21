<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;

class TrainingSchedule extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'batch_id',
        'campus_id',
        'instructor_id',
        'trade_id',
        'module_name',
        'module_description',
        'module_number',
        'duration_hours',
        'scheduled_date',
        'start_time',
        'end_time',
        'room',
        'building',
        'status',
        'started_at',
        'completed_at',
        'actual_duration_minutes',
        'expected_attendees',
        'actual_attendees',
        'attendance_percentage',
        'notes',
        'cancellation_reason',
        'rescheduled_to',
        'created_by',
        'updated_by',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'scheduled_date' => 'date',
        'start_time' => 'datetime:H:i',
        'end_time' => 'datetime:H:i',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'rescheduled_to' => 'date',
        'duration_hours' => 'integer',
        'module_number' => 'integer',
        'actual_duration_minutes' => 'integer',
        'expected_attendees' => 'integer',
        'actual_attendees' => 'integer',
        'attendance_percentage' => 'decimal:2',
    ];

    /**
     * Status constants
     */
    const STATUS_SCHEDULED = 'scheduled';
    const STATUS_IN_PROGRESS = 'in_progress';
    const STATUS_COMPLETED = 'completed';
    const STATUS_CANCELLED = 'cancelled';
    const STATUS_POSTPONED = 'postponed';
    const STATUS_RESCHEDULED = 'rescheduled';

    /**
     * Get all status options.
     */
    public static function getStatusOptions(): array
    {
        return [
            self::STATUS_SCHEDULED => 'Scheduled',
            self::STATUS_IN_PROGRESS => 'In Progress',
            self::STATUS_COMPLETED => 'Completed',
            self::STATUS_CANCELLED => 'Cancelled',
            self::STATUS_POSTPONED => 'Postponed',
            self::STATUS_RESCHEDULED => 'Rescheduled',
        ];
    }

    // ==================== RELATIONSHIPS ====================

    /**
     * Get the batch for this schedule.
     */
    public function batch(): BelongsTo
    {
        return $this->belongsTo(Batch::class);
    }

    /**
     * Get the campus for this schedule.
     */
    public function campus(): BelongsTo
    {
        return $this->belongsTo(Campus::class);
    }

    /**
     * Get the instructor for this schedule.
     */
    public function instructor(): BelongsTo
    {
        return $this->belongsTo(Instructor::class);
    }

    /**
     * Get the trade for this schedule.
     */
    public function trade(): BelongsTo
    {
        return $this->belongsTo(Trade::class);
    }

    /**
     * Get the user who created this record.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated this record.
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    // ==================== SCOPES ====================

    /**
     * Scope to get schedules for a specific date.
     */
    public function scopeOnDate($query, $date)
    {
        return $query->whereDate('scheduled_date', $date);
    }

    /**
     * Scope to get today's schedules.
     */
    public function scopeToday($query)
    {
        return $query->whereDate('scheduled_date', today());
    }

    /**
     * Scope to get upcoming schedules.
     */
    public function scopeUpcoming($query)
    {
        return $query->where('scheduled_date', '>=', today())
                     ->where('status', self::STATUS_SCHEDULED)
                     ->orderBy('scheduled_date')
                     ->orderBy('start_time');
    }

    /**
     * Scope to get schedules by status.
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope to get schedules for a batch.
     */
    public function scopeForBatch($query, $batchId)
    {
        return $query->where('batch_id', $batchId);
    }

    /**
     * Scope to get schedules for a campus.
     */
    public function scopeForCampus($query, $campusId)
    {
        return $query->where('campus_id', $campusId);
    }

    /**
     * Scope to get schedules for an instructor.
     */
    public function scopeForInstructor($query, $instructorId)
    {
        return $query->where('instructor_id', $instructorId);
    }

    /**
     * Scope for date range.
     */
    public function scopeBetweenDates($query, $startDate, $endDate)
    {
        return $query->whereBetween('scheduled_date', [$startDate, $endDate]);
    }

    /**
     * Scope to search by module name.
     */
    public function scopeSearch($query, $term)
    {
        $escapedTerm = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $term);
        return $query->where('module_name', 'like', "%{$escapedTerm}%");
    }

    // ==================== ACCESSORS ====================

    /**
     * Get formatted time range.
     */
    public function getTimeRangeAttribute(): string
    {
        $start = Carbon::parse($this->start_time)->format('h:i A');
        $end = Carbon::parse($this->end_time)->format('h:i A');
        return "{$start} - {$end}";
    }

    /**
     * Get formatted date.
     */
    public function getFormattedDateAttribute(): string
    {
        return $this->scheduled_date->format('D, M d, Y');
    }

    /**
     * Get location string.
     */
    public function getLocationAttribute(): string
    {
        $parts = [];
        if ($this->room) $parts[] = $this->room;
        if ($this->building) $parts[] = $this->building;
        if ($this->campus) $parts[] = $this->campus->name;
        return implode(', ', $parts) ?: 'TBD';
    }

    /**
     * Get status badge class.
     */
    public function getStatusBadgeClassAttribute(): string
    {
        return match($this->status) {
            self::STATUS_SCHEDULED => 'bg-primary',
            self::STATUS_IN_PROGRESS => 'bg-warning',
            self::STATUS_COMPLETED => 'bg-success',
            self::STATUS_CANCELLED => 'bg-danger',
            self::STATUS_POSTPONED => 'bg-secondary',
            self::STATUS_RESCHEDULED => 'bg-info',
            default => 'bg-secondary',
        };
    }

    /**
     * Check if schedule is in the past.
     */
    public function getIsPastAttribute(): bool
    {
        return $this->scheduled_date->isPast();
    }

    /**
     * Check if schedule is today.
     */
    public function getIsTodayAttribute(): bool
    {
        return $this->scheduled_date->isToday();
    }

    /**
     * Get duration in minutes.
     */
    public function getDurationMinutesAttribute(): int
    {
        $start = Carbon::parse($this->start_time);
        $end = Carbon::parse($this->end_time);
        return $end->diffInMinutes($start);
    }

    // ==================== METHODS ====================

    /**
     * Start the training session.
     */
    public function start(): self
    {
        $this->status = self::STATUS_IN_PROGRESS;
        $this->started_at = now();
        $this->save();

        activity()
            ->causedBy(auth()->user())
            ->performedOn($this)
            ->log('Training session started');

        return $this;
    }

    /**
     * Complete the training session.
     */
    public function complete(int $actualAttendees = null): self
    {
        $this->status = self::STATUS_COMPLETED;
        $this->completed_at = now();

        if ($this->started_at) {
            $this->actual_duration_minutes = now()->diffInMinutes($this->started_at);
        }

        if ($actualAttendees !== null) {
            $this->actual_attendees = $actualAttendees;
            if ($this->expected_attendees > 0) {
                $this->attendance_percentage = ($actualAttendees / $this->expected_attendees) * 100;
            }
        }

        $this->save();

        activity()
            ->causedBy(auth()->user())
            ->performedOn($this)
            ->withProperties(['actual_attendees' => $actualAttendees])
            ->log('Training session completed');

        return $this;
    }

    /**
     * Cancel the training session.
     */
    public function cancel(string $reason = null): self
    {
        $this->status = self::STATUS_CANCELLED;
        $this->cancellation_reason = $reason;
        $this->save();

        activity()
            ->causedBy(auth()->user())
            ->performedOn($this)
            ->withProperties(['reason' => $reason])
            ->log('Training session cancelled');

        return $this;
    }

    /**
     * Postpone the training session.
     */
    public function postpone(string $reason = null): self
    {
        $this->status = self::STATUS_POSTPONED;
        $this->cancellation_reason = $reason;
        $this->save();

        activity()
            ->causedBy(auth()->user())
            ->performedOn($this)
            ->withProperties(['reason' => $reason])
            ->log('Training session postponed');

        return $this;
    }

    /**
     * Reschedule the training session.
     */
    public function reschedule(Carbon $newDate, string $newStartTime = null, string $newEndTime = null): self
    {
        $this->status = self::STATUS_RESCHEDULED;
        $this->rescheduled_to = $newDate;
        $this->save();

        // Create new schedule
        $newSchedule = $this->replicate();
        $newSchedule->scheduled_date = $newDate;
        $newSchedule->status = self::STATUS_SCHEDULED;
        $newSchedule->rescheduled_to = null;
        $newSchedule->cancellation_reason = null;

        if ($newStartTime) {
            $newSchedule->start_time = $newStartTime;
        }
        if ($newEndTime) {
            $newSchedule->end_time = $newEndTime;
        }

        $newSchedule->save();

        activity()
            ->causedBy(auth()->user())
            ->performedOn($this)
            ->withProperties([
                'new_date' => $newDate->toDateString(),
                'new_schedule_id' => $newSchedule->id,
            ])
            ->log('Training session rescheduled');

        return $newSchedule;
    }

    /**
     * Check for scheduling conflicts.
     */
    public static function hasConflict(
        int $instructorId,
        string $date,
        string $startTime,
        string $endTime,
        int $excludeId = null
    ): bool {
        $query = self::where('instructor_id', $instructorId)
            ->whereDate('scheduled_date', $date)
            ->whereIn('status', [self::STATUS_SCHEDULED, self::STATUS_IN_PROGRESS])
            ->where(function($q) use ($startTime, $endTime) {
                $q->whereBetween('start_time', [$startTime, $endTime])
                  ->orWhereBetween('end_time', [$startTime, $endTime])
                  ->orWhere(function($q2) use ($startTime, $endTime) {
                      $q2->where('start_time', '<=', $startTime)
                         ->where('end_time', '>=', $endTime);
                  });
            });

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }

    /**
     * Get instructor's schedule for a week.
     */
    public static function getInstructorWeeklySchedule(int $instructorId, Carbon $weekStart = null): array
    {
        $weekStart = $weekStart ?? Carbon::now()->startOfWeek();
        $weekEnd = $weekStart->copy()->endOfWeek();

        $schedules = self::forInstructor($instructorId)
            ->betweenDates($weekStart, $weekEnd)
            ->with(['batch', 'campus'])
            ->orderBy('scheduled_date')
            ->orderBy('start_time')
            ->get();

        $weeklySchedule = [];
        for ($i = 0; $i < 7; $i++) {
            $date = $weekStart->copy()->addDays($i);
            $weeklySchedule[$date->toDateString()] = $schedules->filter(function($schedule) use ($date) {
                return $schedule->scheduled_date->isSameDay($date);
            })->values();
        }

        return $weeklySchedule;
    }

    /**
     * Boot method to handle model events.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($schedule) {
            if (auth()->check()) {
                $schedule->created_by = auth()->id();
            }

            // Set expected attendees from batch
            if ($schedule->batch_id && !$schedule->expected_attendees) {
                $batch = Batch::find($schedule->batch_id);
                if ($batch) {
                    $schedule->expected_attendees = $batch->candidates()->count();
                }
            }
        });

        static::updating(function ($schedule) {
            if (auth()->check()) {
                $schedule->updated_by = auth()->id();
            }
        });
    }
}
