Title: Replace hardcoded strings with enums/constants
Labels: refactor, P2, backend, estimate:4-8h
Assignees: @(unassigned)

Description:
Replace literal status/priority strings across the codebase with enums or constants (e.g., `ComplaintStatus::OPEN->value`) to avoid drift and regressions.

Checklist:
- [ ] Find occurrences of hardcoded status/priority strings and categorize them
- [ ] Replace with enums/constants and update code where `->value` or enum checks are required
- [ ] Add unit tests targeting changed behavior
- [ ] Search and update documentation and views showing statuses

Acceptance Criteria:
- No remaining hard-coded status strings in `app/*` files for complaint flows

Files: Multiple across `app/Controllers`, `app/Services`, `resources/views`