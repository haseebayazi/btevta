# Correspondence Module

**WASL / BTEVTA** — Internal & External Communication Tracking

---

## Overview

The Correspondence module manages all written communication between BTEVTA, its campuses, Overseas Employment Promoters (OEPs), embassies, and other government bodies. Every letter, email, memo, and notice is recorded, reference-numbered, and tracked through to resolution.

---

## Database Schema

Table: `correspondences`

| Column | Type | Default | Description |
|--------|------|---------|-------------|
| `id` | bigint PK | | |
| `type` | string | `incoming` | Direction: `incoming` or `outgoing` |
| `file_reference_number` | string | auto | Auto-generated `COR-YYYYMM-NNNNN` |
| `organization_type` | string | null | `btevta` · `oep` · `embassy` · `campus` · `government` · `private` · `ngo` · `internal` · `other` |
| `subject` | string | | Short subject line |
| `sender` | string | null | Sending party name |
| `recipient` | string | null | Receiving party name |
| `message` | text | null | Main body text |
| `description` | text | null | Extended description / context |
| `notes` | text | null | Internal notes; also used for reply notes |
| `priority_level` | string | `normal` | `low` · `normal` · `high` · `urgent` |
| `status` | string | `pending` | `pending` · `in_progress` · `replied` · `closed` |
| `requires_reply` | boolean | false | Whether a reply is expected |
| `replied` | boolean | false | Whether a reply has been sent |
| `sent_at` | datetime | now() | Date sent/received (set automatically on create) |
| `replied_at` | datetime | null | Timestamp when marked as replied |
| `due_date` | date | null | Deadline for a reply |
| `attachment_path` | string | null | Path to attached file (private storage) |
| `campus_id` | FK → campuses | null | |
| `oep_id` | FK → oeps | null | |
| `candidate_id` | FK → candidates | null | |
| `assigned_to` | FK → users | null | User responsible for action |
| `created_by` | FK → users | null | Set automatically by boot hook |
| `updated_by` | FK → users | null | Updated automatically by boot hook |
| `created_at` / `updated_at` | timestamps | | |
| `deleted_at` | timestamp | null | Soft delete |

### Canonical Column Names

Several earlier code paths used inconsistent names. The canonical names used throughout this module are:

| Concept | Canonical Column | Old conflicting names (retired) |
|---------|-----------------|-------------------------------|
| Direction | `type` | `correspondence_type` |
| Reference | `file_reference_number` | `reference_number` |
| Priority | `priority_level` | `priority` |
| Body text | `message` | `content` |
| Date | `sent_at` | `date`, `date_received`, `date_sent`, `correspondence_date` |
| Reply date | `replied_at` | `response_date` |
| Deadline | `due_date` | `reply_deadline` |
| Reply notes | `notes` | `reply_notes` |

> **API note:** The REST API accepts the old names (`type`, `content`, `priority`, `reference_number`, `date_received`/`date_sent`, `response_date`) as input and maps them to canonical columns internally. The `CorrespondenceResource` outputs both the canonical value and the API-compatible alias so consumers are not broken.

---

## Model — `App\Models\Correspondence`

### Constants

```php
// Direction
Correspondence::TYPE_INCOMING  // 'incoming'
Correspondence::TYPE_OUTGOING  // 'outgoing'

// Medium / format (UI display only, no DB column)
Correspondence::MEDIUM_EMAIL
Correspondence::MEDIUM_LETTER
Correspondence::MEDIUM_MEMO
Correspondence::MEDIUM_NOTICE
Correspondence::MEDIUM_OTHER

// Priority
Correspondence::PRIORITY_LOW
Correspondence::PRIORITY_NORMAL
Correspondence::PRIORITY_HIGH
Correspondence::PRIORITY_URGENT

// Status
Correspondence::STATUS_PENDING
Correspondence::STATUS_IN_PROGRESS
Correspondence::STATUS_REPLIED
Correspondence::STATUS_CLOSED

// Organization type
Correspondence::ORG_BTEVTA
Correspondence::ORG_OEP
Correspondence::ORG_EMBASSY
Correspondence::ORG_CAMPUS
Correspondence::ORG_GOVERNMENT
Correspondence::ORG_PRIVATE
Correspondence::ORG_NGO
Correspondence::ORG_INTERNAL
Correspondence::ORG_OTHER
```

