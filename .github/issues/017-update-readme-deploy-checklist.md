Title: Update deployment checklist, remove credentials & secure defaults
Labels: chore, P2, docs, estimate:1-2h
Assignees: @(unassigned)

Description:
Finalize README/DEPLOY checklists: set APP_ENV=production guidance, APP_DEBUG=false, generate APP_KEY, remove seeder-credentials.log references, document Supervisor/cron/email/SSL setup.

Checklist:
- [ ] Update `README.md` and `README_AUDIT_REPORT.md` steps reflecting completed audit actions
- [ ] Remove any plain text credentials or move them to secure storage (seeder logs)
- [ ] Add deploy checklist step to regenerate `APP_KEY` and set `APP_DEBUG=false`
- [ ] Add guidance for Supervisor, cron, SSL, backups and 2FA

Acceptance Criteria:
- README contains explicit, correct deployment steps and no plaintext credentials are in repo

Files: `README.md`, `README_AUDIT_REPORT.md`, `DEPLOY_PRODUCTION_FIX.md`