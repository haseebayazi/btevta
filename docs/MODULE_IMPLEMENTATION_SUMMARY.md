# WASL ERP - Module Implementation Plans Summary

**Version:** 1.0
**Date:** 2026-01-15

This document provides a quick reference summary for all 10 module implementation plans.

---

## Module 4.2: Candidate Screening Implementation

### Current State: 90% Complete
**Priority:** MEDIUM | **Effort:** 2-3 days

### Critical Gaps
1. Call reminder notifications not fully automated
2. Screening dashboard analytics incomplete
3. Evidence upload workflow can be streamlined

### Key Implementation Tasks

#### Task 1: Automated Call Reminders (0.5 days)
**Create Command:** `app/Console/Commands/SendScreeningReminders.php`
```bash
php artisan make:command SendScreeningReminders
```

**Implementation:**
- Query candidates with pending screening calls
- Send notifications to screening staff
- Update reminder timestamps
- Schedule in `app/Console/Kernel.php`

**Schedule:**
```php
$schedule->command('screening:send-reminders')->daily();
```

#### Task 2: Screening Dashboard (1 day)
**Enhance Controller:** `app/Http/Controllers/ScreeningController.php`

**Add Analytics Method:**
```php
public function dashboard()
{
    $stats = [
        'total_pending' => Screening::where('status', 'pending')->count(),
        'completed_today' => Screening::whereDate('completed_at', today())->count(),
        'eligible_rate' => Screening::where('outcome', 'eligible')->count() / Screening::count() * 100,
        'by_campus' => Screening::groupBy('campus_id')->selectRaw('campus_id, count(*) as count')->get(),
    ];
    
    return view('screening.dashboard', compact('stats'));
}
```

**Create View:** `resources/views/screening/dashboard.blade.php`

#### Task 3: Evidence Upload UI (0.5 days)
**Improve:** `resources/views/screening/create.blade.php`
- Add drag-and-drop upload
- Preview uploaded files
- Better validation messages

### Testing Requirements
```bash
php artisan test --filter=ScreeningTest
```

**Test Cases:**
- Automated reminder sending
- Dashboard statistics accuracy
- Evidence upload and retrieval
- Permission enforcement

### Acceptance Criteria
- ✅ Reminders sent daily automatically
- ✅ Dashboard shows real-time statistics
- ✅ Evidence upload intuitive
- ✅ All tests passing

---

## Module 4.3: Registration at Campus Implementation

### Current State: 92% Complete
**Priority:** MEDIUM | **Effort:** 3-4 days

### Critical Gaps
1. Document expiry alerts not automated
2. Document verification workflow UI incomplete
3. Bulk document upload not available

### Key Implementation Tasks

#### Task 1: Document Expiry Alert System (1 day)
**Create Command:** `app/Console/Commands/CheckDocumentExpiry.php`

```php
class CheckDocumentExpiry extends Command
{
    protected $signature = 'documents:check-expiry';
    
    public function handle()
    {
        // Check documents expiring in 30 days
        $expiringDocs = RegistrationDocument::whereBetween('expiry_date', [
            now(),
            now()->addDays(30)
        ])->get();
        
        foreach ($expiringDocs as $doc) {
            // Send notification to candidate and admin
            Notification::send($doc->candidate, new DocumentExpiringNotification($doc));
        }
    }
}
```

**Schedule:**
```php
$schedule->command('documents:check-expiry')->weekly();
```

#### Task 2: Document Verification Workflow (1.5 days)
**Enhance:** `app/Http/Controllers/RegistrationController.php`

**Add Methods:**
```php
public function verifyDocument(RegistrationDocument $document)
{
    $this->authorize('verify', $document);
    
    $document->update([
        'status' => 'verified',
        'verified_by' => auth()->id(),
        'verified_at' => now(),
    ]);
    
    return back()->with('success', 'Document verified');
}

public function rejectDocument(Request $request, RegistrationDocument $document)
{
    $request->validate([
        'rejection_reason' => 'required|string|max:500',
    ]);
    
    $document->update([
        'status' => 'rejected',
        'rejection_reason' => $request->rejection_reason,
        'verified_by' => auth()->id(),
        'verified_at' => now(),
    ]);
    
    return back()->with('success', 'Document rejected');
}
```

**Create View:** `resources/views/registration/verify-documents.blade.php`

#### Task 3: Bulk Document Upload (0.5 days)
**Add to:** `resources/views/registration/create.blade.php`
- Multiple file upload field
- Bulk upload API endpoint
- Progress indicator

### Testing Requirements
```bash
php artisan test --filter=RegistrationTest
php artisan test --filter=DocumentExpiryTest
```

### Acceptance Criteria
- ✅ Expiry alerts automated
- ✅ Document verification workflow complete
- ✅ Bulk upload functional
- ✅ All tests passing

---

