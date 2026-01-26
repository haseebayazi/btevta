Title: Add `/health` endpoint for system checks
Labels: chore, P2, infra, estimate:1-2h
Assignees: @(unassigned)

Description:
Implement a lightweight `/health` endpoint to return DB/cache/storage status for monitoring.

Checklist:
- [ ] Add route `GET /health` in `routes/api.php`
- [ ] Implement checks for DB, cache, and storage (safe and fast)
- [ ] Return 200 when healthy, 503 when failing
- [ ] Add automated test for endpoint

Acceptance Criteria:
- Endpoint returns JSON with component status and correct HTTP status

Files: `routes/api.php`, small controller/closure and tests