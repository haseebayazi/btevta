Title: Prepare branch, baseline CI & tests
Labels: chore, infra, estimate:0.5h
Assignees: @(unassigned)

Description:
Create a working branch and establish baseline CI/test artifacts so subsequent fixes are small, reviewable PRs.

Checklist:
- [ ] Create branch `fix/audit-complete` from `main` or `develop`
- [ ] Run full test suite and save failing tests output as artifact
- [ ] Ensure CI runs `php artisan test` and publishes results
- [ ] Add a short PR template referencing the audit report and this issue

Acceptance Criteria:
- Branch exists and CI job runs tests on PRs
- Baseline test results attached to the issue

Related: Implementation Plan Phase 0