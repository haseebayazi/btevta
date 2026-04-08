# Module 9: Post-Correspondence — Features & Workflows

**Project:** WASL (BTEVTA)
**Module:** 9 — Post-Correspondence Enhancement
**Version:** 2.0 (Enhanced)
**Status:** Production Ready
**Date:** April 2026

---

## Overview

Module 9 covers **Post-Correspondence** for candidates after overseas deployment. It comprises two sub-modules:

| Sub-Module | Description | Status |
|------------|-------------|--------|
| **9A** | Success Stories | Enhanced ✅ |
| **9B** | Complaints & SLA | Enhanced ✅ |

---

## Module 9A: Success Stories

### Purpose
Document, review, approve, and publish success stories of candidates who achieved overseas employment through BTEVTA. Stories are structured, multi-typed, and support rich evidence attachments.

### Key Features

| Feature | Description |
|---------|-------------|
| **Story Types** | Employment, Career Growth, Skill Achievement, Remittance Impact, Other |
| **Approval Workflow** | Draft → Pending Review → Approved → Published (or Rejected) |
| **Employment Tracking** | Employer name, position, destination country, salary |
| **Evidence Gallery** | Multiple evidence files per story with typed categories |
| **Public Gallery** | Published stories viewable by the public without login |
| **Featured Stories** | Flag stories for promotion on the public gallery |
| **View Counter** | Track how many times each story has been viewed |
| **Salary Tracking** | Record salary achieved with multi-currency support |
| **Time Metrics** | Days from training completion to first employment |

---

### Story Types (StoryType Enum)

| Value | Label | Icon |
|-------|-------|------|
| `employment` | Employment Success | `fas fa-briefcase` |
| `career_growth` | Career Growth | `fas fa-chart-line` |
| `skill_achievement` | Skill Achievement | `fas fa-award` |
| `remittance` | Remittance Impact | `fas fa-money-bill-wave` |
| `other` | Other Success | `fas fa-star` |

---

### Approval Workflow (StoryStatus Enum)

```
DRAFT
  │
  ▼ (submitForReview)
PENDING_REVIEW
  │
  ├─── (approve) ──▶ APPROVED
  │                       │
  │                       ▼ (publish)
  │                   PUBLISHED
  │
  └─── (reject) ──▶ REJECTED
                        │
                        ▼ (submitForReview)
                    PENDING_REVIEW
```

| Status | Color | Can Transition To |
|--------|-------|-------------------|
| `draft` | Secondary | `pending_review` |
| `pending_review` | Warning | `approved`, `rejected` |
| `approved` | Info | `published`, `rejected` |
| `published` | Success | — (terminal) |
| `rejected` | Danger | `pending_review` |

---

### Evidence Types (StoryEvidenceType Enum)

| Type | Label | Allowed Formats | Max Size |
|------|-------|----------------|----------|
| `photo` | Photograph | jpg, jpeg, png, webp | 10 MB |
| `video` | Video | mp4, mov, avi, webm | 100 MB |
| `document` | Document | pdf, doc, docx | 10 MB |
| `interview` | Interview Recording | mp3, wav, mp4, mov | 100 MB |
| `testimonial` | Written Testimonial | pdf, doc, docx, txt | 10 MB |
| `certificate` | Certificate/Award | pdf, jpg, jpeg, png | 10 MB |

---

### Database Schema

#### `success_stories` table (enhanced)

| Column | Type | Description |
|--------|------|-------------|
| `story_type` | enum | Story category |
| `headline` | string(200) | Short impactful headline |
| `employer_name` | string(200) | Overseas employer |
| `position_achieved` | string(100) | Job title/role |
| `country_id` | FK → countries | Destination country |
| `salary_achieved` | decimal(12,2) | Salary earned |
| `salary_currency` | string(10) | Currency code (default: SAR) |
| `employment_start_date` | date | When employment started |
| `time_to_employment_days` | integer | Days from deployment to employment |
| `views_count` | integer | Public view counter |
| `published_at` | timestamp | When story was published |
| `status` | enum | draft/pending_review/approved/published/rejected |
| `approved_by` | FK → users | Who approved |
| `approved_at` | timestamp | When approved |
| `rejection_reason` | text | Why it was rejected |

#### `success_story_evidence` table (new)

| Column | Type | Description |
|--------|------|-------------|
| `success_story_id` | FK → success_stories | Parent story |
| `evidence_type` | enum | photo/video/document/interview/testimonial/certificate |
| `title` | string(200) | Evidence title |
| `description` | text | Optional description |
| `file_path` | string(500) | Private storage path |
| `mime_type` | string | File MIME type |
| `file_size` | integer | File size in bytes |
| `is_primary` | boolean | Primary evidence shown in listings |
| `display_order` | integer | Sort order |
| `uploaded_by` | FK → users | Uploader |

