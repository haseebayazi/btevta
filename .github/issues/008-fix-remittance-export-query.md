Title: Fix remittance export filter using correct column (transfer_date)
Labels: bug, P1, backend, estimate:0.5-1h
Assignees: @(unassigned)

Description:
Export query filters by `remittance_date` while DB column is `transfer_date`, returning no results; update and add tests.

Checklist:
- [ ] Update export query to use `transfer_date`
- [ ] Add unit test ensuring exports return rows for given date range
- [ ] Add integration test with sample data

Acceptance Criteria:
- Export returns expected rows in tests

Files: `app/Services/RemittanceExportService.php` (or related export files)