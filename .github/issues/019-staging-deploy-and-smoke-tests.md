Title: Deploy to staging and run smoke tests
Labels: infra, P1, estimate:2-4h
Assignees: @(unassigned)

Description:
Deploy the branch to a staging environment and perform end-to-end smoke tests for the critical flows (registration, complaint lifecycle, scheduled tasks, exports).

Checklist:
- [ ] Deploy `fix/audit-complete` to staging environment
- [ ] Run migrations and seeders on staging
- [ ] Execute smoke tests: registration verify, create complaint with evidence, status transitions, export remittance, scheduled job run
- [ ] Document any staging-found issues and open follow-up issues

Acceptance Criteria:
- All smoke tests pass on staging, or follow-up issues are logged

Files: Deployment scripts, docs in `DEPLOY_PRODUCTION_FIX.md`