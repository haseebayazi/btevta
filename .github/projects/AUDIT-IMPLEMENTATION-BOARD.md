# Audit Implementation Board (Project)

Columns:
- To Do
- In Progress
- QA
- Done

How to use:
- Create a GitHub Issue PR per markdown in `.github/issues/` (one-to-one mapping)
- Link PR to its issue and move issue to **In Progress** when work begins
- After tests and review, move to **QA**, then **Done** once merged and deployed

Issue mapping (initial backlog)

## To Do
- `000-prepare-branch-and-baseline.md` — Prepare branch and baseline CI (0.5h)
- `001-fix-complaint-evidence-and-api.md` — Fix complaint evidence and resolve mismatch (3-4h) [P0]
- `002-normalize-complaint-statuses.md` — Normalize legacy statuses & migration (1-2h) [P0]
- `003-secure-registration-token-and-route.md` — Replace MD5 tokens & add verify route (1-2h) [P0]
- `004-fix-departure-date-mutation.md` — DepartureService date mutation fix (0.5-1h)
- `005-fix-priority-escalation-edgecase.md` — array_search edge-case (0.5h)
- `006-add-auth-null-checks.md` — Add auth null checks (2-4h)
- `007-validate-scheduled-commands.md` — Validate scheduled commands (1h)
- `008-fix-remittance-export-query.md` — Fix remittance export (0.5-1h)
- `009-wrap-bulk-operations-in-transactions.md` — Wrap bulk ops in transactions (1-2h)
- `010-add-document-download-authorization.md` — Add download auth checks (0.5-1h)
- `011-add-missing-compliance-routes.md` — Add missing routes (1-2h)
- `012-model-schema-alignments-and-migrations.md` — Model schema and migrations (2-4h)
- `013-create-formrequest-classes.md` — Create FormRequest classes (3-6h)
- `014-replace-hardcoded-strings-with-enums.md` — Replace hardcoded values (4-8h)
- `015-add-health-check-endpoint.md` — Add /health endpoint (1-2h)
- `016-structured-exception-logging.md` — Improve exception logging (1h)
- `017-update-readme-deploy-checklist.md` — Update README/deploy checklist (1-2h)
- `018-expand-tests-and-ci-enforcement.md` — Expand tests and CI enforcement (2-4h)
- `019-staging-deploy-and-smoke-tests.md` — Staging deploy & smoke tests (2-4h)
- `020-post-deploy-monitoring-and-followups.md` — Post-deploy monitoring (1-2h)

---

Tips:
- Keep PRs small and focused (1 issue -> 1 PR)
- Add cross-links to the audit report and failing test outputs in each PR
- Use labels: P0/P1/P2, `backend`, `security`, `infra`, `test` to triage

---

Generated from the audit implementation plan on 2026-01-27.