Title: Post-deploy monitoring and follow-ups
Labels: ops, P2, estimate:1-2h
Assignees: @(unassigned)

Description:
After production deploy, verify monitoring, alerts, scheduled jobs, and follow up on any operational gaps.

Checklist:
- [ ] Verify health checks/monitoring are reporting correctly
- [ ] Ensure scheduled tasks are running under Supervisor/cron
- [ ] Review logs for unexpected exceptions and create issues for any new findings
- [ ] Schedule a post-release review and update documentation

Acceptance Criteria:
- Monitoring is operational and an action list is created for any outstanding items

Files: Ops runbook, monitoring dashboards