## Module 4.4: Training Management Implementation

### Current State: 85% Complete
**Priority:** MEDIUM | **Effort:** 4-5 days

### Critical Gaps
1. Certificate PDF generation needs refinement
2. Trainer evaluation system incomplete
3. Assessment result analytics missing
4. Bulk attendance upload UX poor

### Key Implementation Tasks

#### Task 1: Enhanced Certificate Generation (1.5 days)
**Create Service:** `app/Services/CertificateGenerationService.php`

```php
class CertificateGenerationService
{
    public function generateCertificate(Candidate $candidate, TrainingAssessment $assessment)
    {
        $pdf = PDF::loadView('certificates.training', [
            'candidate' => $candidate,
            'assessment' => $assessment,
            'trade' => $candidate->trade,
            'batch' => $candidate->batch,
            'issued_date' => now(),
        ]);
        
        $filename = "certificate_{$candidate->btevta_id}_" . now()->format('Ymd') . ".pdf";
        $path = "certificates/{$filename}";
        
        Storage::put($path, $pdf->output());
        
        TrainingCertificate::create([
            'candidate_id' => $candidate->id,
            'assessment_id' => $assessment->id,
            'certificate_number' => $this->generateCertificateNumber(),
            'file_path' => $path,
            'issued_at' => now(),
            'issued_by' => auth()->id(),
        ]);
        
        return $path;
    }
    
    public function generateBulkCertificates(Batch $batch)
    {
        $candidates = $batch->candidates()
            ->whereHas('assessments', function($q) {
                $q->where('type', 'final')->where('result', 'passed');
            })
            ->get();
        
        foreach ($candidates as $candidate) {
            $assessment = $candidate->assessments()
                ->where('type', 'final')
                ->where('result', 'passed')
                ->first();
            
            $this->generateCertificate($candidate, $assessment);
        }
    }
}
```

**Improve Template:** `resources/views/certificates/training.blade.php`
- Professional design
- QR code for verification
- Signature placeholders
- Security watermark

#### Task 2: Trainer Evaluation System (1.5 days)
**Create Model:** `app/Models/TrainerEvaluation.php`

**Migration:**
```php
Schema::create('trainer_evaluations', function (Blueprint $table) {
    $table->id();
    $table->foreignId('trainer_id')->constrained('users');
    $table->foreignId('batch_id')->constrained();
    $table->foreignId('candidate_id')->constrained();
    $table->integer('knowledge_rating')->comment('1-5');
    $table->integer('communication_rating')->comment('1-5');
    $table->integer('punctuality_rating')->comment('1-5');
    $table->integer('overall_rating')->comment('1-5');
    $table->text('comments')->nullable();
    $table->timestamps();
});
```

**Add to TrainingController:**
```php
public function evaluateTrainer(Request $request, User $trainer, Batch $batch)
{
    $validated = $request->validate([
        'knowledge_rating' => 'required|integer|min:1|max:5',
        'communication_rating' => 'required|integer|min:1|max:5',
        'punctuality_rating' => 'required|integer|min:1|max:5',
        'overall_rating' => 'required|integer|min:1|max:5',
        'comments' => 'nullable|string|max:1000',
    ]);
    
    TrainerEvaluation::create([
        'trainer_id' => $trainer->id,
        'batch_id' => $batch->id,
        'candidate_id' => auth()->id(),
        ...$validated,
    ]);
    
    return back()->with('success', 'Evaluation submitted');
}
```

#### Task 3: Assessment Analytics Dashboard (1 day)
**Add to TrainingController:**
```php
public function assessmentAnalytics(Batch $batch)
{
    $analytics = [
        'total_candidates' => $batch->candidates()->count(),
        'assessments_completed' => TrainingAssessment::where('batch_id', $batch->id)->count(),
        'pass_rate' => TrainingAssessment::where('batch_id', $batch->id)
            ->where('result', 'passed')
            ->count() / TrainingAssessment::where('batch_id', $batch->id)->count() * 100,
        'average_score' => TrainingAssessment::where('batch_id', $batch->id)->avg('score'),
        'by_type' => TrainingAssessment::where('batch_id', $batch->id)
            ->groupBy('type')
            ->selectRaw('type, avg(score) as avg_score, count(*) as count')
            ->get(),
    ];
    
    return view('training.analytics', compact('batch', 'analytics'));
}
```

#### Task 4: Bulk Attendance Upload (0.5 days)
**Improve:** `resources/views/training/attendance.blade.php`
- CSV upload option
- Template download
- Validation and preview

### Testing Requirements
```bash
php artisan test --filter=TrainingTest
php artisan test --filter=CertificateTest
php artisan test --filter=TrainerEvaluationTest
```

### Acceptance Criteria
- ✅ Certificate PDF professional quality
- ✅ Bulk certificate generation works
- ✅ Trainer evaluation system functional
- ✅ Assessment analytics accurate
- ✅ All tests passing

