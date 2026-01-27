Title: Add structured exception logging to `Handler::register()`
Labels: chore, P2, infra, estimate:1h
Assignees: @(unassigned)

Description:
Improve observability by logging exceptions with context (user_id, url, input except password) in `app/Exceptions/Handler.php`.

Checklist:
- [x] Update `reportable()` in Handler to log structured data for errors
- [x] Exclude sensitive fields from logs (`password`, `password_confirmation`)
- [ ] Add a test or manual smoke check to ensure logging runs (manual smoke performed locally)

Acceptance Criteria:
- Exceptions logged with contextual properties in local dev logs

Files: `app/Exceptions/Handler.php`