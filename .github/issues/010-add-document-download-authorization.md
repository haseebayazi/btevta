Title: Add document download authorization checks
Labels: security, P1, backend, estimate:0.5-1h
Assignees: @(unassigned)

Description:
Prevent unauthorized document downloads by enforcing policy checks in `SecureFileController` and `DocumentArchiveController`.

Checklist:
- [x] Add `$this->authorize('view', $document)` or equivalent ownership checks in download endpoints (`DocumentArchiveController`, `SecureFileController` authorization)
- [ ] Add unit tests verifying unauthorized users receive 403
- [x] Update documentation and security notes (routes and controllers annotated)

Acceptance Criteria:
- Controllers include authorization checks and tests pass

Files: `app/Http/Controllers/SecureFileController.php`, `app/Http/Controllers/DocumentArchiveController.php`