---

## Module 4.5: Visa Processing Implementation

### Current State: 90% Complete
**Priority:** LOW-MEDIUM | **Effort:** 3-4 days

### Critical Gaps
1. Stage transition validation can be stricter
2. Document expiry tracking for medical/passport
3. Visa status dashboard incomplete

### Key Implementation Tasks

#### Task 1: Strict Stage Validation (1 day)
**Enhance:** `app/Services/VisaProcessingService.php`

```php
public function canTransitionToStage(VisaProcess $visa, string $targetStage): array
{
    $currentStageOrder = VisaStage::from($visa->current_stage)->order();
    $targetStageOrder = VisaStage::from($targetStage)->order();
    
    $issues = [];
    
    // Can only move forward one stage at a time
    if ($targetStageOrder > $currentStageOrder + 1) {
        $issues[] = "Cannot skip stages. Complete {$visa->current_stage} first.";
    }
    
    // Check prerequisites based on stage
    switch ($targetStage) {
        case 'trade_test':
            if (!$visa->interview_passed) {
                $issues[] = "Interview must be passed before trade test.";
            }
            break;
            
        case 'medical':
            if (!$visa->takamol_completed) {
                $issues[] = "Takamol registration must be completed.";
            }
            break;
            
        case 'visa_submission':
            if (!$visa->medical_certificate || !$visa->biometric_completed) {
                $issues[] = "Medical certificate and biometrics required.";
            }
            break;
    }
    
    return $issues;
}
```

#### Task 2: Visa Document Expiry Tracking (1 day)
**Add to CheckDocumentExpiry Command:**
```php
// Check medical certificates
$expiringMedical = VisaProcess::whereNotNull('medical_certificate')
    ->whereBetween('medical_expiry', [now(), now()->addDays(30)])
    ->get();

// Check passport expiry
$expiringPassports = Candidate::whereHas('visa')
    ->whereBetween('passport_expiry', [now(), now()->addMonths(6)])
    ->get();

// Send notifications
```

#### Task 3: Visa Dashboard (1 day)
**Add to VisaProcessingController:**
```php
public function dashboard()
{
    $stats = [
        'total_in_process' => VisaProcess::whereNotIn('overall_status', ['completed', 'rejected'])->count(),
        'by_stage' => VisaProcess::groupBy('current_stage')
            ->selectRaw('current_stage, count(*) as count')
            ->get(),
        'average_processing_time' => VisaProcess::where('overall_status', 'completed')
            ->avg(DB::raw('DATEDIFF(completed_at, created_at)')),
        'bottlenecks' => $this->identifyBottlenecks(),
    ];
    
    return view('visa-processing.dashboard', compact('stats'));
}

private function identifyBottlenecks()
{
    // Stages where candidates are stuck > 30 days
    return VisaProcess::where('created_at', '<', now()->subDays(30))
        ->whereNotIn('overall_status', ['completed', 'rejected'])
        ->groupBy('current_stage')
        ->selectRaw('current_stage, count(*) as stuck_count')
        ->having('stuck_count', '>', 5)
        ->get();
}
```

### Testing Requirements
```bash
php artisan test --filter=VisaProcessingTest
```

### Acceptance Criteria
- ✅ Stage transitions validated strictly
- ✅ Document expiry alerts functional
- ✅ Dashboard shows bottlenecks
- ✅ All tests passing

---

## Module 4.6: Departure & Post-Deployment Implementation

### Current State: 95% Complete
**Priority:** MEDIUM | **Effort:** 3-4 days

### Critical Gaps
1. 90-day compliance checking not automated
2. Salary verification reminders missing
3. Welfare monitoring dashboard incomplete

### Key Implementation Tasks

#### Task 1: 90-Day Compliance Automation (1 day)
**Create Command:** `app/Console/Commands/Check90DayCompliance.php`

```php
class Check90DayCompliance extends Command
{
    protected $signature = 'departure:check-compliance';
    
    public function handle()
    {
        $departures = Departure::where('departure_date', '<=', now()->subDays(90))
            ->where('ninety_day_compliance_checked', false)
            ->get();
        
        foreach ($departures as $departure) {
            $complianceIssues = [];
            
            // Check required fields
            if (!$departure->iqama_number) {
                $complianceIssues[] = 'IQAMA not registered';
            }
            
            if (!$departure->absher_registered) {
                $complianceIssues[] = 'Absher not registered';
            }
            
            if (!$departure->salary_confirmed) {
                $complianceIssues[] = 'Salary not confirmed';
            }
            
            // Update compliance status
            $departure->update([
                'ninety_day_compliance_checked' => true,
                'ninety_day_compliance_status' => empty($complianceIssues) ? 'compliant' : 'non_compliant',
                'ninety_day_compliance_issues' => implode(', ', $complianceIssues),
                'ninety_day_compliance_checked_at' => now(),
            ]);
            
            // Send notifications if non-compliant
            if (!empty($complianceIssues)) {
                Notification::send($departure->candidate, new ComplianceIssueNotification($departure, $complianceIssues));
            }
        }
        
        $this->info("Checked compliance for {$departures->count()} departures");
    }
}
```

