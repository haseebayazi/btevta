Title: Replace MD5 token generation with secure token and add `registration.verify` route
Labels: security, P0, backend, estimate:1-2h
Assignees: @(unassigned)

Description:
Replace predictable `md5()` token generation with secure SHA-256 + random salt or UUID, persist token if needed, and add missing verification route `registration.verify` to `routes/web.php`.

Checklist:
- [x] Update token generation in `RegistrationService` to use cryptographically secure token (random) and migrate away from predictable MD5
- [x] Persist verification token in DB and provide idempotent seeder to backfill existing records
- [x] Route `Route::get('/registration/verify/{id}/{token}', [RegistrationController::class, 'verifyQRCode'])->name('registration.verify');` exists and is publicly accessible via signed URL
- [x] Add tests validating token generation and verification fail for invalid tokens (legacy SHA-256 supported for backwards compatibility)

Acceptance Criteria:
- No usage of md5 remains for security tokens
- Registration verification route exists and unit tests pass

Files: `app/Services/RegistrationService.php`, `routes/web.php`, tests for registration flow