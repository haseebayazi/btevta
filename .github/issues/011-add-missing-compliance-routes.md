Title: Add missing compliance monitoring & report routes
Labels: chore, P1, backend, estimate:1-2h
Assignees: @(unassigned)

Description:
Add the routes referenced by views but missing from `routes/web.php` (e.g., `complaints.overdue`, `document-archive.expiring`, `reports.trainer-performance`, `complaints.sla-report`, etc.).

Checklist:
- [ ] Add `registration.verify` (if not yet added)
- [ ] Add `complaints.overdue`, `complaints.sla-report`
- [ ] Add `document-archive.expiring` and docs report routes
- [ ] Add `reports.trainer-performance` and `departure.reports.pending-activations` as needed
- [ ] Add route tests ensuring route names resolve

Acceptance Criteria:
- `php artisan route:list` contains all added routes
- Views referencing route names no longer fail

Files: `routes/web.php`, controllers to be created/updated as needed