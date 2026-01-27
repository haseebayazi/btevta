Title: Wrap bulk operations in DB transactions
Labels: bug, P1, backend, estimate:1-2h
Assignees: @(unassigned)

Description:
Wrap multi-record modifications (imports, bulk updates, etc.) in `DB::transaction()` to ensure atomicity and prevent partial data writes.

Checklist:
- [x] Identify bulk operations (`ImportController`, `BulkOperationsController`, etc.)
- [x] Wrap operations in `DB::transaction()` and add explicit try/catch for error logging (applied where appropriate)
- [ ] Add unit tests that simulate failure to ensure rollback occurs

Acceptance Criteria:
- Bulk operation tests assert DB state unchanged on simulated failure

Files: `app/Http/Controllers/*`, relevant service classes