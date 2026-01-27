Title: Fix Carbon date mutation bug in DepartureService
Labels: bug, P0, backend, estimate:0.5-1h
Assignees: @(unassigned)

Description:
Avoid mutating original Carbon objects when calculating compliance deadlines; use `copy()` to compute derived dates and add null checks for missing departure_date.

Checklist:
- [ ] Replace usages of `$departureDate->addDays(...)` with `$departureDate->copy()->addDays(...)`
- [ ] Add null checks before parsing dates and handle missing dates gracefully
- [ ] Add unit tests ensuring original date is not mutated and calculations are correct

Acceptance Criteria:
- Tests for `DepartureService` pass and no date-mutation side effects occur

Files: `app/Services/DepartureService.php`, tests covering compliance calculations