**Migration to add compliance fields:**
```php
Schema::table('departures', function (Blueprint $table) {
    $table->boolean('ninety_day_compliance_checked')->default(false);
    $table->string('ninety_day_compliance_status')->nullable();
    $table->text('ninety_day_compliance_issues')->nullable();
    $table->timestamp('ninety_day_compliance_checked_at')->nullable();
});
```

**Schedule:**
```php
$schedule->command('departure:check-compliance')->daily();
```

#### Task 2: Salary Verification Workflow (1 day)
**Add to DepartureController:**
```php
public function confirmSalary(Request $request, Departure $departure)
{
    $request->validate([
        'salary_amount' => 'required|numeric|min:0',
        'salary_currency' => 'required|string',
        'first_salary_date' => 'required|date',
        'proof_document' => 'required|file|mimes:pdf,jpg,png|max:5120',
    ]);
    
    $proofPath = $request->file('proof_document')->store('salary-proofs');
    
    $departure->update([
        'salary_amount' => $request->salary_amount,
        'salary_currency' => $request->salary_currency,
        'first_salary_date' => $request->first_salary_date,
        'salary_proof_path' => $proofPath,
        'salary_confirmed' => true,
        'salary_confirmed_by' => auth()->id(),
        'salary_confirmed_at' => now(),
    ]);
    
    return back()->with('success', 'Salary confirmed successfully');
}
```

**Create Reminder Command:**
```php
class SendSalaryVerificationReminders extends Command
{
    protected $signature = 'departure:salary-reminders';
    
    public function handle()
    {
        // Remind for candidates departed > 30 days without salary confirmation
        $needingReminders = Departure::where('departure_date', '<=', now()->subDays(30))
            ->where('salary_confirmed', false)
            ->get();
        
        foreach ($needingReminders as $departure) {
            Notification::send($departure->candidate, new SalaryVerificationReminderNotification($departure));
            Notification::send($departure->candidate->oep->users, new SalaryVerificationReminderNotification($departure));
        }
    }
}
```

#### Task 3: Welfare Monitoring Dashboard (1 day)
**Add to DepartureController:**
```php
public function welfareMonitoring()
{
    $stats = [
        'total_deployed' => Departure::count(),
        'ninety_day_compliant' => Departure::where('ninety_day_compliance_status', 'compliant')->count(),
        'ninety_day_non_compliant' => Departure::where('ninety_day_compliance_status', 'non_compliant')->count(),
        'salary_confirmed' => Departure::where('salary_confirmed', true)->count(),
        'pending_salary_confirmation' => Departure::where('salary_confirmed', false)
            ->where('departure_date', '<=', now()->subDays(30))
            ->count(),
        'at_risk_candidates' => $this->getAtRiskCandidates(),
        'by_country' => Departure::groupBy('destination_country')
            ->selectRaw('destination_country, count(*) as count')
            ->get(),
    ];
    
    return view('departure.welfare-monitoring', compact('stats'));
}

private function getAtRiskCandidates()
{
    return Departure::where(function($query) {
            $query->where('ninety_day_compliance_status', 'non_compliant')
                ->orWhere(function($q) {
                    $q->where('salary_confirmed', false)
                      ->where('departure_date', '<=', now()->subDays(45));
                });
        })
        ->with(['candidate', 'candidate.oep'])
        ->get();
}
```

### Testing Requirements
```bash
php artisan test --filter=DepartureTest
php artisan test --filter=ComplianceTest
```

### Acceptance Criteria
- ✅ 90-day compliance automated
- ✅ Salary verification workflow complete
- ✅ Welfare dashboard functional
- ✅ Notifications sent correctly
- ✅ All tests passing

---

## Module 4.7: Correspondence Implementation

### Current State: 90% Complete
**Priority:** LOW | **Effort:** 2-3 days

### Critical Gaps
1. Reply threading UI incomplete
2. Full-text search not implemented
3. Pendency tracking analytics missing

### Key Implementation Tasks

#### Task 1: Reply Threading UI (1 day)
**Enhance:** `resources/views/correspondence/show.blade.php`

