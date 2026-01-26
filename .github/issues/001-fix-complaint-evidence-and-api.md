Title: Fix complaint evidence handling & register/resolve parameter mismatch
Labels: bug, P0, backend, estimate:3-4h
Assignees: @(unassigned)

Description:
Resolve runtime exceptions in `ComplaintService::uploadEvidence` when the controller passes a path string instead of an UploadedFile. Align `ComplaintController::resolve()` call to the service signature.

Checklist:
- [ ] Update `ComplaintService::uploadEvidence()` to accept either UploadedFile or a file path string and handle both cases safely
- [ ] Update `ComplaintController::store()` to pass UploadedFile (preferred) or document the API contract clearly
- [ ] Fix `ComplaintController::resolve()` to call `resolveComplaint($id, $dataArray)` matching service expectations OR update the service signature (choose one and document)
- [ ] Add defensive type checks and clear exception messages
- [ ] Add unit tests covering: register with UploadedFile, addEvidence with both filepath and UploadedFile, resolve with correct params
- [ ] Update any failing tests in `tests/Unit/ComplaintServiceTest.php`

Acceptance Criteria:
- `php artisan test --filter=ComplaintServiceTest` passes
- No runtime exceptions for `store` or `addEvidence` flows

Files: `app/Services/ComplaintService.php`, `app/Http/Controllers/ComplaintController.php`, `tests/Unit/ComplaintServiceTest.php`

Notes: Blocker for shipping P0 fixes per audit report.