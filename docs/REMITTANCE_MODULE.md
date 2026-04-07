# Remittance Module (Module 10)

**Purpose:** Track and manage money transfers (remittances) sent by overseas workers back to Pakistan. Provides end-to-end lifecycle management from recording a transfer through proof verification and analytics reporting.

---

## Features

| Feature | Description |
|---------|-------------|
| Multi-currency support | PKR, USD, SAR, AED, EUR, GBP, QAR, KWD, OMR, BHD |
| Proof document management | Upload bank receipts, transfer slips, mobile screenshots |
| Verification workflow | Pending → Verified / Rejected / Under Review |
| Beneficiary management | Per-candidate beneficiary registry with bank details |
| Statistics & analytics | Real-time stats on index page; full reports dashboard |
| Alert system | Auto-generated alerts for anomalies (90-day gap, large amounts) |
| Usage breakdown | Track how remittances are actually spent |
| CSV export | Downloadable export from the index page |
| Role-based access | Campus-scoped filtering; viewers read-only |
| Activity logging | Full Spatie audit trail on all changes |

---

## Workflow

```
Worker departs overseas
        │
        ▼
Worker sends money home
        │
        ▼
Staff records Remittance
  ├─ Candidate selected (must have a Departure record)
  ├─ Transfer date, method, amount, currency entered
  ├─ Sender & receiver details filled
  ├─ Primary purpose selected (education, health, rent, etc.)
  └─ Optional: proof document uploaded at creation
        │
        ▼
Remittance status: pending
        │
        ├─── Upload additional proof receipts (show page)
        │
        ├─── Flag for review (manual or via alert)
        │
        ▼
Verifier reviews (admin / campus_admin)
        │
        ├── Verify → status: completed, verification_status: verified
        └── Reject → status: flagged,   verification_status: rejected
```

---

## Module Components

### Models

| Model | Table | Purpose |
|-------|-------|---------|
| `Remittance` | `remittances` | Core transfer record |
| `RemittanceReceipt` | `remittance_receipts` | Proof documents (multiple per remittance) |
| `RemittanceBeneficiary` | `remittance_beneficiaries` | Candidate's family/receiver registry |
| `RemittanceUsageBreakdown` | `remittance_usage_breakdown` | How funds were spent (optional) |
| `RemittanceAlert` | `remittance_alerts` | System-generated anomaly alerts |

### Key `Remittance` Fields

| Field | Type | Notes |
|-------|------|-------|
| `candidate_id` | FK | Must have a Departure record |
| `campus_id` | FK | Auto-populated from candidate |
| `transfer_date` | date | Date worker sent the money |
| `amount` | decimal | Amount in the stated currency |
| `currency` | string | PKR, SAR, AED, etc. |
| `amount_in_pkr` | decimal | Calculated if exchange rate provided |
| `exchange_rate` | decimal | 1 foreign = X PKR |
| `transfer_method` | string | bank_transfer, money_exchange, mobile_wallet, etc. |
| `primary_purpose` | enum | education, health, rent, food, savings, etc. |
| `sender_name` | string | Worker's name (auto-filled from candidate) |
| `receiver_name` | string | Beneficiary name |
| `verification_status` | enum | pending, verified, rejected, under_review |
| `status` | enum | pending, verified, flagged, completed |
| `has_proof` | boolean | True if any receipt uploaded |
| `proof_document_path` | string | Path of primary proof (public disk) |

### Status Values

**`status` (legacy display status):**
| Value | Label | Meaning |
|-------|-------|---------|
| `pending` | Pending Verification | Newly recorded |
| `verified` | Verified | Legacy verified state |
| `flagged` | Flagged for Review | Rejected or flagged |
| `completed` | Completed | Fully verified & complete |

**`verification_status` (workflow state):**
| Value | Meaning |
|-------|---------|
| `pending` | Awaiting review |
| `verified` | Approved by verifier |
| `rejected` | Rejected with reason |
| `under_review` | In active review |

---

## Controllers & Routes

### `RemittanceController`

| Method | Route | Name | Purpose |
|--------|-------|------|---------|
| `index` | GET `/remittances` | `remittances.index` | List with stats & filters |
| `create` | GET `/remittances/create` | `remittances.create` | Record form |
| `store` | POST `/remittances` | `remittances.store` | Save new record |
| `show` | GET `/remittances/{id}` | `remittances.show` | Detail view |
| `edit` | GET `/remittances/{id}/edit` | `remittances.edit` | Edit form |
| `update` | PUT `/remittances/{id}` | `remittances.update` | Save edits |
| `destroy` | DELETE `/remittances/{id}` | `remittances.destroy` | Soft delete |
| `verify` | POST `/remittances/{id}/verify` | `remittances.verify` | Approve remittance |
| `reject` | POST `/remittances/{id}/reject` | `remittances.reject` | Reject with reason |
| `uploadReceipt` | POST `/remittances/{id}/upload-receipt` | `remittances.upload-receipt` | Attach proof doc |
| `deleteReceipt` | DELETE `/remittances/receipts/{id}` | `remittances.delete-receipt` | Remove proof doc |
| `export` | GET `/remittances/export/{format}` | `remittances.export` | CSV export |

