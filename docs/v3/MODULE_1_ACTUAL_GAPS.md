# Module 1: Pre-Departure Documents - COMPLETE Gap Analysis (v2)

## Executive Summary

**Module 1 backend is 90% implemented. However, there are critical UI/UX and workflow integration gaps that make it unusable in practice.**

Previous analysis missed:
- No navigation link to access the feature
- Status checks don't align with the new workflow
- Policy blocks document editing for the wrong statuses

---

## Implementation Status

### ✅ BACKEND IMPLEMENTED (Do NOT recreate)

| Component | File | Status |
|-----------|------|--------|
| PreDepartureDocument Model | `app/Models/PreDepartureDocument.php` | Complete |
| DocumentChecklist Model | `app/Models/DocumentChecklist.php` | Complete |
| PreDepartureDocumentService | `app/Services/PreDepartureDocumentService.php` | Complete (584 lines) |
| PreDepartureDocumentController | `app/Http/Controllers/PreDepartureDocumentController.php` | Complete |
| PreDepartureReportController | `app/Http/Controllers/PreDepartureReportController.php` | Has import bug |
| CandidateLicense Model | `app/Models/CandidateLicense.php` | Complete |
| Routes | `routes/web.php` lines 161-190 | Complete |
| Pre-departure views | `resources/views/candidates/pre-departure-documents/` | 4 files exist |
| Unit Tests | `tests/Unit/CandidatePreDepartureTest.php` | 6 tests |

### ❌ GAPS THAT MAKE IT UNUSABLE

---

## Gap 1: Missing Import (BUG)
**File:** `app/Http/Controllers/PreDepartureReportController.php`
**Line:** 24
**Issue:** Uses `PreDepartureDocument::class` without importing it

**Fix:**
```php
// Add after line 6
use App\Models\PreDepartureDocument;
```

---

## Gap 2: No Navigation Link to Pre-Departure Documents (CRITICAL)
**File:** `resources/views/candidates/show.blade.php`
**Issue:** There is NO link/button to access pre-departure documents from the candidate detail page. Users can only access it by typing the URL directly.

**Fix:** Add a card/button in the sidebar Actions section (around line 188):

```blade
{{-- Pre-Departure Documents Card - Add before Action Card --}}
@if(in_array($candidate->status, ['listed', 'pre_departure_docs', 'new']))
<div class="bg-white rounded-lg shadow-md p-6">
    <h3 class="text-lg font-semibold text-gray-900 mb-4">Pre-Departure Documents</h3>
    @php
        $docStatus = $candidate->getPreDepartureDocumentStatus();
    @endphp
    <div class="mb-4">
        <div class="flex justify-between text-sm mb-1">
            <span>Mandatory Documents</span>
            <span class="{{ $docStatus['is_complete'] ? 'text-green-600' : 'text-yellow-600' }}">
                {{ $docStatus['mandatory_uploaded'] }}/{{ $docStatus['mandatory_total'] }}
            </span>
        </div>
        <div class="w-full bg-gray-200 rounded-full h-2">
            <div class="h-2 rounded-full {{ $docStatus['is_complete'] ? 'bg-green-500' : 'bg-yellow-500' }}"
                 style="width: {{ $docStatus['completion_percentage'] }}%"></div>
        </div>
    </div>
    @can('viewAny', [App\Models\PreDepartureDocument::class, $candidate])
    <a href="{{ route('candidates.pre-departure-documents.index', $candidate) }}"
       class="block w-full {{ $docStatus['is_complete'] ? 'bg-green-600 hover:bg-green-700' : 'bg-yellow-500 hover:bg-yellow-600' }} text-white text-center px-4 py-2 rounded-lg transition">
        <i class="fas fa-file-alt mr-2"></i>
        {{ $docStatus['is_complete'] ? 'View Documents' : 'Upload Documents' }}
    </a>
    @endcan
</div>
@endif
```

---

## Gap 3: Policy Status Check is Wrong (CRITICAL)
**File:** `app/Policies/PreDepartureDocumentPolicy.php`
**Lines:** 53, 83, 118
**Issue:** Policy checks `$candidate->status !== 'new'` but the new workflow uses:
- `LISTED` (order 1) - candidates start here
- `PRE_DEPARTURE_DOCS` (order 2) - during document collection
- `NEW` (order 0) - legacy status

**Current (broken):**
```php
if ($candidate->status !== 'new') {
    return false;
}
```

