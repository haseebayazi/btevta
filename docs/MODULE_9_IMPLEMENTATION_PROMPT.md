# Module 9: Post-Correspondence Enhancement - Implementation Prompt for Claude

**Project:** BTEVTA WASL
**Module:** Module 9 - Post-Correspondence (Success Stories & Complaints Enhancement)
**Status:** Existing Module - Requires Enhancement
**Date:** February 2026

---

## Executive Summary

Module 9 has **TWO EXISTING SUB-MODULES**:

### 9A: Success Stories (Existing)
- SuccessStoryController (10 methods)
- Evidence file storage
- Featured story promotion

### 9B: Complaints (Existing - Comprehensive)
- ComplaintController (21 methods)
- ComplaintService with SLA tracking
- Categories, priorities, escalation
- Evidence management

This prompt focuses on **ENHANCEMENTS** for structured success story capture and enhanced complaint evidence types.

**CRITICAL:** Both modules are working. Make targeted improvements only.

---

## Pre-Implementation Analysis

### Step 1: Read Existing Implementation

```
# Success Stories
app/Http/Controllers/SuccessStoryController.php
app/Models/SuccessStory.php
app/Enums/EvidenceType.php (if exists)
resources/views/success-stories/

# Complaints
app/Http/Controllers/ComplaintController.php
app/Services/ComplaintService.php
app/Models/Complaint.php
app/Models/ComplaintUpdate.php
app/Models/ComplaintEvidence.php
app/Enums/ComplaintStatus.php
app/Enums/ComplaintPriority.php
resources/views/complaints/

# Tests
tests/Feature/SuccessStoryControllerTest.php
tests/Feature/ComplaintControllerTest.php
```

---

## Part A: Success Stories Enhancement

### Required Changes

| Change ID | Type | Description | Priority |
|-----------|------|-------------|----------|
| SS-001 | MODIFIED | Enhanced success story structure | MEDIUM |
| SS-002 | NEW | Multiple evidence types (photo, video, interview) | MEDIUM |
| SS-003 | NEW | Employment outcome tracking | MEDIUM |
| SS-004 | NEW | Salary achievement tracking | LOW |
| SS-005 | MODIFIED | Public display enhancements | LOW |

### A1: Database Changes

```php
// database/migrations/YYYY_MM_DD_enhance_success_stories_table.php
Schema::table('success_stories', function (Blueprint $table) {
    // Story Details
    if (!Schema::hasColumn('success_stories', 'story_type')) {
        $table->enum('story_type', ['employment', 'career_growth', 'skill_achievement', 'remittance', 'other'])
            ->default('employment')
            ->after('candidate_id');
    }
    if (!Schema::hasColumn('success_stories', 'headline')) {
        $table->string('headline', 200)->nullable()->after('story_type');
    }

    // Employment Outcome
    if (!Schema::hasColumn('success_stories', 'employer_name')) {
        $table->string('employer_name', 200)->nullable()->after('description');
    }
    if (!Schema::hasColumn('success_stories', 'position_achieved')) {
        $table->string('position_achieved', 100)->nullable()->after('employer_name');
    }
    if (!Schema::hasColumn('success_stories', 'country_id')) {
        $table->foreignId('country_id')->nullable()->after('position_achieved')
            ->constrained()->nullOnDelete();
    }

    // Salary Achievement
    if (!Schema::hasColumn('success_stories', 'salary_achieved')) {
        $table->decimal('salary_achieved', 12, 2)->nullable()->after('country_id');
    }
    if (!Schema::hasColumn('success_stories', 'salary_currency')) {
        $table->string('salary_currency', 10)->default('SAR')->after('salary_achieved');
    }

    // Timeline
    if (!Schema::hasColumn('success_stories', 'employment_start_date')) {
        $table->date('employment_start_date')->nullable()->after('salary_currency');
    }
    if (!Schema::hasColumn('success_stories', 'time_to_employment_days')) {
        $table->integer('time_to_employment_days')->nullable()->after('employment_start_date');
    }

    // Metrics
    if (!Schema::hasColumn('success_stories', 'views_count')) {
        $table->integer('views_count')->default(0)->after('is_featured');
    }
    if (!Schema::hasColumn('success_stories', 'published_at')) {
        $table->timestamp('published_at')->nullable()->after('views_count');
    }

    // Approval Workflow
    if (!Schema::hasColumn('success_stories', 'status')) {
        $table->enum('status', ['draft', 'pending_review', 'approved', 'published', 'rejected'])
            ->default('draft')
            ->after('published_at');
    }
    if (!Schema::hasColumn('success_stories', 'approved_by')) {
        $table->foreignId('approved_by')->nullable()->after('status')
            ->constrained('users')->nullOnDelete();
    }
    if (!Schema::hasColumn('success_stories', 'approved_at')) {
        $table->timestamp('approved_at')->nullable()->after('approved_by');
    }

    $table->index('story_type');
    $table->index('status');
    $table->index('is_featured');
});
```