```blade
<!-- Reply Thread Section -->
<div class="mt-6">
    <h3 class="text-lg font-semibold mb-4">Correspondence Thread</h3>
    
    <div class="space-y-4">
        <!-- Original correspondence -->
        <div class="bg-white border-l-4 border-blue-500 p-4">
            <div class="flex justify-between items-start">
                <div>
                    <p class="font-semibold">{{ $correspondence->sender }}</p>
                    <p class="text-sm text-gray-600">{{ $correspondence->created_at->format('M d, Y H:i') }}</p>
                </div>
                <span class="px-2 py-1 text-xs rounded-full bg-blue-100 text-blue-800">Original</span>
            </div>
            <p class="mt-2">{{ $correspondence->subject }}</p>
        </div>
        
        <!-- Replies -->
        @foreach($correspondence->replies as $reply)
        <div class="ml-8 bg-gray-50 border-l-4 border-gray-300 p-4">
            <div class="flex justify-between items-start">
                <div>
                    <p class="font-semibold">{{ $reply->sender }}</p>
                    <p class="text-sm text-gray-600">{{ $reply->created_at->format('M d, Y H:i') }}</p>
                </div>
                <span class="px-2 py-1 text-xs rounded-full bg-gray-100 text-gray-800">Reply</span>
            </div>
            <p class="mt-2">{{ $reply->subject }}</p>
        </div>
        @endforeach
    </div>
    
    <!-- Add Reply Button -->
    @can('manage_correspondence')
    <div class="mt-4">
        <a href="{{ route('correspondence.reply', $correspondence) }}" 
           class="bg-blue-600 text-white px-4 py-2 rounded-lg">
            Reply to this Correspondence
        </a>
    </div>
    @endcan
</div>
```

#### Task 2: Full-Text Search (0.5 days)
**Add to CorrespondenceController:**
```php
public function search(Request $request)
{
    $query = $request->get('q');
    
    $results = Correspondence::where(function($q) use ($query) {
        $q->where('subject', 'LIKE', "%{$query}%")
          ->orWhere('reference_number', 'LIKE', "%{$query}%")
          ->orWhere('sender', 'LIKE', "%{$query}%")
          ->orWhere('recipient', 'LIKE', "%{$query}%")
          ->orWhere('content', 'LIKE', "%{$query}%");
    })
    ->with(['creator'])
    ->latest()
    ->paginate(20);
    
    return view('correspondence.search-results', compact('results', 'query'));
}
```

#### Task 3: Pendency Analytics (0.5 days)
**Add to CorrespondenceController:**
```php
public function pendencyReport()
{
    $stats = [
        'total_pending' => Correspondence::where('status', 'pending')->count(),
        'pending_by_type' => Correspondence::where('status', 'pending')
            ->groupBy('type')
            ->selectRaw('type, count(*) as count')
            ->get(),
        'avg_response_time' => Correspondence::whereNotNull('response_date')
            ->avg(DB::raw('DATEDIFF(response_date, created_at)')),
        'overdue' => Correspondence::where('status', 'pending')
            ->where('due_date', '<', now())
            ->count(),
    ];
    
    return view('correspondence.pendency', compact('stats'));
}
```

### Acceptance Criteria
- ✅ Reply threading visible
- ✅ Search returns accurate results
- ✅ Pendency analytics functional
- ✅ All tests passing

---

## Module 4.8: Complaints & Grievance Implementation

### Current State: 90% Complete
**Priority:** MEDIUM | **Effort:** 3-4 days

### Critical Gaps
1. SLA monitoring command not scheduled
2. Auto-escalation workflow incomplete
3. Complaint analytics incomplete

### Key Implementation Tasks

#### Task 1: Schedule SLA Monitoring (0.5 days)
**Update:** `app/Console/Kernel.php`

```php
protected function schedule(Schedule $schedule)
{
    // SLA compliance check - run hourly during business hours
    $schedule->command('complaints:check-sla')
        ->hourly()
        ->between('8:00', '18:00')
        ->weekdays();
}
```

**Enhance Command:** `app/Console/Commands/CheckComplaintSLA.php`
```php
public function handle()
{
    $breached = Complaint::whereIn('status', ['submitted', 'under_review', 'investigating'])
        ->where('sla_deadline', '<', now())
        ->where('sla_breached', false)
        ->get();
    
    foreach ($breached as $complaint) {
        $complaint->update(['sla_breached' => true]);
        
        // Notify relevant parties
        Notification::send($complaint->assignee, new SLABreachedNotification($complaint));
        Notification::send(User::role('admin')->get(), new SLABreachedNotification($complaint));
    }
    
    $this->info("Updated {$breached->count()} complaints with SLA breach.");
}
```