**Fix (correct):**
```php
// Allow editing during document collection phase
$editableStatuses = ['new', 'listed', 'pre_departure_docs'];
if (!in_array($candidate->status, $editableStatuses)) {
    return false;
}
```

Apply this fix to:
- `create()` method (line 53)
- `update()` method (line 83)
- `delete()` method (line 118)

---

## Gap 4: View Status Check is Wrong
**File:** `resources/views/candidates/pre-departure-documents/index.blade.php`
**Lines:** 42, 75
**Issue:** Same as policy - checks `$candidate->status === 'new'` instead of editable statuses

**Line 42 - Status badge:**
```blade
{{-- Change from: --}}
<span class="badge badge-{{ $candidate->status === 'new' ? 'primary' : 'secondary' }}">

{{-- To: --}}
<span class="badge badge-{{ in_array($candidate->status, ['new', 'listed', 'pre_departure_docs']) ? 'primary' : 'secondary' }}">
```

**Line 75 - Read-only alert:**
```blade
{{-- Change from: --}}
@if($candidate->status !== 'new')

{{-- To: --}}
@if(!in_array($candidate->status, ['new', 'listed', 'pre_departure_docs']))
```

---

## Gap 5: Missing DocumentChecklistSeeder (CRITICAL)
**File to create:** `database/seeders/DocumentChecklistSeeder.php`
**Issue:** Without this seeder, the DocumentChecklist table is empty and the page shows no document cards.

```php
<?php

namespace Database\Seeders;

use App\Models\DocumentChecklist;
use Illuminate\Database\Seeder;

class DocumentChecklistSeeder extends Seeder
{
    public function run(): void
    {
        $checklists = [
            // Mandatory (5)
            [
                'name' => 'CNIC (Front & Back)',
                'code' => 'CNIC',
                'description' => 'Computerized National Identity Card - both sides clearly visible',
                'category' => 'mandatory',
                'is_mandatory' => true,
                'display_order' => 1,
                'is_active' => true,
            ],
            [
                'name' => 'Passport (1st & 2nd Page)',
                'code' => 'PASSPORT',
                'description' => 'Valid passport with at least 6 months validity remaining',
                'category' => 'mandatory',
                'is_mandatory' => true,
                'display_order' => 2,
                'is_active' => true,
            ],
            [
                'name' => 'Domicile Certificate',
                'code' => 'DOMICILE',
                'description' => 'Valid domicile certificate from concerned authority',
                'category' => 'mandatory',
                'is_mandatory' => true,
                'display_order' => 3,
                'is_active' => true,
            ],
            [
                'name' => 'Family Registration Certificate (FRC)',
                'code' => 'FRC',
                'description' => 'Family Registration Certificate from NADRA',
                'category' => 'mandatory',
                'is_mandatory' => true,
                'display_order' => 4,
                'is_active' => true,
            ],
            [
                'name' => 'Police Character Certificate (PCC)',
                'code' => 'PCC',
                'description' => 'Police Clearance Certificate from local police station',
                'category' => 'mandatory',
                'is_mandatory' => true,
                'display_order' => 5,
                'is_active' => true,
            ],

            // Optional (3)
            [
                'name' => 'Pre-Medical Results',
                'code' => 'PRE_MEDICAL',
                'description' => 'Medical examination results from authorized center',
                'category' => 'optional',
                'is_mandatory' => false,
                'display_order' => 6,
                'is_active' => true,
            ],
            [
                'name' => 'Professional Certifications',
                'code' => 'CERTIFICATIONS',
                'description' => 'Trade or professional certificates relevant to the job',
                'category' => 'optional',
                'is_mandatory' => false,
                'display_order' => 7,
                'is_active' => true,
            ],
            [
                'name' => 'Resume/CV',
                'code' => 'RESUME',
                'description' => 'Updated Curriculum Vitae or Resume',
                'category' => 'optional',
                'is_mandatory' => false,
                'display_order' => 8,
                'is_active' => true,
            ],
        ];

        foreach ($checklists as $checklist) {
            DocumentChecklist::updateOrCreate(
                ['code' => $checklist['code']],
                $checklist
            );
        }

        $this->command->info('Created ' . count($checklists) . ' document checklist items.');
    }
}
```

---

## Gap 6: Update DatabaseSeeder
**File:** `database/seeders/DatabaseSeeder.php`
**Issue:** DocumentChecklistSeeder must be called during seeding