### A2: Create Success Story Evidence Table

```php
// database/migrations/YYYY_MM_DD_create_success_story_evidence_table.php
Schema::create('success_story_evidence', function (Blueprint $table) {
    $table->id();
    $table->foreignId('success_story_id')->constrained()->cascadeOnDelete();

    $table->enum('evidence_type', ['photo', 'video', 'document', 'interview', 'testimonial', 'certificate']);
    $table->string('title', 200);
    $table->text('description')->nullable();
    $table->string('file_path', 500);
    $table->string('mime_type', 100)->nullable();
    $table->integer('file_size')->nullable();
    $table->string('thumbnail_path', 500)->nullable();

    $table->boolean('is_primary')->default(false);
    $table->integer('display_order')->default(0);

    $table->foreignId('uploaded_by')->constrained('users');
    $table->timestamps();

    $table->index(['success_story_id', 'evidence_type']);
});
```

### A3: Create Enums

```php
// app/Enums/StoryType.php
<?php

namespace App\Enums;

enum StoryType: string
{
    case EMPLOYMENT = 'employment';
    case CAREER_GROWTH = 'career_growth';
    case SKILL_ACHIEVEMENT = 'skill_achievement';
    case REMITTANCE = 'remittance';
    case OTHER = 'other';

    public function label(): string
    {
        return match($this) {
            self::EMPLOYMENT => 'Employment Success',
            self::CAREER_GROWTH => 'Career Growth',
            self::SKILL_ACHIEVEMENT => 'Skill Achievement',
            self::REMITTANCE => 'Remittance Impact',
            self::OTHER => 'Other Success',
        };
    }

    public function icon(): string
    {
        return match($this) {
            self::EMPLOYMENT => 'fas fa-briefcase',
            self::CAREER_GROWTH => 'fas fa-chart-line',
            self::SKILL_ACHIEVEMENT => 'fas fa-award',
            self::REMITTANCE => 'fas fa-money-bill-wave',
            self::OTHER => 'fas fa-star',
        };
    }
}

// app/Enums/StoryStatus.php
<?php

namespace App\Enums;

enum StoryStatus: string
{
    case DRAFT = 'draft';
    case PENDING_REVIEW = 'pending_review';
    case APPROVED = 'approved';
    case PUBLISHED = 'published';
    case REJECTED = 'rejected';

    public function label(): string
    {
        return match($this) {
            self::DRAFT => 'Draft',
            self::PENDING_REVIEW => 'Pending Review',
            self::APPROVED => 'Approved',
            self::PUBLISHED => 'Published',
            self::REJECTED => 'Rejected',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::DRAFT => 'secondary',
            self::PENDING_REVIEW => 'warning',
            self::APPROVED => 'info',
            self::PUBLISHED => 'success',
            self::REJECTED => 'danger',
        };
    }
}

// app/Enums/StoryEvidenceType.php
<?php

namespace App\Enums;

enum StoryEvidenceType: string
{
    case PHOTO = 'photo';
    case VIDEO = 'video';
    case DOCUMENT = 'document';
    case INTERVIEW = 'interview';
    case TESTIMONIAL = 'testimonial';
    case CERTIFICATE = 'certificate';

    public function label(): string
    {
        return match($this) {
            self::PHOTO => 'Photograph',
            self::VIDEO => 'Video',
            self::DOCUMENT => 'Document',
            self::INTERVIEW => 'Interview Recording',
            self::TESTIMONIAL => 'Written Testimonial',
            self::CERTIFICATE => 'Certificate/Award',
        };
    }

    public function allowedMimes(): array
    {
        return match($this) {
            self::PHOTO => ['jpg', 'jpeg', 'png', 'webp'],
            self::VIDEO => ['mp4', 'mov', 'avi', 'webm'],
            self::DOCUMENT => ['pdf', 'doc', 'docx'],
            self::INTERVIEW => ['mp3', 'wav', 'mp4', 'mov'],
            self::TESTIMONIAL => ['pdf', 'doc', 'docx', 'txt'],
            self::CERTIFICATE => ['pdf', 'jpg', 'jpeg', 'png'],
        };
    }

    public function maxSizeMB(): int
    {
        return match($this) {
            self::VIDEO, self::INTERVIEW => 100,
            default => 10,
        };
    }
}
```

