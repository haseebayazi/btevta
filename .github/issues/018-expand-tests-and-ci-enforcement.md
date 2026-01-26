Title: Expand test coverage and enforce CI checks
Labels: test, chore, P1, estimate:2-4h
Assignees: @(unassigned)

Description:
Add tests for all fixed behaviors and ensure CI fails on regressions. Include focused tests for complaint flows and scheduled commands.

Checklist:
- [ ] Add/expand unit tests for `ComplaintService`, `DepartureService`, registration token flow, and scheduled commands
- [ ] Add integration tests where appropriate (exports, file uploads)
- [ ] Ensure CI pipeline runs `php artisan test` and fails on any test failure
- [ ] Add a job to run `phpstan`/`psalm` or `phpcs` if not present

Acceptance Criteria:
- CI pipeline is green for the branch and fails on introduced regressions

Files: `tests/Unit/*`, `.github/workflows/*`