# Sonnet Prompt: Complete Module 1 Pre-Departure Documents (v2)

## CRITICAL: READ FIRST

**Module 1 backend is 90% implemented, but it's UNUSABLE because:**
1. No navigation link to access it
2. Policy blocks editing for the wrong statuses
3. No seeder data exists

Your job is to fix **8 specific gaps** - NOT recreate existing code.

---

## PROMPT TO COPY FOR SONNET

```
Complete Module 1 (Pre-Departure Documents) for BTEVTA WASL by fixing the 8 gaps below.

IMPORTANT: The backend is ALREADY IMPLEMENTED. Do NOT recreate these files:
- app/Models/PreDepartureDocument.php ✓
- app/Models/DocumentChecklist.php ✓
- app/Services/PreDepartureDocumentService.php ✓ (584 lines)
- app/Http/Controllers/PreDepartureDocumentController.php ✓
- routes/web.php (routes exist at lines 161-190) ✓
- resources/views/candidates/pre-departure-documents/ (4 blade files) ✓

## YOUR 8 TASKS (IN THIS ORDER)

### TASK 1: Create DocumentChecklistSeeder
Run: `php artisan make:seeder DocumentChecklistSeeder`

Create file `database/seeders/DocumentChecklistSeeder.php` with 8 checklist items:
- 5 Mandatory: CNIC, PASSPORT, DOMICILE, FRC, PCC
- 3 Optional: PRE_MEDICAL, CERTIFICATIONS, RESUME

Use `updateOrCreate` with 'code' as the unique key.

**Verification:** `php artisan db:seed --class=DocumentChecklistSeeder`

---

### TASK 2: Update DatabaseSeeder
File: `database/seeders/DatabaseSeeder.php`

Add inside run() method:
```php
$this->call(DocumentChecklistSeeder::class);
```

---

### TASK 3: Fix Policy Status Checks (CRITICAL)
File: `app/Policies/PreDepartureDocumentPolicy.php`

The policy currently checks `$candidate->status !== 'new'` but the workflow uses:
- `listed` (order 1) - where candidates start
- `pre_departure_docs` (order 2) - during document collection
- `new` (order 0) - legacy status

Find and replace in THREE places (create, update, delete methods):

FROM:
```php
if ($candidate->status !== 'new') {
    return false;
}
```

TO:
```php
$editableStatuses = ['new', 'listed', 'pre_departure_docs'];
if (!in_array($candidate->status, $editableStatuses)) {
    return false;
}
```

Apply to:
- `create()` method (around line 53)
- `update()` method (around line 83)
- `delete()` method (around line 118)

**Verification:** Create a candidate with status='listed', try to upload a document - should work now.

---

### TASK 4: Fix View Status Checks
File: `resources/views/candidates/pre-departure-documents/index.blade.php`

Line 42 - Change:
```blade
<span class="badge badge-{{ $candidate->status === 'new' ? 'primary' : 'secondary' }}">
```
To:
```blade
<span class="badge badge-{{ in_array($candidate->status, ['new', 'listed', 'pre_departure_docs']) ? 'primary' : 'secondary' }}">
```

Line 75 - Change:
```blade
@if($candidate->status !== 'new')
```
To:
```blade
@if(!in_array($candidate->status, ['new', 'listed', 'pre_departure_docs']))
```

---

### TASK 5: Fix Missing Import
File: `app/Http/Controllers/PreDepartureReportController.php`

Add after line 6 (after the Storage import):
```php
use App\Models\PreDepartureDocument;
```

**Verification:** `php artisan route:list | grep pre-departure` runs without errors.

---

### TASK 6: Add Navigation Link (CRITICAL)
File: `resources/views/candidates/show.blade.php`

Add a Pre-Departure Documents card in the sidebar BEFORE the "Action Card" (before line 186).

Insert this code:
```blade
{{-- Pre-Departure Documents Card --}}
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

**Verification:** View a candidate with status='listed' - should see "Pre-Departure Documents" card with progress bar.

---

### TASK 7: Create PDF Report View
Create directory and file: `resources/views/reports/pre-departure/individual-pdf.blade.php`

First run: `mkdir -p resources/views/reports/pre-departure`