### A4: Update SuccessStory Model

```php
// Add to app/Models/SuccessStory.php

use App\Enums\StoryType;
use App\Enums\StoryStatus;

// Add to $fillable
'story_type',
'headline',
'employer_name',
'position_achieved',
'country_id',
'salary_achieved',
'salary_currency',
'employment_start_date',
'time_to_employment_days',
'views_count',
'published_at',
'status',
'approved_by',
'approved_at',

// Add to $casts
'story_type' => StoryType::class,
'status' => StoryStatus::class,
'salary_achieved' => 'decimal:2',
'employment_start_date' => 'date',
'published_at' => 'datetime',
'approved_at' => 'datetime',

// Add relationships
public function evidence()
{
    return $this->hasMany(SuccessStoryEvidence::class)->orderBy('display_order');
}

public function primaryEvidence()
{
    return $this->hasOne(SuccessStoryEvidence::class)->where('is_primary', true);
}

public function country()
{
    return $this->belongsTo(Country::class);
}

public function approvedBy()
{
    return $this->belongsTo(User::class, 'approved_by');
}

// Add methods
public function submitForReview(): void
{
    $this->status = StoryStatus::PENDING_REVIEW;
    $this->save();
}

public function approve(): void
{
    $this->status = StoryStatus::APPROVED;
    $this->approved_by = auth()->id();
    $this->approved_at = now();
    $this->save();
}

public function publish(): void
{
    $this->status = StoryStatus::PUBLISHED;
    $this->published_at = now();
    $this->save();
}

public function reject(string $reason): void
{
    $this->status = StoryStatus::REJECTED;
    $this->rejection_reason = $reason;
    $this->save();
}

public function incrementViews(): void
{
    $this->increment('views_count');
}
```

### A5: Create SuccessStoryEvidence Model

```php
// app/Models/SuccessStoryEvidence.php
<?php

namespace App\Models;

use App\Enums\StoryEvidenceType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SuccessStoryEvidence extends Model
{
    use HasFactory;

    protected $table = 'success_story_evidence';

    protected $fillable = [
        'success_story_id',
        'evidence_type',
        'title',
        'description',
        'file_path',
        'mime_type',
        'file_size',
        'thumbnail_path',
        'is_primary',
        'display_order',
        'uploaded_by',
    ];

    protected $casts = [
        'evidence_type' => StoryEvidenceType::class,
        'is_primary' => 'boolean',
    ];

    public function successStory()
    {
        return $this->belongsTo(SuccessStory::class);
    }

    public function uploadedBy()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function getUrlAttribute(): string
    {
        return route('secure-file.view', ['path' => $this->file_path]);
    }

    public function getThumbnailUrlAttribute(): ?string
    {
        return $this->thumbnail_path
            ? route('secure-file.view', ['path' => $this->thumbnail_path])
            : null;
    }
}
```