### Static Helpers

```php
Correspondence::getDirectionTypes()     // ['incoming' => 'Incoming', 'outgoing' => 'Outgoing']
Correspondence::getMediumTypes()        // ['email' => 'Email', ...]
Correspondence::getPriorities()         // ['low' => 'Low', ...]
Correspondence::getStatuses()           // ['pending' => 'Pending', ...]
Correspondence::getOrganizationTypes()  // ['btevta' => 'BTEVTA', ...]
Correspondence::generateFileReferenceNumber()  // 'COR-202604-00001'
```

### Query Scopes

| Scope | Description |
|-------|-------------|
| `pendingReply()` | `requires_reply = true` AND `replied = false` |
| `urgent()` | `priority_level = urgent` |
| `overdue()` | Requires reply, not replied, `due_date` is in the past |
| `ofType($type)` | Filter by `type` (e.g. `incoming`) |

### Relationships

| Method | Type | Description |
|--------|------|-------------|
| `campus()` | belongsTo Campus | Linked campus |
| `oep()` | belongsTo Oep | Linked OEP |
| `candidate()` | belongsTo Candidate | Linked candidate |
| `assignee()` | belongsTo User | Assigned to (via `assigned_to`) |
| `creator()` / `createdBy()` | belongsTo User | Created by (via `created_by`) |
| `updater()` | belongsTo User | Last updated by (via `updated_by`) |

### Boot Hooks

On **create**:
- `file_reference_number` is auto-generated if empty
- `sent_at` is set to `now()` if empty
- `created_by` is set to `auth()->id()` if authenticated

On **update**:
- `updated_by` is set to `auth()->id()` if authenticated

---

## Web Controller — `CorrespondenceController`

### Routes

```
GET    /correspondence                           → index()
GET    /correspondence/create                    → create()
POST   /correspondence                           → store()
GET    /correspondence/{id}                      → show()
GET    /correspondence/{id}/edit                 → edit()
PUT    /correspondence/{id}                      → update()
DELETE /correspondence/{id}                      → destroy()
GET    /correspondence/pending-reply             → pendingReply()
POST   /correspondence/{id}/mark-replied         → markReplied()
GET    /correspondence/register                  → register()
GET    /correspondence/summary                   → summary()
GET    /correspondence/search                    → search()
GET    /correspondence/pendency-report           → pendencyReport()
```

Middleware: `role:admin,campus_admin,viewer`

### Method Summary

| Method | Description |
|--------|-------------|
| `index()` | Paginated list with `type`, `organization_type`, and `status` filters. Scoped by role. |
| `create()` | Form with dropdowns for campus, OEP, types, priorities. |
| `store()` | Validates and persists. File uploaded to `private` disk as `attachment_path`. |
| `show()` | Detail view; loads relations including `assignee`. |
| `edit()` | Edit form; requires `update` policy. |
| `update()` | Updates correspondence fields, logs activity. |
| `destroy()` | Soft delete; super admin only (policy). |
| `pendingReply()` | Lists correspondence with `requires_reply = true` and `replied = false`. |
| `markReplied()` | Sets `replied = true`, `replied_at = now()`, `status = replied`, updates `notes`. |
| `register()` | Full register view, 50 records per page, printable. |
| `summary()` | Aggregated report: totals by type, by organisation, monthly trend (12 months), pending/overdue counts, avg response time. Filterable by date range, org type, campus. |
| `search()` | Full-text search across `subject`, `file_reference_number`, `sender`, `recipient`, `message`, `notes`, `description`. |
| `pendencyReport()` | Analytics dashboard: status breakdown, overdue list with severity, monthly trend, on-time response rate, campus/OEP breakdown (admins). |

### Campus / OEP Scoping