**Add inside run() method:**
```php
$this->call(DocumentChecklistSeeder::class);
```

---

## Gap 7: Missing PDF Report View
**File to create:** `resources/views/reports/pre-departure/individual-pdf.blade.php`
**Issue:** PreDepartureDocumentService line 422 references this view but it doesn't exist

Create directory first: `resources/views/reports/pre-departure/`

```blade
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Pre-Departure Documents Report - {{ $candidate->name }}</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; line-height: 1.4; color: #333; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #333; padding-bottom: 10px; }
        .header h1 { margin: 0; font-size: 18px; }
        .header p { margin: 5px 0 0; color: #666; }
        .info-grid { display: table; width: 100%; margin-bottom: 20px; }
        .info-row { display: table-row; }
        .info-label { display: table-cell; width: 30%; padding: 5px; background: #f5f5f5; font-weight: bold; border: 1px solid #ddd; }
        .info-value { display: table-cell; padding: 5px; border: 1px solid #ddd; }
        .status-section { margin-bottom: 20px; }
        .status-section h2 { font-size: 14px; background: #333; color: #fff; padding: 8px; margin: 0; }
        .progress-bar { background: #e0e0e0; height: 20px; margin: 10px 0; }
        .progress-fill { background: #4caf50; height: 20px; text-align: center; color: #fff; line-height: 20px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th { background: #f5f5f5; padding: 8px; text-align: left; border: 1px solid #ddd; font-weight: bold; }
        td { padding: 8px; border: 1px solid #ddd; }
        .status-uploaded { color: #4caf50; }
        .status-missing { color: #f44336; }
        .status-verified { color: #2196f3; }
        .footer { margin-top: 30px; padding-top: 10px; border-top: 1px solid #ddd; font-size: 10px; color: #666; }
        .badge { padding: 2px 6px; border-radius: 3px; font-size: 10px; }
        .badge-danger { background: #f44336; color: #fff; }
        .badge-success { background: #4caf50; color: #fff; }
        .badge-info { background: #2196f3; color: #fff; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Pre-Departure Documents Report</h1>
        <p>BTEVTA - Board of Technical Education & Vocational Training Authority</p>
    </div>

    <div class="info-grid">
        <div class="info-row">
            <div class="info-label">Candidate Name</div>
            <div class="info-value">{{ $candidate->name }}</div>
        </div>
        <div class="info-row">
            <div class="info-label">BTEVTA ID</div>
            <div class="info-value">{{ $candidate->btevta_id }}</div>
        </div>
        <div class="info-row">
            <div class="info-label">CNIC</div>
            <div class="info-value">{{ $candidate->cnic }}</div>
        </div>
        <div class="info-row">
            <div class="info-label">Campus</div>
            <div class="info-value">{{ $candidate->campus?->name ?? 'N/A' }}</div>
        </div>
        <div class="info-row">
            <div class="info-label">Trade</div>
            <div class="info-value">{{ $candidate->trade?->name ?? 'N/A' }}</div>
        </div>
        <div class="info-row">
            <div class="info-label">Current Status</div>
            <div class="info-value">{{ ucfirst(str_replace('_', ' ', $candidate->status)) }}</div>
        </div>
    </div>

    <div class="status-section">
        <h2>Document Completion Status</h2>
        <div class="progress-bar">
            <div class="progress-fill" style="width: {{ $status['completion_percentage'] }}%">
                {{ $status['completion_percentage'] }}%
            </div>
        </div>
        <p>
            <strong>Mandatory:</strong> {{ $status['mandatory_uploaded'] }} / {{ $status['mandatory_total'] }} uploaded
            &nbsp;|&nbsp;
            <strong>Optional:</strong> {{ $status['optional_uploaded'] }} / {{ $status['optional_total'] }} uploaded
        </p>
    </div>

    <div class="status-section">
        <h2>Mandatory Documents</h2>
        <table>
            <thead>
                <tr>
                    <th>Document</th>
                    <th>Status</th>
                    <th>Uploaded Date</th>
                    <th>Verified</th>
                </tr>
            </thead>
            <tbody>
                @foreach($mandatory as $checklist)
                    @php
                        $doc = $documents->firstWhere('document_checklist_id', $checklist->id);
                    @endphp
                    <tr>
                        <td>{{ $checklist->name }}</td>
                        <td>
                            @if($doc)
                                <span class="status-uploaded">✓ Uploaded</span>
                            @else
                                <span class="status-missing">✗ Missing</span>
                            @endif
                        </td>
                        <td>{{ $doc?->uploaded_at?->format('d M Y') ?? '-' }}</td>
                        <td>
                            @if($doc?->isVerified())
                                <span class="status-verified">✓ {{ $doc->verified_at->format('d M Y') }}</span>
                            @else
                                -
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="status-section">
        <h2>Optional Documents</h2>
        <table>
            <thead>
                <tr>
                    <th>Document</th>
                    <th>Status</th>
                    <th>Uploaded Date</th>
                    <th>Verified</th>
                </tr>
            </thead>
            <tbody>
                @foreach($optional as $checklist)
                    @php
                        $doc = $documents->firstWhere('document_checklist_id', $checklist->id);
                    @endphp
                    <tr>
                        <td>{{ $checklist->name }}</td>
                        <td>
                            @if($doc)
                                <span class="status-uploaded">✓ Uploaded</span>
                            @else
                                <span class="status-missing">— Not Uploaded</span>
                            @endif
                        </td>
                        <td>{{ $doc?->uploaded_at?->format('d M Y') ?? '-' }}</td>
                        <td>
                            @if($doc?->isVerified())
                                <span class="status-verified">✓ {{ $doc->verified_at->format('d M Y') }}</span>
                            @else
                                -
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="footer">
        <p>
            <strong>Generated:</strong> {{ $generated_at->format('d M Y, g:i A') }}
            &nbsp;|&nbsp;
            <strong>Generated By:</strong> {{ $generated_by->name }}
        </p>
        <p>This is a system-generated document from the WASL Pre-Departure Documents Module.</p>
    </div>
</body>
</html>
```