---

## Part B: Complaints Enhancement

### Required Changes

| Change ID | Type | Description | Priority |
|-----------|------|-------------|----------|
| CO-001 | EXISTS | SLA tracking - already comprehensive | N/A |
| CO-002 | MODIFIED | Structured evidence types | MEDIUM |
| CO-003 | NEW | Evidence categorization | MEDIUM |
| CO-004 | NEW | Complaint templates | LOW |
| CO-005 | MODIFIED | Enhanced dashboard metrics | MEDIUM |

### B1: Enhance Complaint Evidence Table

```php
// database/migrations/YYYY_MM_DD_enhance_complaint_evidence_table.php
Schema::table('complaint_evidence', function (Blueprint $table) {
    if (!Schema::hasColumn('complaint_evidence', 'evidence_category')) {
        $table->enum('evidence_category', [
            'initial_report',
            'supporting_document',
            'photo_video',
            'witness_statement',
            'communication_record',
            'resolution_proof',
            'other'
        ])->default('supporting_document')->after('file_path');
    }
    if (!Schema::hasColumn('complaint_evidence', 'is_confidential')) {
        $table->boolean('is_confidential')->default(false)->after('evidence_category');
    }
    if (!Schema::hasColumn('complaint_evidence', 'verified')) {
        $table->boolean('verified')->default(false)->after('is_confidential');
    }
    if (!Schema::hasColumn('complaint_evidence', 'verified_by')) {
        $table->foreignId('verified_by')->nullable()->after('verified')
            ->constrained('users')->nullOnDelete();
    }
    if (!Schema::hasColumn('complaint_evidence', 'verified_at')) {
        $table->timestamp('verified_at')->nullable()->after('verified_by');
    }

    $table->index('evidence_category');
});
```

### B2: Create Complaint Templates Table

```php
// database/migrations/YYYY_MM_DD_create_complaint_templates_table.php
Schema::create('complaint_templates', function (Blueprint $table) {
    $table->id();
    $table->string('name', 100);
    $table->string('category', 50);
    $table->text('description_template');
    $table->json('required_evidence_types')->nullable();
    $table->json('suggested_actions')->nullable();
    $table->enum('default_priority', ['low', 'normal', 'high', 'urgent'])->default('normal');
    $table->integer('suggested_sla_hours')->nullable();
    $table->boolean('is_active')->default(true);
    $table->timestamps();

    $table->index(['category', 'is_active']);
});
```

### B3: Create Evidence Category Enum

```php
// app/Enums/ComplaintEvidenceCategory.php
<?php

namespace App\Enums;

enum ComplaintEvidenceCategory: string
{
    case INITIAL_REPORT = 'initial_report';
    case SUPPORTING_DOCUMENT = 'supporting_document';
    case PHOTO_VIDEO = 'photo_video';
    case WITNESS_STATEMENT = 'witness_statement';
    case COMMUNICATION_RECORD = 'communication_record';
    case RESOLUTION_PROOF = 'resolution_proof';
    case OTHER = 'other';

    public function label(): string
    {
        return match($this) {
            self::INITIAL_REPORT => 'Initial Report',
            self::SUPPORTING_DOCUMENT => 'Supporting Document',
            self::PHOTO_VIDEO => 'Photo/Video Evidence',
            self::WITNESS_STATEMENT => 'Witness Statement',
            self::COMMUNICATION_RECORD => 'Communication Record',
            self::RESOLUTION_PROOF => 'Resolution Proof',
            self::OTHER => 'Other',
        };
    }

    public function description(): string
    {
        return match($this) {
            self::INITIAL_REPORT => 'The original complaint report or incident description',
            self::SUPPORTING_DOCUMENT => 'Documents that support the complaint (contracts, letters, etc.)',
            self::PHOTO_VIDEO => 'Visual evidence of the issue',
            self::WITNESS_STATEMENT => 'Statements from witnesses to the incident',
            self::COMMUNICATION_RECORD => 'Emails, messages, or call recordings',
            self::RESOLUTION_PROOF => 'Evidence that the issue was resolved',
            self::OTHER => 'Other relevant evidence',
        };
    }
}
```