The view receives these variables from PreDepartureDocumentService:
- $candidate (Candidate model)
- $documents (Collection)
- $status (array with completion stats)
- $mandatory (Collection of mandatory checklists)
- $optional (Collection of optional checklists)
- $generated_at (Carbon)
- $generated_by (User)

Create a professional PDF-ready HTML template with inline CSS (no Tailwind) showing:
1. Header with BTEVTA branding
2. Candidate info (name, BTEVTA ID, CNIC, campus, trade)
3. Completion progress bar
4. Mandatory documents table (Document, Status, Uploaded Date, Verified)
5. Optional documents table
6. Footer with generation timestamp

---

### TASK 8: Create Feature Tests
File: `tests/Feature/PreDepartureDocumentControllerTest.php`

Write 8 test methods:
1. `test_authenticated_user_can_view_candidate_documents()`
2. `test_can_upload_document_for_listed_candidate()`
3. `test_cannot_upload_document_for_screening_candidate()`
4. `test_campus_admin_cannot_view_other_campus_candidates()`
5. `test_admin_can_verify_document()`
6. `test_admin_can_reject_document_with_reason()`
7. `test_can_download_uploaded_document()`
8. `test_upload_fails_for_invalid_file_type()`

Use `RefreshDatabase` trait. Setup creates admin, campusAdmin, campus, candidate (status='listed'), and a DocumentChecklist item.

---

## VERIFICATION AFTER ALL TASKS

```bash
# 1. Seed the database
php artisan db:seed --class=DocumentChecklistSeeder

# 2. Check routes work
php artisan route:list | grep pre-departure

# 3. Run existing tests (should still pass)
php artisan test tests/Unit/CandidatePreDepartureTest.php

# 4. Run your new tests
php artisan test tests/Feature/PreDepartureDocumentControllerTest.php

# 5. Manual verification:
# - Go to a candidate with status='listed'
# - Verify "Pre-Departure Documents" card appears
# - Click it, verify page loads with 8 document cards
# - Try uploading a document
```

## DO NOT:
- Create new models (they exist)
- Create new services (complete at 584 lines)
- Create new controllers (just fix the import)
- Add new routes (already registered)
- Modify CandidateStatus enum (statuses already defined)

## COMMIT AFTER EACH TASK:
```bash
git add <files>
git commit -m "Module 1: <task description>

https://claude.ai/code/session_ID"
```
```

---

## Why Previous Attempts Failed

| Problem | Result |
|---------|--------|
| Plans ignored existing code | Sonnet tried to recreate 600+ lines that existed |
| No navigation link identified | Feature was inaccessible without direct URL |
| Status checks used wrong value | Policy blocked all document operations |
| No verification steps | No way to confirm success |
| Tasks were not atomic | Couldn't be completed incrementally |

---

## Expected Deliverables

| Task | Files | Lines Changed/Added |
|------|-------|---------------------|
| 1. Seeder | `DocumentChecklistSeeder.php` | ~60 new |
| 2. DatabaseSeeder | `DatabaseSeeder.php` | 1 line |
| 3. Policy fix | `PreDepartureDocumentPolicy.php` | 6 lines changed |
| 4. View fix | `index.blade.php` | 2 lines changed |
| 5. Import fix | `PreDepartureReportController.php` | 1 line |
| 6. Navigation | `candidates/show.blade.php` | ~25 new |
| 7. PDF view | `individual-pdf.blade.php` | ~150 new |
| 8. Tests | `PreDepartureDocumentControllerTest.php` | ~180 new |
| **TOTAL** | | ~425 lines |

---

## Quick Reference: File Locations

```
MODIFY:
├── app/Http/Controllers/PreDepartureReportController.php (add import)
├── app/Policies/PreDepartureDocumentPolicy.php (fix 3 status checks)
├── database/seeders/DatabaseSeeder.php (add seeder call)
├── resources/views/candidates/show.blade.php (add nav link)
└── resources/views/candidates/pre-departure-documents/index.blade.php (fix 2 status checks)

CREATE:
├── database/seeders/DocumentChecklistSeeder.php
├── resources/views/reports/pre-departure/individual-pdf.blade.php
└── tests/Feature/PreDepartureDocumentControllerTest.php
```

---

*Document Version: 2.0 | Updated: 2026-01-30*
*Includes UI/workflow integration gaps discovered during review*