---

### Routes

| Method | URI | Name | Description |
|--------|-----|------|-------------|
| GET | `/admin/success-stories` | `admin.success-stories.index` | List all stories |
| GET | `/admin/success-stories/create` | `admin.success-stories.create` | Create form |
| POST | `/admin/success-stories` | `admin.success-stories.store` | Store story |
| GET | `/admin/success-stories/{id}` | `admin.success-stories.show` | View story |
| GET | `/admin/success-stories/{id}/edit` | `admin.success-stories.edit` | Edit form |
| PUT | `/admin/success-stories/{id}` | `admin.success-stories.update` | Update story |
| DELETE | `/admin/success-stories/{id}` | `admin.success-stories.destroy` | Delete story |
| POST | `/admin/success-stories/{id}/submit-review` | `admin.success-stories.submit-review` | Submit for review |
| POST | `/admin/success-stories/{id}/approve` | `admin.success-stories.approve` | Approve story |
| POST | `/admin/success-stories/{id}/publish` | `admin.success-stories.publish` | Publish story |
| POST | `/admin/success-stories/{id}/reject` | `admin.success-stories.reject` | Reject story |
| POST | `/admin/success-stories/{id}/evidence` | `admin.success-stories.add-evidence` | Upload evidence |
| DELETE | `/admin/success-stories/{id}/evidence/{eid}` | `admin.success-stories.delete-evidence` | Delete evidence |
| POST | `/admin/success-stories/{id}/toggle-featured` | `admin.success-stories.toggle-featured` | Toggle featured |
| GET | `/admin/success-stories/{id}/download-evidence` | `admin.success-stories.download-evidence` | Download legacy evidence |
| GET | `/stories/gallery` | `success-stories.public` | **Public** gallery (no auth) |

---

### Views

| View | Description |
|------|-------------|
| `admin/success-stories/index` | Story list with filters, stats, type badges |
| `admin/success-stories/create` | Enhanced create form with employment & salary fields |
| `admin/success-stories/show` | Detail view with workflow actions, evidence gallery |
| `admin/success-stories/edit` | Edit form |
| `admin/success-stories/partials/evidence-upload` | Evidence upload form partial |
| `admin/success-stories/partials/evidence-gallery` | Evidence grid partial |
| `success-stories/public-gallery` | Public-facing gallery (standalone HTML) |

---

## Module 9B: Complaints Enhancement

### Purpose
Enhance the existing comprehensive complaints system with evidence categorization, complaint templates for faster filing, evidence verification workflow, and an analytics-enriched dashboard.

### New Features

| Feature | Description |
|---------|-------------|
| **Evidence Categorization** | 7 categories for structured evidence |
| **Confidential Evidence** | Flag sensitive evidence as confidential |
| **Evidence Verification** | Admin can mark evidence as verified |
| **Complaint Templates** | Pre-configured templates for common complaint types |
| **Template-based Filing** | Create complaints directly from templates |
| **Enhanced Dashboard** | Metrics, trends, resolution times, evidence breakdown |
| **Category Trends** | 6-month complaint trend by category |

---

### Evidence Categories (ComplaintEvidenceCategory Enum)

| Value | Label | Description |
|-------|-------|-------------|
| `initial_report` | Initial Report | Original complaint report |
| `supporting_document` | Supporting Document | Contracts, letters, etc. |
| `photo_video` | Photo/Video Evidence | Visual evidence |
| `witness_statement` | Witness Statement | Third-party statements |
| `communication_record` | Communication Record | Emails, messages, recordings |
| `resolution_proof` | Resolution Proof | Evidence the issue was resolved |
| `other` | Other | Any other relevant evidence |

---

### Complaint Templates

Pre-seeded templates accelerate complaint filing:

| Template | Category | Priority | SLA |
|----------|----------|----------|-----|
| Salary Dispute | salary | High | 48h |
| Workplace Safety Issue | facility | Urgent | 24h |
| Document Issue | document | Normal | 72h |
| Harassment Report | conduct | Urgent | 24h |
| Accommodation Complaint | accommodation | High | 48h |
| Training Issue | training | Normal | 72h |

---

### Database Schema

#### `complaint_evidence` table (enhanced)

| Column Added | Type | Description |
|-------------|------|-------------|
| `evidence_category` | enum | Evidence classification |
| `is_confidential` | boolean | Hidden from non-admins |
| `verified` | boolean | Evidence has been verified |
| `verified_by` | FK → users | Who verified |
| `verified_at` | timestamp | When verified |