### B4: Update ComplaintEvidence Model

```php
// Add to app/Models/ComplaintEvidence.php

use App\Enums\ComplaintEvidenceCategory;

// Add to $fillable
'evidence_category',
'is_confidential',
'verified',
'verified_by',
'verified_at',

// Add to $casts
'evidence_category' => ComplaintEvidenceCategory::class,
'is_confidential' => 'boolean',
'verified' => 'boolean',
'verified_at' => 'datetime',

// Add methods
public function verify(): void
{
    $this->verified = true;
    $this->verified_by = auth()->id();
    $this->verified_at = now();
    $this->save();

    activity()
        ->performedOn($this)
        ->causedBy(auth()->user())
        ->log('Evidence verified');
}
```

### B5: Create ComplaintTemplate Model

```php
// app/Models/ComplaintTemplate.php
<?php

namespace App\Models;

use App\Enums\ComplaintPriority;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ComplaintTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'category',
        'description_template',
        'required_evidence_types',
        'suggested_actions',
        'default_priority',
        'suggested_sla_hours',
        'is_active',
    ];

    protected $casts = [
        'required_evidence_types' => 'array',
        'suggested_actions' => 'array',
        'default_priority' => ComplaintPriority::class,
        'is_active' => 'boolean',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }
}
```

### B6: Enhance Complaint Service

Add to `app/Services/ComplaintService.php`:

```php
/**
 * Get enhanced dashboard metrics
 */
public function getEnhancedDashboard(?int $campusId = null): array
{
    $baseMetrics = $this->getStatistics($campusId);

    // Evidence metrics
    $evidenceMetrics = ComplaintEvidence::query()
        ->selectRaw('evidence_category, COUNT(*) as count')
        ->groupBy('evidence_category')
        ->pluck('count', 'evidence_category');

    // Resolution time metrics
    $resolutionMetrics = Complaint::where('status', 'resolved')
        ->whereNotNull('resolved_at')
        ->selectRaw('
            AVG(TIMESTAMPDIFF(HOUR, created_at, resolved_at)) as avg_resolution_hours,
            MIN(TIMESTAMPDIFF(HOUR, created_at, resolved_at)) as min_resolution_hours,
            MAX(TIMESTAMPDIFF(HOUR, created_at, resolved_at)) as max_resolution_hours
        ')
        ->first();

    // Category trends (last 6 months)
    $categoryTrends = Complaint::selectRaw('
            category,
            DATE_FORMAT(created_at, "%Y-%m") as month,
            COUNT(*) as count
        ')
        ->where('created_at', '>=', now()->subMonths(6))
        ->groupBy('category', 'month')
        ->orderBy('month')
        ->get()
        ->groupBy('category');

    return array_merge($baseMetrics, [
        'evidence_by_category' => $evidenceMetrics,
        'resolution_metrics' => $resolutionMetrics,
        'category_trends' => $categoryTrends,
        'templates' => ComplaintTemplate::active()->get(),
    ]);
}

/**
 * Create complaint from template
 */
public function createFromTemplate(
    ComplaintTemplate $template,
    Candidate $candidate,
    string $description,
    array $additionalData = []
): Complaint {
    return $this->createComplaint([
        'candidate_id' => $candidate->id,
        'category' => $template->category,
        'description' => $description,
        'priority' => $template->default_priority->value,
        'sla_hours' => $template->suggested_sla_hours,
        ...$additionalData,
    ]);
}

/**
 * Add categorized evidence
 */
public function addCategorizedEvidence(
    Complaint $complaint,
    $file,
    string $category,
    bool $isConfidential = false,
    ?string $description = null
): ComplaintEvidence {
    $path = $file->store("complaints/{$complaint->id}/evidence", 'private');

    return ComplaintEvidence::create([
        'complaint_id' => $complaint->id,
        'file_name' => $file->getClientOriginalName(),
        'file_path' => $path,
        'file_type' => $file->getMimeType(),
        'evidence_category' => $category,
        'is_confidential' => $isConfidential,
        'description' => $description,
        'uploaded_by' => auth()->id(),
    ]);
}
```

