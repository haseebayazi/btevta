<?php

namespace App\Services;

use App\Models\Complaint;
use App\Models\Candidate;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class ComplaintService
{
    /**
     * Complaint categories
     */
    const CATEGORIES = [
        'screening' => 'Screening Issues',
        'registration' => 'Registration Issues',
        'training' => 'Training Issues',
        'visa' => 'Visa Processing Issues',
        'salary' => 'Salary Issues',
        'conduct' => 'Misconduct',
        'accommodation' => 'Accommodation Issues',
        'health' => 'Health & Safety',
        'discrimination' => 'Discrimination',
        'other' => 'Other',
    ];

    /**
     * Complaint priorities
     */
    const PRIORITIES = [
        'low' => 'Low',
        'normal' => 'Normal',
        'high' => 'High',
        'urgent' => 'Urgent',
        'critical' => 'Critical',
    ];

    /**
     * SLA days by priority
     */
    const SLA_DAYS = [
        'critical' => 1,
        'urgent' => 3,
        'high' => 5,
        'normal' => 7,
        'low' => 10,
    ];

    /**
     * Escalation levels
     */
    const ESCALATION_LEVELS = [
        0 => 'Level 0 - Initial',
        1 => 'Level 1 - Supervisor',
        2 => 'Level 2 - Manager',
        3 => 'Level 3 - Director',
        4 => 'Level 4 - Executive',
    ];

    /**
     * Get all complaint categories
     */
    public function getCategories(): array
    {
        return self::CATEGORIES;
    }

    /**
     * Get priorities
     */
    public function getPriorities(): array
    {
        return self::PRIORITIES;
    }

    /**
     * Get SLA days for priority
     */
    public function getSLADays($priority): int
    {
        return self::SLA_DAYS[$priority] ?? 7;
    }

    /**
     * Register a new complaint
     */
    public function registerComplaint($data): Complaint
    {
        // Calculate SLA due date
        $priority = $data['priority'] ?? 'normal';
        $slaDays = $this->getSLADays($priority);
        $slaDueDate = Carbon::now()->addDays($slaDays);

        // Generate complaint reference number
        $referenceNumber = $this->generateReferenceNumber($data['complaint_category']);

        $complaint = Complaint::create([
            'complaint_reference' => $referenceNumber,
            'candidate_id' => $data['candidate_id'] ?? null,
            'complainant_name' => $data['complainant_name'],
            'complainant_contact' => $data['complainant_contact'],
            'complainant_email' => $data['complainant_email'] ?? null,
            'complaint_category' => $data['complaint_category'],
            'priority' => $priority,
            'subject' => $data['subject'],
            'description' => $data['description'],
            'registered_at' => now(),
            'registered_by' => $data['registered_by'] ?? auth()->id(),
            'user_id' => $data['user_id'] ?? auth()->id(),
            'status' => 'open',
            'sla_days' => $slaDays,
            'sla_due_date' => $slaDueDate,
            'escalation_level' => 0,
        ]);

        // Upload evidence if provided
        if (!empty($data['evidence'])) {
            $this->uploadEvidence($complaint->id, $data['evidence']);
        }

        // Log activity
        activity()
            ->performedOn($complaint)
            ->causedBy(auth()->user())
            ->log("Complaint registered: {$referenceNumber}");

        return $complaint;
    }

    /**
     * Generate complaint reference number
     */
    private function generateReferenceNumber($category): string
    {
        $categoryCode = strtoupper(substr($category, 0, 3));
        $year = date('Y');
        $month = date('m');
        
        $lastComplaint = Complaint::where('complaint_reference', 'like', "CMP-{$categoryCode}-{$year}{$month}-%")
            ->orderBy('complaint_reference', 'desc')
            ->first();
        
        if ($lastComplaint) {
            $lastSequence = (int) substr($lastComplaint->complaint_reference, -4);
            $sequence = $lastSequence + 1;
        } else {
            $sequence = 1;
        }
        
        return sprintf('CMP-%s-%s%s-%04d', $categoryCode, $year, $month, $sequence);
    }

    /**
     * Upload complaint evidence
     */
    public function uploadEvidence($complaintId, $file): string
    {
        $complaint = Complaint::findOrFail($complaintId);

        // ERROR HANDLING: Store file with error handling
        try {
            $path = $file->store('complaints/evidence', 'public');
        } catch (\Exception $e) {
            throw new \Exception("Failed to store evidence file: " . $e->getMessage());
        }

        // JSON ERROR HANDLING: Get existing evidence files with error checking
        $evidenceFiles = [];
        if ($complaint->evidence_files) {
            $evidenceFiles = json_decode($complaint->evidence_files, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                \Log::warning("Invalid JSON in evidence_files for complaint {$complaintId}");
                $evidenceFiles = [];
            }
        }
        
        // Add new file
        $evidenceFiles[] = [
            'path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'uploaded_at' => now()->toDateTimeString(),
            'uploaded_by' => auth()->user()->name ?? 'System',
        ];
        
        $complaint->update([
            'evidence_files' => json_encode($evidenceFiles),
        ]);

        return $path;
    }

    /**
     * Assign complaint to user
     */
    public function assignComplaint($complaintId, $assignedToUserId, $remarks = null): Complaint
    {
        $complaint = Complaint::findOrFail($complaintId);
        
        $complaint->update([
            'assigned_to' => $assignedToUserId,
            'assigned_at' => now(),
            'status' => 'assigned',
            'assignment_remarks' => $remarks,
        ]);

        // Log activity
        activity()
            ->performedOn($complaint)
            ->causedBy(auth()->user())
            ->log("Complaint assigned to user ID: {$assignedToUserId}");

        // You can send notification here
        // event(new ComplaintAssigned($complaint));

        return $complaint;
    }

    /**
     * Update complaint status
     */
    public function updateStatus($complaintId, $status, $remarks = null): Complaint
    {
        $complaint = Complaint::findOrFail($complaintId);
        
        $complaint->update([
            'status' => $status,
            'status_updated_at' => now(),
            'status_remarks' => $remarks,
        ]);

        // If status is in_progress, start tracking
        if ($status === 'in_progress' && !$complaint->in_progress_at) {
            $complaint->update(['in_progress_at' => now()]);
        }

        // Log activity
        activity()
            ->performedOn($complaint)
            ->causedBy(auth()->user())
            ->log("Status updated to: {$status}");

        return $complaint;
    }

    /**
     * Add investigation note
     */
    public function addInvestigationNote($complaintId, $note): Complaint
    {
        $complaint = Complaint::findOrFail($complaintId);

        // JSON ERROR HANDLING: Get existing notes with error checking
        $notes = [];
        if ($complaint->investigation_notes) {
            $notes = json_decode($complaint->investigation_notes, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                \Log::warning("Invalid JSON in investigation_notes for complaint {$complaintId}");
                $notes = [];
            }
        }
        
        // Add new note
        $notes[] = [
            'note' => $note,
            'added_by' => auth()->user()->name ?? 'System',
            'added_at' => now()->toDateTimeString(),
        ];
        
        $complaint->update([
            'investigation_notes' => json_encode($notes),
        ]);

        return $complaint;
    }

    /**
     * Resolve complaint
     */
    public function resolveComplaint($complaintId, $data): Complaint
    {
        $complaint = Complaint::findOrFail($complaintId);
        
        $complaint->update([
            'status' => 'resolved',
            'resolved_at' => now(),
            'resolved_by' => auth()->id(),
            'resolution_details' => $data['resolution_details'],
            'action_taken' => $data['action_taken'] ?? null,
            'resolution_category' => $data['resolution_category'] ?? null, // accepted, rejected, partial
        ]);

        // Calculate resolution time
        $resolutionTime = Carbon::parse($complaint->registered_at)->diffInDays($complaint->resolved_at);
        $complaint->update(['resolution_time_days' => $resolutionTime]);

        // Log activity
        activity()
            ->performedOn($complaint)
            ->causedBy(auth()->user())
            ->log("Complaint resolved");

        return $complaint;
    }

    /**
     * Close complaint
     */
    public function closeComplaint($complaintId, $closureRemarks = null): Complaint
    {
        $complaint = Complaint::findOrFail($complaintId);
        
        if ($complaint->status !== 'resolved') {
            throw new \Exception('Complaint must be resolved before closing');
        }
        
        $complaint->update([
            'status' => 'closed',
            'closed_at' => now(),
            'closed_by' => auth()->id(),
            'closure_remarks' => $closureRemarks,
        ]);

        // Log activity
        activity()
            ->performedOn($complaint)
            ->causedBy(auth()->user())
            ->log("Complaint closed");

        return $complaint;
    }

    /**
     * Escalate complaint
     */
    public function escalateComplaint($complaintId, $reason = null): Complaint
    {
        $complaint = Complaint::findOrFail($complaintId);
        
        $currentLevel = $complaint->escalation_level;
        $newLevel = min($currentLevel + 1, 4); // Max level 4
        
        $complaint->update([
            'escalation_level' => $newLevel,
            'escalated_at' => now(),
            'escalation_reason' => $reason,
            'priority' => $this->increasePriority($complaint->priority),
        ]);

        // Recalculate SLA
        $this->recalculateSLA($complaint);

        // Log activity
        activity()
            ->performedOn($complaint)
            ->causedBy(auth()->user())
            ->log("Complaint escalated to level {$newLevel}");

        return $complaint;
    }

    /**
     * Increase priority for escalation
     */
    private function increasePriority($currentPriority): string
    {
        $priorities = ['low', 'normal', 'high', 'urgent', 'critical'];
        $currentIndex = array_search($currentPriority, $priorities);
        $newIndex = min($currentIndex + 1, count($priorities) - 1);
        
        return $priorities[$newIndex];
    }

    /**
     * Recalculate SLA based on new priority
     */
    private function recalculateSLA($complaint): void
    {
        $slaDays = $this->getSLADays($complaint->priority);
        $newDueDate = Carbon::parse($complaint->registered_at)->addDays($slaDays);
        
        $complaint->update([
            'sla_days' => $slaDays,
            'sla_due_date' => $newDueDate,
        ]);
    }

    /**
     * Check and auto-escalate overdue complaints
     */
    public function checkAndAutoEscalate(): array
    {
        $overdueComplaints = Complaint::where('status', '!=', 'closed')
            ->where('status', '!=', 'resolved')
            ->where('sla_due_date', '<', now())
            ->where('escalation_level', '<', 4)
            ->get();

        $escalatedCount = 0;
        foreach ($overdueComplaints as $complaint) {
            // Check if already escalated today
            if ($complaint->escalated_at && Carbon::parse($complaint->escalated_at)->isToday()) {
                continue;
            }

            $daysPastDue = Carbon::parse($complaint->sla_due_date)->diffInDays(now());
            
            // Escalate if more than 2 days past due
            if ($daysPastDue >= 2) {
                $this->escalateComplaint($complaint->id, "Auto-escalated: {$daysPastDue} days overdue");
                $escalatedCount++;
            }
        }

        return [
            'checked' => $overdueComplaints->count(),
            'escalated' => $escalatedCount,
        ];
    }

    /**
     * Get overdue complaints
     */
    public function getOverdueComplaints($filters = []): \Illuminate\Support\Collection
    {
        $query = Complaint::with(['candidate', 'assignedToUser'])
            ->where('status', '!=', 'closed')
            ->where('status', '!=', 'resolved')
            ->where('sla_due_date', '<', now());

        // Apply filters
        if (!empty($filters['category'])) {
            $query->where('complaint_category', $filters['category']);
        }

        if (!empty($filters['assigned_to'])) {
            $query->where('assigned_to', $filters['assigned_to']);
        }

        if (!empty($filters['priority'])) {
            $query->where('priority', $filters['priority']);
        }

        return $query->orderBy('sla_due_date', 'asc')->get()->map(function($complaint) {
            $daysPastDue = Carbon::parse($complaint->sla_due_date)->diffInDays(now());
            
            return [
                'complaint' => $complaint,
                'days_past_due' => $daysPastDue,
                'severity' => $this->calculateOverdueSeverity($daysPastDue),
            ];
        });
    }

    /**
     * Calculate overdue severity
     */
    private function calculateOverdueSeverity($daysPastDue): string
    {
        if ($daysPastDue <= 2) return 'moderate';
        if ($daysPastDue <= 5) return 'serious';
        return 'critical';
    }

    /**
     * Get complaint statistics
     */
    public function getStatistics($filters = []): array
    {
        $query = Complaint::query();

        // Apply date filters
        if (!empty($filters['from_date'])) {
            $query->whereDate('registered_at', '>=', $filters['from_date']);
        }

        if (!empty($filters['to_date'])) {
            $query->whereDate('registered_at', '<=', $filters['to_date']);
        }

        $total = $query->count();
        $complaints = (clone $query)->get();

        $statistics = [
            'total_complaints' => $total,
            'open' => (clone $query)->where('status', 'open')->count(),
            'assigned' => (clone $query)->where('status', 'assigned')->count(),
            'in_progress' => (clone $query)->where('status', 'in_progress')->count(),
            'resolved' => (clone $query)->where('status', 'resolved')->count(),
            'closed' => (clone $query)->where('status', 'closed')->count(),
            'overdue' => (clone $query)->where('sla_due_date', '<', now())
                ->whereNotIn('status', ['resolved', 'closed'])->count(),
            'by_category' => $this->groupByCategory($complaints),
            'by_priority' => $this->groupByPriority($complaints),
            'average_resolution_time' => $this->calculateAverageResolutionTime($complaints),
            'sla_compliance_rate' => $this->calculateSLAComplianceRate($complaints),
        ];

        return $statistics;
    }

    /**
     * Group complaints by category
     */
    private function groupByCategory($complaints): \Illuminate\Support\Collection
    {
        return $complaints->groupBy('complaint_category')->map(function($group, $category) {
            return [
                'category' => self::CATEGORIES[$category] ?? $category,
                'count' => $group->count(),
                'resolved' => $group->whereIn('status', ['resolved', 'closed'])->count(),
            ];
        });
    }

    /**
     * Group complaints by priority
     */
    private function groupByPriority($complaints): \Illuminate\Support\Collection
    {
        return $complaints->groupBy('priority')->map(function($group) {
            return [
                'count' => $group->count(),
                'resolved' => $group->whereIn('status', ['resolved', 'closed'])->count(),
            ];
        });
    }

    /**
     * Calculate average resolution time
     */
    private function calculateAverageResolutionTime($complaints): float
    {
        $resolved = $complaints->whereNotNull('resolved_at');
        
        if ($resolved->isEmpty()) {
            return 0;
        }

        $totalDays = 0;
        foreach ($resolved as $complaint) {
            $totalDays += Carbon::parse($complaint->registered_at)->diffInDays($complaint->resolved_at);
        }

        return round($totalDays / $resolved->count(), 1);
    }

    /**
     * Calculate SLA compliance rate
     */
    private function calculateSLAComplianceRate($complaints): float
    {
        $resolved = $complaints->whereNotNull('resolved_at');
        
        if ($resolved->isEmpty()) {
            return 0;
        }

        $withinSLA = $resolved->filter(function($complaint) {
            return Carbon::parse($complaint->resolved_at)->lte($complaint->sla_due_date);
        })->count();

        return round(($withinSLA / $resolved->count()) * 100, 2);
    }

    /**
     * Get campus-wise complaint trends
     */
    public function getCampusTrends($filters = []): \Illuminate\Support\Collection
    {
        $query = Complaint::with('candidate.campus')
            ->whereHas('candidate');

        if (!empty($filters['from_date'])) {
            $query->whereDate('registered_at', '>=', $filters['from_date']);
        }

        if (!empty($filters['to_date'])) {
            $query->whereDate('registered_at', '<=', $filters['to_date']);
        }

        $complaints = $query->get();

        // NULL CHECK: Group by campus name with null handling
        return $complaints->groupBy(function($complaint) {
            return $complaint->candidate?->campus?->name ?? 'Unknown';
        })->map(function($group, $campusName) {
            return [
                'campus' => $campusName,
                'total' => $group->count(),
                'resolved' => $group->whereIn('status', ['resolved', 'closed'])->count(),
                'by_category' => $group->groupBy('complaint_category')->map->count(),
            ];
        });
    }

    /**
     * Generate complaint analysis report
     */
    public function generateAnalysisReport($filters = []): array
    {
        $statistics = $this->getStatistics($filters);
        $campusTrends = $this->getCampusTrends($filters);
        $overdueComplaints = $this->getOverdueComplaints($filters);

        return [
            'statistics' => $statistics,
            'campus_trends' => $campusTrends,
            'overdue_complaints' => $overdueComplaints,
            'recommendations' => $this->generateRecommendations($statistics),
        ];
    }

    /**
     * Generate recommendations based on complaint data
     */
    private function generateRecommendations($statistics): array
    {
        $recommendations = [];

        // Check SLA compliance
        if ($statistics['sla_compliance_rate'] < 70) {
            $recommendations[] = 'SLA compliance is below 70%. Consider reviewing complaint handling processes.';
        }

        // Check overdue rate
        $overdueRate = $statistics['total_complaints'] > 0 
            ? ($statistics['overdue'] / $statistics['total_complaints']) * 100 
            : 0;
        
        if ($overdueRate > 20) {
            $recommendations[] = 'More than 20% of complaints are overdue. Increase staff allocation or streamline processes.';
        }

        // Check category concentration
        if (!empty($statistics['by_category'])) {
            $topCategory = collect($statistics['by_category'])->sortByDesc('count')->first();
            if ($topCategory['count'] > $statistics['total_complaints'] * 0.4) {
                $recommendations[] = "High concentration of complaints in {$topCategory['category']}. Focus improvement efforts here.";
            }
        }

        return $recommendations;
    }

    /**
     * Export complaints data
     */
    public function exportComplaints($filters = []): \Illuminate\Database\Eloquent\Collection
    {
        $query = Complaint::with(['candidate', 'assignedToUser']);

        // Apply filters
        if (!empty($filters['from_date'])) {
            $query->whereDate('registered_at', '>=', $filters['from_date']);
        }

        if (!empty($filters['to_date'])) {
            $query->whereDate('registered_at', '<=', $filters['to_date']);
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['category'])) {
            $query->where('complaint_category', $filters['category']);
        }

        return $query->get();
    }
}