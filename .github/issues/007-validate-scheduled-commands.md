Title: Validate scheduled command signatures vs route/schedule usage
Labels: bug, P1, infra, estimate:1h
Assignees: @(unassigned)

Description:
Ensure scheduled command signatures match the invocations in `routes/console.php` and that schedule parameters are correct (fix mismatches causing tasks to never run).

Checklist:
- [ ] Audit scheduled commands listed in `routes/console.php` vs `app/Console/Commands/*` signatures
- [ ] Fix signature mismatches (options, flags) in command classes or schedule definitions
- [ ] Add unit/integration test to call command signature parsing where possible
- [ ] Document schedule responsibilities in `README`/deployment notes

Acceptance Criteria:
- `php artisan list` shows commands usable with scheduled signatures
- Scheduler tasks execute locally using `schedule:run` for manual tests

Files: `routes/console.php`, `app/Console/Commands/*`