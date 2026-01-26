Title: Add auth() null checks across services
Labels: bug, P0, backend, estimate:2-4h
Assignees: @(unassigned)

Description:
Audit services calling `auth()->user()`/`auth()->id()` and add null checks or explicit failures to avoid null-related runtime errors.

Checklist:
- [ ] Grep for `auth()->user()` and `auth()->id()` across `app/Services` and relevant controllers
- [ ] For each usage, decide: require auth (throw informative exception) or allow null (use `?? null`)
- [ ] Add unit tests to simulate unauthenticated calls where relevant
- [ ] Add short documentation comment where a service requires authenticated user

Acceptance Criteria:
- No runtime errors due to null `auth()` in services after changes

Files: `app/Services/*`