#### Task 2: Auto-Escalation Workflow (1 day)
**Add to ComplaintService:**
```php
public function autoEscalate(Complaint $complaint)
{
    if (!$complaint->sla_breached) {
        return false;
    }
    
    // Determine escalation level
    $currentLevel = $complaint->escalation_level ?? 0;
    $newLevel = $currentLevel + 1;
    
    // Get escalation recipient
    $recipient = $this->getEscalationRecipient($complaint, $newLevel);
    
    $complaint->update([
        'status' => 'escalated',
        'escalation_level' => $newLevel,
        'escalated_to' => $recipient->id,
        'escalated_at' => now(),
    ]);
    
    // Notify
    Notification::send($recipient, new ComplaintEscalatedNotification($complaint));
    
    return true;
}

private function getEscalationRecipient(Complaint $complaint, int $level)
{
    return match($level) {
        1 => $complaint->campus->admin,
        2 => User::role('admin')->first(),
        3 => User::role('super_admin')->first(),
        default => User::role('super_admin')->first(),
    };
}
```

#### Task 3: Complaint Analytics Dashboard (1 day)
**Add to ComplaintController:**
```php
public function analytics()
{
    $stats = [
        'total' => Complaint::count(),
        'by_status' => Complaint::groupBy('status')
            ->selectRaw('status, count(*) as count')
            ->get(),
        'by_priority' => Complaint::groupBy('priority')
            ->selectRaw('priority, count(*) as count')
            ->get(),
        'by_category' => Complaint::groupBy('category')
            ->selectRaw('category, count(*) as count')
            ->get(),
        'sla_compliance_rate' => (Complaint::where('sla_breached', false)->count() / Complaint::count()) * 100,
        'avg_resolution_time' => Complaint::where('status', 'resolved')
            ->avg(DB::raw('DATEDIFF(resolved_at, created_at)')),
        'trend' => $this->getComplaintTrend(),
    ];
    
    return view('complaints.analytics', compact('stats'));
}

private function getComplaintTrend()
{
    return Complaint::where('created_at', '>=', now()->subMonths(6))
        ->groupBy(DB::raw('DATE_FORMAT(created_at, "%Y-%m")'))
        ->selectRaw('DATE_FORMAT(created_at, "%Y-%m") as month, count(*) as count')
        ->get();
}
```

### Acceptance Criteria
- ✅ SLA monitoring runs automatically
- ✅ Auto-escalation functional
- ✅ Analytics dashboard complete
- ✅ All tests passing

---

## Module 4.9: Document Archive Implementation

### Current State: 85% Complete
**Priority:** MEDIUM | **Effort:** 4-5 days

### Critical Gaps
1. Version comparison UI missing
2. Document tagging system basic
3. Advanced search incomplete
4. Expiry alert automation not scheduled

### Key Implementation Tasks

#### Task 1: Version Comparison UI (1.5 days)
**Add to DocumentArchiveController:**
```php
public function compareVersions(DocumentArchive $document, DocumentArchive $previousVersion)
{
    $this->authorize('view', $document);
    
    // Get document content or metadata
    $currentMeta = [
        'filename' => $document->filename,
        'size' => $document->file_size,
        'uploaded_at' => $document->created_at,
        'uploaded_by' => $document->creator->name,
        'verification_status' => $document->verification_status,
    ];
    
    $previousMeta = [
        'filename' => $previousVersion->filename,
        'size' => $previousVersion->file_size,
        'uploaded_at' => $previousVersion->created_at,
        'uploaded_by' => $previousVersion->creator->name,
        'verification_status' => $previousVersion->verification_status,
    ];
    
    return view('document-archive.compare', compact('document', 'previousVersion', 'currentMeta', 'previousMeta'));
}
```

**Create View:** `resources/views/document-archive/compare.blade.php`
```blade
<div class="grid grid-cols-2 gap-6">
    <!-- Current Version -->
    <div class="bg-white p-6 rounded-lg">
        <h3 class="text-lg font-semibold mb-4">Current Version</h3>
        <dl class="space-y-2">
            <dt class="text-sm font-medium text-gray-500">Filename</dt>
            <dd class="text-sm">{{ $currentMeta['filename'] }}</dd>
            
            <dt class="text-sm font-medium text-gray-500">Size</dt>
            <dd class="text-sm">{{ Number::fileSize($currentMeta['size']) }}</dd>
            
            <dt class="text-sm font-medium text-gray-500">Uploaded At</dt>
            <dd class="text-sm">{{ $currentMeta['uploaded_at']->format('M d, Y H:i') }}</dd>
            
            <dt class="text-sm font-medium text-gray-500">Uploaded By</dt>
            <dd class="text-sm">{{ $currentMeta['uploaded_by'] }}</dd>
        </dl>
        
        <iframe src="{{ route('documents.preview', $document) }}" 
                class="w-full h-96 mt-4 border rounded"></iframe>
    </div>
    
    <!-- Previous Version -->
    <div class="bg-gray-50 p-6 rounded-lg">
        <h3 class="text-lg font-semibold mb-4">Previous Version</h3>
        <dl class="space-y-2">
            <dt class="text-sm font-medium text-gray-500">Filename</dt>
            <dd class="text-sm">{{ $previousMeta['filename'] }}</dd>
            
            <dt class="text-sm font-medium text-gray-500">Size</dt>
            <dd class="text-sm">{{ Number::fileSize($previousMeta['size']) }}</dd>
            
            <dt class="text-sm font-medium text-gray-500">Uploaded At</dt>
            <dd class="text-sm">{{ $previousMeta['uploaded_at']->format('M d, Y H:i') }}</dd>
            
            <dt class="text-sm font-medium text-gray-500">Uploaded By</dt>
            <dd class="text-sm">{{ $previousMeta['uploaded_by'] }}</dd>
        </dl>
        
        <iframe src="{{ route('documents.preview', $previousVersion) }}" 
                class="w-full h-96 mt-4 border rounded"></iframe>
    </div>
</div>
```