---

## Phase C: Create Controllers & Routes

### Success Stories Controller Enhancements

```php
// Add to SuccessStoryController

public function submitForReview(SuccessStory $story)
{
    $this->authorize('update', $story);
    $story->submitForReview();
    return back()->with('success', 'Story submitted for review.');
}

public function approve(SuccessStory $story)
{
    $this->authorize('approve', $story);
    $story->approve();
    return back()->with('success', 'Story approved.');
}

public function publish(SuccessStory $story)
{
    $this->authorize('publish', $story);
    $story->publish();
    return back()->with('success', 'Story published successfully.');
}

public function addEvidence(Request $request, SuccessStory $story)
{
    $this->authorize('update', $story);

    $validated = $request->validate([
        'evidence_type' => 'required|in:photo,video,document,interview,testimonial,certificate',
        'title' => 'required|string|max:200',
        'description' => 'nullable|string|max:1000',
        'file' => 'required|file|max:102400', // 100MB max for videos
        'is_primary' => 'boolean',
    ]);

    // Validate file type matches evidence type
    $evidenceType = StoryEvidenceType::from($validated['evidence_type']);
    $extension = $request->file('file')->getClientOriginalExtension();

    if (!in_array(strtolower($extension), $evidenceType->allowedMimes())) {
        return back()->with('error', 'Invalid file type for this evidence category.');
    }

    $path = $request->file('file')->store("success-stories/{$story->id}", 'private');

    SuccessStoryEvidence::create([
        'success_story_id' => $story->id,
        'evidence_type' => $validated['evidence_type'],
        'title' => $validated['title'],
        'description' => $validated['description'],
        'file_path' => $path,
        'mime_type' => $request->file('file')->getMimeType(),
        'file_size' => $request->file('file')->getSize(),
        'is_primary' => $request->boolean('is_primary'),
        'uploaded_by' => auth()->id(),
    ]);

    return back()->with('success', 'Evidence added successfully.');
}

public function publicGallery()
{
    $stories = SuccessStory::where('status', 'published')
        ->with(['candidate', 'primaryEvidence', 'country'])
        ->orderBy('is_featured', 'desc')
        ->orderBy('published_at', 'desc')
        ->paginate(12);

    return view('success-stories.public-gallery', compact('stories'));
}
```

### Add Routes

```php
// routes/web.php

// Success Stories
Route::middleware(['auth'])->prefix('success-stories')->name('success-stories.')->group(function () {
    // Existing CRUD routes...

    Route::post('/{story}/submit-review', [SuccessStoryController::class, 'submitForReview'])->name('submit-review');
    Route::post('/{story}/approve', [SuccessStoryController::class, 'approve'])->name('approve');
    Route::post('/{story}/publish', [SuccessStoryController::class, 'publish'])->name('publish');
    Route::post('/{story}/evidence', [SuccessStoryController::class, 'addEvidence'])->name('add-evidence');
});

Route::get('/stories', [SuccessStoryController::class, 'publicGallery'])->name('success-stories.public');

// Complaints (enhanced)
Route::middleware(['auth'])->prefix('complaints')->name('complaints.')->group(function () {
    // Existing routes...

    Route::get('/templates', [ComplaintController::class, 'templates'])->name('templates');
    Route::post('/from-template/{template}', [ComplaintController::class, 'createFromTemplate'])->name('from-template');
    Route::post('/{complaint}/evidence/categorized', [ComplaintController::class, 'addCategorizedEvidence'])->name('add-categorized-evidence');
    Route::post('/evidence/{evidence}/verify', [ComplaintController::class, 'verifyEvidence'])->name('verify-evidence');
    Route::get('/enhanced-dashboard', [ComplaintController::class, 'enhancedDashboard'])->name('enhanced-dashboard');
});
```