All methods call `applyUserScope()` which constrains queries:
- **Super admin / Project Director / Viewer** — see everything
- **Campus admin** — restricted to `campus_id = user.campus_id`
- **OEP user** — restricted to `oep_id = user.oep_id`

---

## API Controller — `CorrespondenceApiController`

Base URL: `/api/v1/correspondence`
Authentication: Laravel Sanctum (`auth:sanctum` middleware)

### Endpoints

| Method | URI | Action |
|--------|-----|--------|
| GET | `/api/v1/correspondence` | List with filters & pagination |
| GET | `/api/v1/correspondence/{id}` | Show single record |
| POST | `/api/v1/correspondence` | Create |
| PUT | `/api/v1/correspondence/{id}` | Update (partial) |
| DELETE | `/api/v1/correspondence/{id}` | Soft delete |
| GET | `/api/v1/correspondence/stats` | Statistics summary |
| GET | `/api/v1/correspondence/pending` | Pending correspondence |

### Request: Create (POST)

```json
{
    "organization_type": "government",   // required
    "type": "incoming",                  // required: incoming|outgoing
    "subject": "Letter regarding...",    // required
    "sender": "Ministry of Labour",      // required
    "recipient": "BTEVTA HQ",            // required
    "content": "Body text here",         // required → stored as `message`
    "date_received": "2026-04-01",       // required if type=incoming → stored as `sent_at`
    "date_sent": "2026-04-01",           // required if type=outgoing → stored as `sent_at`
    "reference_number": "ML/2026/001",   // optional → stored as `file_reference_number`
    "priority": "normal",                // optional → stored as `priority_level`
    "campus_id": 1,
    "oep_id": null,
    "status": "pending",
    "due_date": "2026-04-15",
    "notes": "Follow up required"
}
```

### Request: Update (PUT)

All fields are optional (`sometimes`). Additional field:
```json
{
    "response_date": "2026-04-10"   // → stored as `replied_at`
}
```

### Response Structure

```json
{
    "success": true,
    "data": {
        "id": 1,
        "reference_number": "COR-202604-00001",
        "type": "incoming",
        "content": "Body text",
        "priority": "normal",
        "subject": "Letter regarding...",
        "organization_type": "government",
        "sender": "Ministry of Labour",
        "recipient": "BTEVTA HQ",
        "status": "pending",
        "description": null,
        "notes": null,
        "due_date": "2026-04-15",
        "date_received": "2026-04-01",
        "date_sent": null,
        "response_date": null,
        "requires_reply": false,
        "replied": false,
        "campus": { "id": 1, "name": "Lahore" },
        "oep": null,
        "creator": { "id": 1, "name": "System Administrator" },
        "created_at": "2026-04-07 10:00:00",
        "updated_at": "2026-04-07 10:00:00"
    }
}
```

### Query Filters (GET /api/v1/correspondence)

| Parameter | Description |
|-----------|-------------|
| `status` | Filter by status |
| `type` | Filter by direction (`incoming`/`outgoing`) |
| `organization_type` | Filter by org type |
| `campus_id` | Filter by campus |
| `oep_id` | Filter by OEP |
| `from_date` | `created_at >=` |
| `to_date` | `created_at <=` |
| `search` | Full-text search (subject, reference, sender, recipient, message) |
| `per_page` | Results per page (default 20) |

---

## Authorization — `CorrespondencePolicy`

| Ability | Who |
|---------|-----|
| `viewAny` | Super admin, Project Director, Campus admin, OEP, Viewer |
| `view` | Admins (all); Campus admin (own campus); OEP (own OEP) |
| `create` | Super admin, Project Director, Campus admin |
| `update` | Admins (all); Campus admin (own campus) |
| `delete` | Super admin only |
| `markReplied` | Same as `update` |

---

## Views