#### Task 2: Document Tagging System (1 day)
**Migration:**
```php
Schema::create('document_tags', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->string('slug')->unique();
    $table->string('category')->nullable();
    $table->string('color')->nullable();
    $table->timestamps();
});

Schema::create('document_tag_pivot', function (Blueprint $table) {
    $table->foreignId('document_id')->constrained('document_archives');
    $table->foreignId('tag_id')->constrained('document_tags');
    $table->timestamps();
});
```

**Model:** `app/Models/DocumentTag.php`
```php
class DocumentTag extends Model
{
    protected $fillable = ['name', 'slug', 'category', 'color'];
    
    public function documents()
    {
        return $this->belongsToMany(DocumentArchive::class, 'document_tag_pivot', 'tag_id', 'document_id');
    }
}
```

**Add to DocumentArchive Model:**
```php
public function tags()
{
    return $this->belongsToMany(DocumentTag::class, 'document_tag_pivot', 'document_id', 'tag_id');
}
```

#### Task 3: Advanced Search (1 day)
**Enhance:** `app/Http/Controllers/DocumentArchiveController.php`

```php
public function advancedSearch(Request $request)
{
    $query = DocumentArchive::query();
    
    // Text search
    if ($request->filled('search')) {
        $search = $request->search;
        $query->where(function($q) use ($search) {
            $q->where('filename', 'LIKE', "%{$search}%")
              ->orWhere('description', 'LIKE', "%{$search}%");
        });
    }
    
    // Document type filter
    if ($request->filled('document_type')) {
        $query->where('document_type', $request->document_type);
    }
    
    // Tags filter
    if ($request->filled('tags')) {
        $tagIds = $request->tags;
        $query->whereHas('tags', function($q) use ($tagIds) {
            $q->whereIn('document_tags.id', $tagIds);
        });
    }
    
    // Date range
    if ($request->filled('date_from')) {
        $query->where('created_at', '>=', $request->date_from);
    }
    if ($request->filled('date_to')) {
        $query->where('created_at', '<=', $request->date_to);
    }
    
    // Verification status
    if ($request->filled('verification_status')) {
        $query->where('verification_status', $request->verification_status);
    }
    
    // Candidate filter
    if ($request->filled('candidate_id')) {
        $query->where('documentable_type', 'App\Models\Candidate')
              ->where('documentable_id', $request->candidate_id);
    }
    
    // Campus filter
    if ($request->filled('campus_id')) {
        $query->whereHasMorph('documentable', [Candidate::class], function($q) use ($request) {
            $q->where('campus_id', $request->campus_id);
        });
    }
    
    $documents = $query->with(['documentable', 'tags', 'creator'])
        ->latest()
        ->paginate(30);
    
    return view('document-archive.search', compact('documents'));
}
```

#### Task 4: Schedule Expiry Alerts (0.5 days)
**Update Kernel.php:**
```php
$schedule->command('documents:check-expiry')->weekly();
```

### Acceptance Criteria
- ✅ Version comparison UI functional
- ✅ Tagging system complete
- ✅ Advanced search works
- ✅ Expiry alerts automated
- ✅ All tests passing

---

## Module 4.10: Remittance Management Implementation

### Current State: 95% Complete
**Priority:** LOW | **Effort:** 2-3 days

### Critical Gaps
1. Advanced dashboard visualizations need chart library
2. Automated compliance reports not scheduled
3. Anomaly detection basic

### Key Implementation Tasks

#### Task 1: Dashboard Visualizations (1 day)
**Install Chart Library:**
```bash
npm install chart.js
```