---

## Phase D: Create Views

### Success Stories Views

1. `resources/views/success-stories/create-enhanced.blade.php`
2. `resources/views/success-stories/show-enhanced.blade.php`
3. `resources/views/success-stories/public-gallery.blade.php`
4. `resources/views/success-stories/partials/evidence-upload.blade.php`
5. `resources/views/success-stories/partials/evidence-gallery.blade.php`

### Complaints Views (Enhancements)

1. `resources/views/complaints/enhanced-dashboard.blade.php`
2. `resources/views/complaints/templates.blade.php`
3. `resources/views/complaints/partials/categorized-evidence-form.blade.php`

---

## Phase E: Seed Data

### Complaint Templates Seeder

```php
// database/seeders/ComplaintTemplatesSeeder.php
<?php

namespace Database\Seeders;

use App\Models\ComplaintTemplate;
use Illuminate\Database\Seeder;

class ComplaintTemplatesSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [
            [
                'name' => 'Salary Dispute',
                'category' => 'salary',
                'description_template' => 'I am reporting a salary dispute. Expected salary: [amount]. Received salary: [amount]. Details: [description]',
                'required_evidence_types' => ['contract', 'payslip', 'bank_statement'],
                'default_priority' => 'high',
                'suggested_sla_hours' => 48,
            ],
            [
                'name' => 'Workplace Safety Issue',
                'category' => 'facility',
                'description_template' => 'I am reporting a workplace safety concern. Location: [location]. Issue: [description]. Date observed: [date]',
                'required_evidence_types' => ['photo', 'witness_statement'],
                'default_priority' => 'urgent',
                'suggested_sla_hours' => 24,
            ],
            [
                'name' => 'Document Issue',
                'category' => 'document',
                'description_template' => 'I am experiencing an issue with my documents. Document type: [type]. Issue: [description]',
                'required_evidence_types' => ['document_copy'],
                'default_priority' => 'normal',
                'suggested_sla_hours' => 72,
            ],
            [
                'name' => 'Harassment Report',
                'category' => 'conduct',
                'description_template' => 'I am reporting harassment. Type: [type]. Details: [description]. This is a confidential report.',
                'required_evidence_types' => ['witness_statement', 'communication_record'],
                'default_priority' => 'urgent',
                'suggested_sla_hours' => 24,
            ],
        ];

        foreach ($templates as $template) {
            ComplaintTemplate::updateOrCreate(
                ['name' => $template['name']],
                array_merge($template, ['is_active' => true])
            );
        }
    }
}
```

---

## Validation Checklist

### Success Stories
- [ ] Enhanced columns added to success_stories table
- [ ] success_story_evidence table created
- [ ] StoryType, StoryStatus, StoryEvidenceType enums created
- [ ] SuccessStory model enhanced
- [ ] SuccessStoryEvidence model created
- [ ] Approval workflow works (draft → review → approve → publish)
- [ ] Multiple evidence types upload works
- [ ] Public gallery displays published stories
- [ ] Featured stories highlighted

### Complaints
- [ ] complaint_evidence table enhanced with categories
- [ ] complaint_templates table created
- [ ] ComplaintEvidenceCategory enum created
- [ ] ComplaintTemplate model created
- [ ] ComplaintEvidence model enhanced
- [ ] Service methods added
- [ ] Template-based complaint creation works
- [ ] Categorized evidence upload works
- [ ] Evidence verification works
- [ ] Enhanced dashboard with metrics works
- [ ] Template seeder created

---

## Success Criteria

Module 9 Enhancement is complete when:

1. Success stories have structured types and headlines
2. Employment outcome tracking (employer, position, salary) works
3. Multiple evidence types (photo, video, interview) work
4. Story approval workflow (draft → review → approve → publish) works
5. Public gallery displays featured stories
6. Complaint evidence categorization works
7. Complaint templates work
8. Evidence verification workflow works
9. Enhanced dashboard shows metrics and trends
10. All tests pass

---

*End of Module 9 Implementation Prompt*
