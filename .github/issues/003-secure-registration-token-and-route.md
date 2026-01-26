Title: Replace MD5 token generation with secure token and add `registration.verify` route
Labels: security, P0, backend, estimate:1-2h
Assignees: @(unassigned)

Description:
Replace predictable `md5()` token generation with secure SHA-256 + random salt or UUID, persist token if needed, and add missing verification route `registration.verify` to `routes/web.php`.

Checklist:
- [ ] Update token generation in `RegistrationService` to use `hash('sha256', ...)` with `config('app.key')` and random salt or use `Str::uuid()`/`bin2hex(random_bytes(16))`
- [ ] Persist verification token in DB if service requires lookup
- [ ] Add route `Route::get('/registration/verify/{id}/{token}', [RegistrationController::class, 'verify'])->name('registration.verify');`
- [ ] Add tests validating token generation and verification fail for invalid tokens

Acceptance Criteria:
- No usage of md5 remains for security tokens
- Registration verification route exists and unit tests pass

Files: `app/Services/RegistrationService.php`, `routes/web.php`, tests for registration flow