Title: Normalize legacy complaint statuses & update validations
Labels: bug, P0, backend, estimate:1-2h
Assignees: @(unassigned)

Description:
Legacy status values (e.g., `registered`, `investigating`) cause invalid transitions and test failures. Standardize to `open, assigned, in_progress, resolved, closed` and add a migration to update DB rows.

Checklist:
- [x] Update controller request validations to use canonical enum values (`open,assigned,in_progress,resolved,closed`)
- [ ] Add a migration that maps legacy statuses to the canonical ones (e.g., `registered` -> `open`, `investigating` -> `in_progress`)
- [ ] Add a small seeder/script to fix production data (idempotent)
- [x] Add unit tests for `updateStatus()` including behavior when current status is legacy and after migration (where feasible)

Acceptance Criteria:
- No tests fail due to invalid status transitions
- Migration is reversible and documented

Files: `app/Http/Controllers/ComplaintController.php`, `database/migrations/`, `app/Enums/ComplaintStatus.php` (reference)

Notes: Ensure data migration is safe and can be run in staging prior to production.