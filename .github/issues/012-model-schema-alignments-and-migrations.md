Title: Align model schemas, casts and add missing migrations
Labels: cleanup, P2, db, estimate:2-4h
Assignees: @(unassigned)

Description:
Ensure model `$fillable` and casts match migrations; add missing columns (e.g., `complaint_number`, `province`, `force_password_change`) or update models/migrations accordingly.

Checklist:
- [ ] Audit `MODEL_SCHEMA_AUDIT_2026-01-09.md` items and list mismatches
- [ ] Add safe migrations for missing/incorrect columns (idempotent)
- [ ] Update models `$fillable` and `$casts` accordingly
- [ ] Update or run seeders to include new fields where appropriate
- [ ] Add tests verifying model attributes and database columns

Acceptance Criteria:
- Models and DB schema are consistent; migrations are documented and reversible

Files: `database/migrations/`, `app/Models/*`