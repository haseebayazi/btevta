Title: Fix priority escalation edge-case (array_search false handling)
Labels: bug, P1, backend, estimate:0.5h
Assignees: @(unassigned)

Description:
Fix the `array_search()` false -> 0 bug in `ComplaintService::increasePriority()` and add stricter checks.

Checklist:
- [x] Use strict search: `array_search(..., true)`
- [x] If `=== false`, default to a safe priority (`high`) or throw `InvalidArgumentException`
- [x] Add unit tests for invalid priority values and escalation correctness

Acceptance Criteria:
- Unit tests cover the edge case and pass

Files: `app/Services/ComplaintService.php`