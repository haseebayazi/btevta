Title: Add document download authorization checks
Labels: security, P1, backend, estimate:0.5-1h
Assignees: @(unassigned)

Description:
Prevent unauthorized document downloads by enforcing policy checks in `SecureFileController` and `DocumentArchiveController`.

Checklist:
- [ ] Add `$this->authorize('view', $document)` or equivalent ownership checks in download endpoints
- [ ] Add unit tests verifying unauthorized users receive 403
- [ ] Update documentation and security notes

Acceptance Criteria:
- Controllers include authorization checks and tests pass

Files: `app/Http/Controllers/SecureFileController.php`, `app/Http/Controllers/DocumentArchiveController.php`