**Enhance View:** `resources/views/remittances/dashboard.blade.php`
```blade
<!-- Monthly Remittance Trend Chart -->
<div class="bg-white p-6 rounded-lg shadow-sm">
    <h3 class="text-lg font-semibold mb-4">Monthly Remittance Trend</h3>
    <canvas id="remittanceTrendChart"></canvas>
</div>

<!-- Purpose Breakdown Chart -->
<div class="bg-white p-6 rounded-lg shadow-sm">
    <h3 class="text-lg font-semibold mb-4">Remittance Purpose Breakdown</h3>
    <canvas id="purposeBreakdownChart"></canvas>
</div>

<script>
// Monthly Trend Chart
const trendCtx = document.getElementById('remittanceTrendChart').getContext('2d');
new Chart(trendCtx, {
    type: 'line',
    data: {
        labels: @json($monthlyTrend->pluck('month')),
        datasets: [{
            label: 'Total Amount (PKR)',
            data: @json($monthlyTrend->pluck('total_amount')),
            borderColor: 'rgb(59, 130, 246)',
            backgroundColor: 'rgba(59, 130, 246, 0.1)',
            tension: 0.4
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: {
                position: 'top',
            }
        }
    }
});

// Purpose Breakdown Chart
const purposeCtx = document.getElementById('purposeBreakdownChart').getContext('2d');
new Chart(purposeCtx, {
    type: 'doughnut',
    data: {
        labels: @json($purposeBreakdown->pluck('purpose')),
        datasets: [{
            data: @json($purposeBreakdown->pluck('total')),
            backgroundColor: [
                'rgb(59, 130, 246)',
                'rgb(16, 185, 129)',
                'rgb(245, 158, 11)',
                'rgb(239, 68, 68)',
                'rgb(139, 92, 246)',
            ]
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: {
                position: 'right',
            }
        }
    }
});
</script>
```

#### Task 2: Automated Compliance Reports (0.5 days)
**Create Command:** `app/Console/Commands/GenerateRemittanceComplianceReport.php`

```php
class GenerateRemittanceComplianceReport extends Command
{
    protected $signature = 'remittance:compliance-report';
    
    public function handle()
    {
        $report = [
            'generated_at' => now(),
            'period' => 'Last 30 days',
            'total_deployed' => Departure::where('departure_date', '<=', now()->subDays(30))->count(),
            'with_remittances' => Departure::whereHas('remittances')->count(),
            'without_remittances' => Departure::whereDoesntHave('remittances')
                ->where('departure_date', '<=', now()->subDays(30))
                ->count(),
            'compliance_rate' => $this->calculateComplianceRate(),
            'flagged_candidates' => $this->getFlaggedCandidates(),
        ];
        
        // Generate PDF report
        $pdf = PDF::loadView('reports.remittance-compliance', compact('report'));
        $filename = 'remittance_compliance_' . now()->format('Ymd') . '.pdf';
        $path = storage_path('app/reports/' . $filename);
        $pdf->save($path);
        
        // Send to admins
        $admins = User::role('admin')->get();
        Notification::send($admins, new ComplianceReportGeneratedNotification($path));
        
        $this->info("Compliance report generated: {$filename}");
    }
}
```

**Schedule:**
```php
$schedule->command('remittance:compliance-report')->monthly();
```

#### Task 3: Enhanced Anomaly Detection (0.5 days)
**Enhance:** `app/Services/RemittanceAlertService.php`

```php
public function detectAnomalies(Candidate $candidate)
{
    $remittances = $candidate->remittances;
    $alerts = [];
    
    // Check for unusual amount spikes
    $avgAmount = $remittances->avg('amount');
    $stdDev = $this->calculateStdDev($remittances->pluck('amount'));
    
    foreach ($remittances as $remittance) {
        if ($remittance->amount > ($avgAmount + (2 * $stdDev))) {
            $alerts[] = [
                'type' => 'unusual_spike',
                'message' => "Unusually high amount: {$remittance->amount} (avg: {$avgAmount})",
                'remittance_id' => $remittance->id,
            ];
        }
    }
    
    // Check for frequency changes
    $avgDaysBetween = $this->calculateAverageDaysBetweenRemittances($remittances);
    $lastRemittance = $remittances->sortByDesc('transfer_date')->first();
    
    if ($lastRemittance && now()->diffInDays($lastRemittance->transfer_date) > ($avgDaysBetween * 1.5)) {
        $alerts[] = [
            'type' => 'frequency_drop',
            'message' => "Remittance frequency decreased significantly",
            'days_since_last' => now()->diffInDays($lastRemittance->transfer_date),
        ];
    }
    
    return $alerts;
}
```

### Acceptance Criteria
- ✅ Dashboard has interactive charts
- ✅ Compliance reports automated
- ✅ Anomaly detection enhanced
- ✅ All tests passing

---

## Implementation Timeline Summary

| Week | Focus | Modules |
|------|-------|---------|
| 1-2 | Critical | 4.1, Branding, Policies |
| 3 | Completion | 4.4, 4.9 |
| 4 | Completion | 4.6, 4.8 |
| 5 | APIs | All modules |
| 6-7 | Testing | E2E, Integration |
| 8 | Production | Config, Monitoring |
| 9-10 | Polish | UI/UX, UAT, Deploy |

---

**Total Effort:** 31-40 days (~6-8 weeks)

---

**END OF SUMMARY**