#### `complaint_templates` table (new)

| Column | Type | Description |
|--------|------|-------------|
| `name` | string(100) | Template name |
| `category` | string(50) | Complaint category |
| `description_template` | text | Pre-filled description with placeholders |
| `required_evidence_types` | json | List of required evidence |
| `suggested_actions` | json | Suggested resolution actions |
| `default_priority` | enum | Default priority level |
| `suggested_sla_hours` | integer | Recommended SLA |
| `is_active` | boolean | Whether template is available |

---

### Routes (New)

| Method | URI | Name | Description |
|--------|-----|------|-------------|
| GET | `/complaints/templates` | `complaints.templates` | Browse templates |
| GET | `/complaints/enhanced-dashboard` | `complaints.enhanced-dashboard` | Enhanced analytics dashboard |
| POST | `/complaints/from-template/{template}` | `complaints.from-template` | File complaint from template |
| POST | `/complaints/{id}/evidence/categorized` | `complaints.add-categorized-evidence` | Add categorized evidence |
| POST | `/complaints/evidence/{id}/verify` | `complaints.verify-evidence` | Verify evidence |

---

### Views (New)

| View | Description |
|------|-------------|
| `complaints/enhanced-dashboard` | KPIs, evidence breakdown, resolution times, trends, templates |
| `complaints/templates` | Template browser with modal to use template |
| `complaints/partials/categorized-evidence-form` | Reusable evidence upload with category selection |

---

### Service Methods Added (ComplaintService)

```php
// Get enhanced dashboard metrics
getEnhancedDashboard(?int $campusId): array

// Create complaint from a template
createFromTemplate(
    ComplaintTemplate $template,
    Candidate $candidate,
    string $description,
    array $additionalData = []
): Complaint

// Add categorized evidence with confidentiality flag
addCategorizedEvidence(
    Complaint $complaint,
    UploadedFile $file,
    string $category,
    bool $isConfidential = false,
    ?string $description = null
): ComplaintEvidence
```

---

## Sidebar Navigation

Both features are accessible from the main sidebar:

- **Complaints** — `dashboard.complaints` (existing)
- **Success Stories** — `admin.success-stories.index` (new, Module 9A)

The sidebar highlights the active section using Laravel's `request()->routeIs()`.

---

## Authorization

All routes use Laravel Policies:

| Action | Policy Check |
|--------|-------------|
| View stories | `SuccessStoryPolicy::viewAny()` |
| Create/edit stories | `SuccessStoryPolicy::create()` / `update()` |
| Approve/publish | `SuccessStoryPolicy::update()` |
| View complaints | `ComplaintPolicy::viewAny()` |
| Manage templates | `ComplaintPolicy::viewAny()` |
| Verify evidence | `ComplaintPolicy::update()` |

Role access: **Admin**, **Campus Admin**, **Viewer** (read-only for viewer)

---

## Testing

| Test File | Tests | Coverage |
|-----------|-------|---------|
| `tests/Feature/SuccessStoryControllerTest.php` | 21 tests | CRUD, workflow, evidence, gallery |
| `tests/Feature/Module9EnhancementsTest.php` | 23 tests | Enums, models, templates, dashboard |

Run Module 9 tests:
```bash
php artisan test tests/Feature/SuccessStoryControllerTest.php tests/Feature/Module9EnhancementsTest.php
```

---

## Seeding

Seed complaint templates:
```bash
php artisan db:seed --class=ComplaintTemplatesSeeder
```

Or as part of full seeding:
```bash
php artisan migrate:fresh --seed
```

---

## Data Sync with Other Modules

| Module | Sync Point |
|--------|-----------|
| **Module 6 (Departure)** | Stories link to `departure_id` to tie success to deployment |
| **Module 5 (Visa)** | Country info synced via `country_id` FK |
| **Module 2 (Screening)** | Candidate campus_id used for campus-scoped access |
| **Remittance** | `remittance` story type links to financial impact tracking |
| **Complaints** | Evidence linking to complaint via `complaint_id` |

---

## File Storage

All files stored securely in `storage/app/private/`:

```
private/
├── success-stories/
│   ├── {candidate_id}/         ← Legacy single evidence
│   └── {story_id}/evidence/    ← New evidence gallery files
└── complaints/
    └── {complaint_id}/evidence/ ← Categorized evidence
```

Access through `SecureFileController` only — never exposed directly.

---

*Last Updated: April 2026 | Module 9 Enhancement v2.0*