---

## Gap 8: Feature Tests for Controller
**File to create:** `tests/Feature/PreDepartureDocumentControllerTest.php`

```php
<?php

namespace Tests\Feature;

use App\Enums\CandidateStatus;
use App\Models\Candidate;
use App\Models\Campus;
use App\Models\DocumentChecklist;
use App\Models\PreDepartureDocument;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PreDepartureDocumentControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $campusAdmin;
    protected Campus $campus;
    protected Candidate $candidate;
    protected DocumentChecklist $checklist;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('private');

        $this->campus = Campus::factory()->create();

        $this->admin = User::factory()->create();
        $this->admin->assignRole('super_admin');

        $this->campusAdmin = User::factory()->create(['campus_id' => $this->campus->id]);
        $this->campusAdmin->assignRole('campus_admin');

        $this->candidate = Candidate::factory()->create([
            'campus_id' => $this->campus->id,
            'status' => 'listed',
        ]);

        $this->checklist = DocumentChecklist::create([
            'name' => 'Test Document',
            'code' => 'TEST_DOC',
            'description' => 'Test document description',
            'category' => 'mandatory',
            'is_mandatory' => true,
            'display_order' => 1,
            'is_active' => true,
        ]);
    }

    public function test_authenticated_user_can_view_candidate_documents(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('candidates.pre-departure-documents.index', $this->candidate));

        $response->assertStatus(200);
        $response->assertViewIs('candidates.pre-departure-documents.index');
        $response->assertViewHas('candidate');
        $response->assertViewHas('checklists');
    }

    public function test_can_upload_document_for_listed_candidate(): void
    {
        $file = UploadedFile::fake()->create('document.pdf', 1024, 'application/pdf');

        $response = $this->actingAs($this->admin)
            ->post(route('candidates.pre-departure-documents.store', $this->candidate), [
                'document_checklist_id' => $this->checklist->id,
                'file' => $file,
                'notes' => 'Test upload',
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('pre_departure_documents', [
            'candidate_id' => $this->candidate->id,
            'document_checklist_id' => $this->checklist->id,
        ]);
    }

    public function test_cannot_upload_document_for_screening_candidate(): void
    {
        $this->candidate->update(['status' => 'screening']);

        $file = UploadedFile::fake()->create('document.pdf', 1024, 'application/pdf');

        $response = $this->actingAs($this->campusAdmin)
            ->post(route('candidates.pre-departure-documents.store', $this->candidate), [
                'document_checklist_id' => $this->checklist->id,
                'file' => $file,
            ]);

        $response->assertForbidden();
    }

    public function test_campus_admin_cannot_view_other_campus_candidates(): void
    {
        $otherCampus = Campus::factory()->create();
        $otherCandidate = Candidate::factory()->create([
            'campus_id' => $otherCampus->id,
            'status' => 'listed',
        ]);

        $response = $this->actingAs($this->campusAdmin)
            ->get(route('candidates.pre-departure-documents.index', $otherCandidate));

        $response->assertForbidden();
    }

    public function test_admin_can_verify_document(): void
    {
        $document = PreDepartureDocument::factory()->create([
            'candidate_id' => $this->candidate->id,
            'document_checklist_id' => $this->checklist->id,
        ]);

        $response = $this->actingAs($this->admin)
            ->post(route('candidates.pre-departure-documents.verify', [$this->candidate, $document]), [
                'notes' => 'Verified successfully',
            ]);

        $response->assertRedirect();
        $this->assertNotNull($document->fresh()->verified_at);
    }

    public function test_admin_can_reject_document_with_reason(): void
    {
        $document = PreDepartureDocument::factory()->create([
            'candidate_id' => $this->candidate->id,
            'document_checklist_id' => $this->checklist->id,
        ]);

        $response = $this->actingAs($this->admin)
            ->post(route('candidates.pre-departure-documents.reject', [$this->candidate, $document]), [
                'reason' => 'Document is blurry and unreadable',
            ]);

        $response->assertRedirect();
    }

    public function test_can_download_uploaded_document(): void
    {
        Storage::disk('private')->put('test/document.pdf', 'fake content');

        $document = PreDepartureDocument::factory()->create([
            'candidate_id' => $this->candidate->id,
            'document_checklist_id' => $this->checklist->id,
            'file_path' => 'test/document.pdf',
            'original_filename' => 'document.pdf',
            'mime_type' => 'application/pdf',
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('candidates.pre-departure-documents.download', [$this->candidate, $document]));

        $response->assertStatus(200);
    }

    public function test_upload_fails_for_invalid_file_type(): void
    {
        $file = UploadedFile::fake()->create('document.exe', 1024);

        $response = $this->actingAs($this->admin)
            ->post(route('candidates.pre-departure-documents.store', $this->candidate), [
                'document_checklist_id' => $this->checklist->id,
                'file' => $file,
            ]);

        $response->assertSessionHasErrors('file');
    }
}
```