| View | Route | Description |
|------|-------|-------------|
| `correspondence/index` | `/correspondence` | List with badge-based status UI |
| `correspondence/create` | `/correspondence/create` | Full creation form |
| `correspondence/show` | `/correspondence/{id}` | Detail + mark-replied modal |
| `correspondence/edit` | `/correspondence/{id}/edit` | Edit form |
| `correspondence/pending-reply` | `/correspondence/pending-reply` | Overdue indicators |
| `correspondence/register` | `/correspondence/register` | Printable full register |
| `correspondence/reports/summary` | `/correspondence/summary` | Charts & monthly trend |
| `correspondence/search-results` | `/correspondence/search` | Highlighted search results |
| `correspondence/pendency-report` | `/correspondence/pendency-report` | Analytics dashboard |
| `dashboard/tabs/correspondence` | `/dashboard/correspondence` | Dashboard widget |

---

## Workflow

```
Record correspondence
        │
        ├─ type: incoming ──→ Assign to handler ──→ Mark replied
        │                                               │
        │                                       (sets replied=true,
        │                                        replied_at=now(),
        │                                        status=replied,
        │                                        notes updated)
        │
        └─ type: outgoing ──→ Archive / Close
                                    │
                              status = closed
```

**Overdue detection:** Any correspondence with `requires_reply = true`, `replied = false`, and `due_date < now()` is flagged as overdue. The pendency report categorises these by severity:
- **Moderate** — 1–7 days past due
- **High** — 8–14 days past due
- **Critical** — 15+ days past due

---

## File Attachments

Attachments are stored in the `private` disk (not publicly accessible):

```
storage/app/private/correspondence/{filename}
```

Access must go through `SecureFileController`. The path is stored in `attachment_path`.

---

## Factory States

```php
Correspondence::factory()->create();              // default
Correspondence::factory()->incoming()->create();
Correspondence::factory()->outgoing()->create();
Correspondence::factory()->requiresReply()->create();
Correspondence::factory()->replied()->create();
Correspondence::factory()->overdue()->create();
Correspondence::factory()->urgent()->create();
Correspondence::factory()->forCampus()->create();
Correspondence::factory()->forOep()->create();
Correspondence::factory()->forCandidate()->create();
Correspondence::factory()->closed()->create();
```

---

## Migrations

| File | Purpose |
|------|---------|
| `2025_10_31_165531_create_correspondences_table.php` | Initial schema |
| `2025_12_27_000001_add_soft_deletes_to_correspondence_table.php` | Adds `deleted_at` |
| `2026_04_07_000001_add_missing_columns_to_correspondences_table.php` | Adds `type`, `file_reference_number`, `organization_type`, `sender`, `recipient`, `priority_level`, `description`, `notes`, `due_date`, `assigned_to`, `created_by`, `updated_by` |

---

## Known Fixes Applied (2026-04-07)

| Issue | Fix |
|-------|-----|
| `correspondence_type` column missing | Added `type` column (canonical name) |
| Soft delete migration used wrong table name `correspondence` | Fixed to `correspondences` |
| `CorrespondenceController::edit()` missing `Candidate` import | Added `use App\Models\Candidate` |
| `CorrespondenceController::edit()` missing `authorize()` | Added `$this->authorize('update', $correspondence)` |
| `store()` stored file to `file_path` | Fixed to `attachment_path` |
| `markReplied()` saved to non-existent `reply_notes` | Fixed to save to `notes` |
| `summary()` / `pendencyReport()` used non-existent `date`, `response_date` columns | Fixed to `sent_at`, `replied_at` |
| `search()` searched non-existent `reference_number`, `content` | Fixed to `file_reference_number`, `message` |
| `update()` used `correspondence_date`, `correspondence_type` | Fixed to `sent_at`, `type` |
| `CorrespondenceResource` mapped to 8 non-existent columns | Rewritten to map canonical columns with API aliases |
| Model boot hooks disabled | Re-enabled for `file_reference_number`, `created_by`, `updated_by` |
| Factory missing all new columns | Added `type`, `file_reference_number`, `organization_type`, `sender`, `recipient`, `priority_level`, `description`, `notes`, `due_date` |
| `DashboardController` used `correspondence_type` | Fixed to `type` |
| `applyUserScope` repeated 7× across controllers | Extracted to private helper in each controller |
