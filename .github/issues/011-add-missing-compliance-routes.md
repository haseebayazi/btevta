Title: Add missing compliance monitoring & report routes
Labels: chore, P1, backend, estimate:1-2h
Assignees: @(unassigned)

Description:
Add the routes referenced by views but missing from `routes/web.php` (e.g., `complaints.overdue`, `document-archive.expiring`, `reports.trainer-performance`, `complaints.sla-report`, etc.).

Checklist:
- [x] Add `registration.verify` (already present)
- [x] Add `complaints.overdue`, `complaints.sla-report` (routes added/renamed)
- [x] Add `document-archive.expiring` and docs report routes
- [x] `reports.trainer-performance` and `departure.reports.pending-activations` are present
- [ ] Add route tests ensuring route names resolve

Acceptance Criteria:
- `php artisan route:list` contains all added routes
- Views referencing route names no longer fail

Files: `routes/web.php`, controllers to be created/updated as needed