---

## Summary of All Gaps

| # | Gap | File(s) | Type | Priority |
|---|-----|---------|------|----------|
| 1 | Missing import | `PreDepartureReportController.php` | Bug fix | High |
| 2 | No navigation link | `candidates/show.blade.php` | UI | **Critical** |
| 3 | Wrong status check in policy | `PreDepartureDocumentPolicy.php` | Bug fix | **Critical** |
| 4 | Wrong status check in view | `pre-departure-documents/index.blade.php` | Bug fix | High |
| 5 | Missing seeder | `DocumentChecklistSeeder.php` | New file | **Critical** |
| 6 | Update DatabaseSeeder | `DatabaseSeeder.php` | Modify | High |
| 7 | Missing PDF view | `reports/pre-departure/individual-pdf.blade.php` | New file | Medium |
| 8 | Missing feature tests | `PreDepartureDocumentControllerTest.php` | New file | Medium |

---

## Implementation Order

1. **Gap 5** - Create DocumentChecklistSeeder (data needed first)
2. **Gap 6** - Update DatabaseSeeder
3. **Gap 3** - Fix policy status checks (unblocks functionality)
4. **Gap 4** - Fix view status checks
5. **Gap 1** - Fix controller import
6. **Gap 2** - Add navigation link (makes feature accessible)
7. **Gap 7** - Create PDF report view
8. **Gap 8** - Create feature tests

---

## Verification Checklist

After implementation, verify:

```bash
# 1. Seed database
php artisan db:seed --class=DocumentChecklistSeeder

# 2. Check for PHP errors
php artisan route:list | grep pre-departure

# 3. Access candidate with 'listed' status and verify:
#    - Pre-Departure Documents link appears in sidebar
#    - Page loads without errors
#    - Document upload works
#    - Verify/reject works

# 4. Run tests
php artisan test --filter=PreDepartureDocument
```

---

*Document Version: 2.0 | Updated: 2026-01-30*
*Changes: Added Gaps 2-4 for UI/workflow integration issues*
