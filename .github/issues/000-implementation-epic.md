Title: Epic: Implement full audit remediation plan
Labels: epic, roadmap, estimate: (sum ~ 24-44h)
Assignees: @(unassigned)

Description:
This epic tracks all tasks required to implement the audit remediation plan (P0->P2). Each subtask is represented by a dedicated issue in `.github/issues/` and must be implemented in small PRs.

Checklist (link to issues in repo once created):
- [ ] 000 - Prepare branch and baseline CI
- [ ] 001 - Fix complaint evidence & API signature
- [ ] 002 - Normalize complaint statuses & migration
- [ ] 003 - Secure registration token + route
- [ ] 004 - Fix departure date mutation
- [ ] 005 - Fix priority escalation edge-case
- [ ] 006 - Add auth null checks
- [ ] 007 - Validate scheduled command signatures
- [ ] 008 - Fix remittance export query
- [ ] 009 - Wrap bulk operations in transactions
- [ ] 010 - Add document download authorization
- [ ] 011 - Add missing compliance routes
- [ ] 012 - Model schema alignments & migrations
- [ ] 013 - Create FormRequest classes
- [ ] 014 - Replace hardcoded strings with enums
- [ ] 015 - Add /health endpoint
- [ ] 016 - Improve structured exception logging
- [ ] 017 - Update README & deployment checklist
- [ ] 018 - Expand tests & enforce CI
- [ ] 019 - Staging deploy & smoke tests
- [ ] 020 - Post-deploy monitoring & followups

Acceptance Criteria:
- All P0 and P1 tasks merged, staged, and smoke-tested
- Tests pass in CI for merged PRs

Notes:
- Use the project board `.github/projects/AUDIT-IMPLEMENTATION-BOARD.md` to track progress visually.
- Create one PR per issue and reference the issue number in the PR title and body.