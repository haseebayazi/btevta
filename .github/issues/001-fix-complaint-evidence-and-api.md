Title: Fix complaint evidence handling & register/resolve parameter mismatch
Labels: bug, P0, backend, estimate:3-4h
Assignees: @(unassigned)

Description:
Resolve runtime exceptions in `ComplaintService::uploadEvidence` when the controller passes a path string instead of an UploadedFile. Align `ComplaintController::resolve()` call to the service signature.

Checklist:
- [x] Update `ComplaintService::uploadEvidence()` to accept either UploadedFile or a file path string and handle both cases safely
- [x] Update `ComplaintController::store()` to pass UploadedFile path (preferred) and document the API contract clearly
- [x] Fix `ComplaintController::resolve()` to call `resolveComplaint($id, $dataArray)` matching service expectations
- [x] Add defensive type checks and clear exception messages
- [x] Add unit tests covering: register with UploadedFile, addEvidence with both filepath and UploadedFile, resolve with correct params
- [x] Update tests in `tests/Unit/ComplaintServiceTest.php` to cover evidence cases

Acceptance Criteria:
- `php artisan test --filter=ComplaintServiceTest` passes
- No runtime exceptions for `store` or `addEvidence` flows

Files: `app/Services/ComplaintService.php`, `app/Http/Controllers/ComplaintController.php`, `tests/Unit/ComplaintServiceTest.php`

Notes: Blocker for shipping P0 fixes per audit report.