### `RemittanceBeneficiaryController`

Routes under `candidates/{candidateId}/beneficiaries/` — CRUD for a candidate's beneficiary list.

- `GET /data` — AJAX endpoint used by remittance forms to load beneficiaries dynamically.

### `RemittanceReportController`

Routes under `/remittance/reports/`:

| Route | Purpose |
|-------|---------|
| `/dashboard` | Aggregated analytics dashboard |
| `/monthly` | Month-by-month breakdown |
| `/purpose-analysis` | Breakdown by transfer purpose |
| `/beneficiary` | Per-beneficiary analysis |
| `/proof-compliance` | % of remittances with proof |
| `/impact` | Economic impact analytics |
| `/export/{type}` | Excel / PDF export |

### `RemittanceAlertController`

Routes under `/remittance/alerts/` — view, acknowledge, resolve, and dismiss system alerts.

---

## Authorization (Policy: `RemittancePolicy`)

| Action | Who Can |
|--------|---------|
| View list | admin, campus_admin, oep, viewer |
| View record | Super admin: all; Campus admin: own campus; OEP: own candidates |
| Create | admin, campus_admin, oep |
| Update | admin / campus_admin (own campus); **blocked if already verified** |
| Delete | admin only; **blocked if verified** |
| Verify/Reject | admin, campus_admin (own campus) |

---

## Service Layer (`RemittanceService`)

| Method | Purpose |
|--------|---------|
| `createRemittance(array, file)` | Create with auto-reference generation & PKR calculation |
| `updateRemittance(model, array, file)` | Update; recalculate PKR; replace proof file |
| `verifyRemittance(model, userId, notes)` | Set verified; set status=completed |
| `rejectRemittance(model, userId, reason)` | Set rejected; set status=flagged |
| `markUnderReview(model, notes)` | Set under_review |
| `getStatistics(filters)` | Totals by currency, type, month |
| `getCandidateRemittances(candidateId, filters)` | History for one candidate |
| `getPendingVerifications(campusId)` | Paginated queue of pending items |
| `deleteProof(model)` | Remove proof file & clear path fields |
| `generateTransactionReference()` | Format: `RMT-YYYYMMDD-XXXXXX` |

---

## Forms & Validation

### Store / Update fields

| Field | Required | Rules |
|-------|----------|-------|
| `candidate_id` | Yes | Must exist and have a Departure |
| `transfer_date` | Yes | Date, not in the future |
| `amount` | Yes | Numeric > 0 |
| `currency` | No | Defaults to PKR |
| `primary_purpose` | Yes | One of the configured purposes |
| `sender_name` | Yes | Max 255 chars |
| `receiver_name` | Yes | Max 255 chars |
| `proof_document` | No | PDF/JPG/PNG, max 5 MB |
| `transaction_reference` | No | Auto-generated if blank; must be unique |

---

## Index Page Statistics

The index page displays four live stat cards computed from the current filter set:

| Card | Formula |
|------|---------|
| Total Remittances | COUNT(*) |
| Total Amount | SUM(amount) |
| Average Amount | SUM / COUNT |
| Proof Compliance | (records with proof / total) × 100 % |

---

## Alert System

Configured thresholds (`config/remittance.php`):

| Alert | Threshold |
|-------|-----------|
| No remittance received | 90 days since last |
| Proof not uploaded | 30 days after transfer |
| First remittance overdue | 60 days post-departure |
| Low frequency | < 3 remittances in 6 months |
| Large amount | > PKR 500,000 |

Alerts are generated by the scheduled command `generate-remittance-alerts` (runs daily).

---

## File Storage

All proof documents are stored on the **public** disk:
- Inline proofs: `storage/remittances/proofs/`
- Receipt uploads: `storage/remittances/receipts/`

Access via `Storage::url($path)` (public disk).

---

## Scheduled Commands

| Command | Schedule |
|---------|----------|
| `generate-remittance-alerts` | Daily |

---

## Common Gotchas

1. **Candidate must have a Departure record** — the create/edit forms only list candidates with a departure. Trying to record a remittance for a non-departed candidate will fail validation.

2. **Legacy vs v3 fields** — The `remittances` table has both legacy fields (`transfer_date`, `primary_purpose`, `year`, `month`) and v3 fields (`transaction_date`, `purpose`, `month_year`). The forms and controller use legacy fields. Both are kept in sync on save.

3. **`status` vs `verification_status`** — `status` is the display badge; `verification_status` drives the workflow logic. Always check `verification_status` in code.

4. **Currency select** — `config('remittance.currencies')` is a key→value map (`'PKR' => 'Pakistani Rupee'`). Always iterate `as $code => $name` and use `$code` as the option value.

5. **File path security** — Receipt files are on the public disk. `$receipt->file_url` (accessor) returns the public URL. The `file_path` attribute is hidden from serialization.
