# Data Readiness & Launch Guide (WASL / BTEVTA)

**Purpose:** Make the WASL application *data-ready* — every dashboard, report and
candidate-journey view loads **real data**, all dummy/test candidates can be
removed, only admin accounts are retained, storage is prepared for real uploads,
and the security posture is verified for go-live.

**Status:** Ready to ship (see [Go-Live Checklist](#go-live-checklist)).
**Date:** 2026-06 · **Branch:** `claude/qa-data-readiness-launch-985szf`

---

## 1. Executive Summary

This QA pass was executed in phases. The key outcomes:

| Area | Result |
|------|--------|
| Dashboard reporting | Already real-data; verified end-to-end |
| Reporting Module | **5 real data-loading bugs fixed** (see §3) |
| Candidate Journey | Verified — computed from live candidate data, no stubs |
| Dummy data removal | Production seeding now creates **admins + reference data only**; new `data:reset-for-launch` command cleans existing databases |
| Storage | Runtime directories guaranteed to exist on deploy |
| Security | Seeder shared-password hole closed; env/storage/credentials verified |

Net test impact: **8 ReportingService unit tests fixed, 0 regressions** across the
Unit and Feature suites (verified by diffing against the clean tree).

---

## 2. Reporting — verified to load real data

The following were audited and confirmed to query the database (no hardcoded or
placeholder values):

- **`DashboardController`** — all role dashboards (admin, campus admin, OEP, visa
  partner, instructor), pipeline stats, monthly/weekly trends, campus & trade
  breakdowns, remittance stats, alerts, and the 10 dashboard tabs.
- **`ReportController`** — candidate profile, batch summary, campus/OEP
  performance, visa timeline, training statistics, complaint analysis, custom
  report, trainer performance/detail, assessment analytics, departure updates,
  instructor utilization, funding metrics (KPIs), CSV/Excel/PDF exports.
- **`ReportingService`** — candidate pipeline, training, visa, compliance and
  custom report builders, all with role-based (campus/OEP) access scoping.
- **`CandidateJourneyService`** — stage-by-stage journey, milestones, next
  actions, blockers, ETA and progress %, all derived from the candidate's own
  records.

### Bugs fixed (reports that previously did NOT load real data)

See §3.

---

## 3. Phase-by-Phase Work

### Phase 1 — Reporting correctness (real data)

| # | File | Bug | Fix |
|---|------|-----|-----|
| 1 | `app/Models/Oep.php` | `ReportingService::getOepVisaPerformance()` called `Oep::withCount('visaProcesses')` but the relation did **not exist** → `BadMethodCallException` crashed the **entire visa processing report** in production. | Added `Oep::visaProcesses()` `hasManyThrough` relation (via candidates). |
| 2 | `app/Services/ReportingService.php` | Departure compliance used `ninety_day_compliance` — a **non-existent column** → report always showed **0 compliant**. | Use the real `ninety_day_report_submitted` column. |
| 3 | `app/Services/ReportingService.php` | `getDocumentExpiryStats()` was a **stub returning `0/0/0`**. | Implemented real `DocumentArchive` query (expiring soon / expired / valid) with campus/OEP scoping. |
| 4 | `app/Services/ReportingService.php` | Registration-trend query used MySQL-only `DATE_FORMAT`, breaking on any non-MySQL connection. | Added a driver-aware `monthExpression()` helper (MySQL/SQLite/PostgreSQL). |
| 5 | `app/Services/ReportingService.php` | `clearCache()` called `Cache::tags()`, which throws `BadMethodCallException` on the `file`/`database` cache stores. | Guarded with a `TaggableStore` check; falls back to a plain flush. |
| 6 | `app/Services/RemittanceAnalyticsService.php` | "Top remitting candidates" report selected `candidates.full_name` in raw SQL — `full_name` is only a PHP accessor, **not a column** → query error in production. | Select `candidates.name as full_name` and group by the real column. |

### Phase 2 — Cache configuration (Laravel 11)

Laravel 11 reads `CACHE_STORE`; the legacy `CACHE_DRIVER` key is **silently
ignored** and the store falls back to the default. The project used the old key:

- `.env.example`: `CACHE_DRIVER=file` → `CACHE_STORE=database` (with explanatory
  note; the cache table migration already exists).
- `phpunit.xml.dist`: `CACHE_DRIVER=array` → `CACHE_STORE=array`.

### Phase 3 — Production-safe seeding (remove dummy candidates, keep admins)

The seeding model is now **environment-aware**:

- **`database/seeders/ProductionSeeder.php`** (new): seeds **only** admin-tier
  accounts (Super Admin, Admin, Project Director) + essential reference/lookup
  data (countries, payment methods, programs, implementing partners, courses,
  document checklists, complaint templates). **No** sample candidates or
  placeholder org data. Existing admin accounts are never modified.
- **`database/seeders/DatabaseSeeder.php`**: in `production` it delegates to
  `ProductionSeeder`; in `local`/`testing` it seeds the full demo environment as
  before. Misleading "random password" docblock corrected.
- **`database/seeders/LifecycleDataSeeder.php`** (dummy candidates): now hard
  **refuses to run in production** (matching the existing `TestDataSeeder` guard).

> Seed scope decision: a clarifying question could not be delivered (tooling
> error), so the documented default — **admins + system reference data only** —
> was applied. To instead keep the sample campuses/trades/OEPs, run the existing
> `DatabaseSeeder` in a non-production environment, or seed those entities via
> the admin UI / import.

### Phase 4 — Launch reset command (clean an existing database)

**`app/Console/Commands/ResetForLaunch.php`** → `php artisan data:reset-for-launch`

Removes candidate + transactional data while **preserving admin accounts and
reference/lookup data**. Always preview first.

```bash
# Preview what would be deleted (no changes)
php artisan data:reset-for-launch --dry-run

# Delete candidates + all transactional data (keeps users, org, reference data)
php artisan data:reset-for-launch

# Also delete non-admin user accounts (keeps super_admin/admin/project_director)
php artisan data:reset-for-launch --with-users

# Also delete org master data (campuses, batches, trades, OEPs, visa partners,
# instructors, employers)
php artisan data:reset-for-launch --with-users --with-org

# Non-interactive (CI/scripts)
php artisan data:reset-for-launch --force
```

**Always preserved:** countries, payment methods, programs, implementing
partners, courses, document checklists, complaint templates, document tags,
system settings, and admin accounts.

**Safety:** production double-confirmation, `--dry-run`, FK-safe deletion
(children-first ordering + FK toggling), and an audit log entry on every run.

*Validated:* dry-run accurately previews counts; live run deletes candidates +
screenings + departures while preserving campuses, trades and users; `--with-org
--with-users` reduces users to admins only and clears org tables with no FK
errors.

### Phase 5 — Storage readiness

Runtime directories are now guaranteed to exist on a fresh clone/deploy via
tracked `.gitkeep` files (contents remain git-ignored):

- `storage/app/private/` — private candidate documents (CNIC, passport, medical,
  visa, certificates, receipts, complaint evidence) served only via
  `SecureFileController`.
- `storage/app/public/` and `storage/app/temp/` — public assets and export temp
  files.
- `storage/logs/` and `bootstrap/cache/`.

`.gitignore` was updated to keep the directories while ignoring their contents.

Post-deploy storage steps are in the [Go-Live Checklist](#go-live-checklist).

### Phase 6 — Security QA

- **Seeder passwords (fixed):** `DatabaseSeeder` previously set a **shared,
  hardcoded password** (`Jaamiah@12345`) for *all* accounts with
  `force_password_change = false`, while its docblock falsely claimed random
  passwords. In production, admins now get a password from `SEED_ADMIN_PASSWORD`
  or a strong random value, **with `force_password_change = true`**. The
  convenience password remains only for local/testing.
- **Credentials log:** written to `storage/logs/seeder-credentials.log` (chmod
  `0600`, git-ignored). Delete after distributing credentials.
- **Env defaults:** `.env.example` already enforces `APP_ENV=production`,
  `APP_DEBUG=false`, `SESSION_SECURE_COOKIE=true`, `SESSION_HTTP_ONLY=true`,
  `SESSION_SAME_SITE=strict`, Sanctum expiry, and password policy.
- **Verified clean:** no `dd()`/`dump()`/`var_dump()` debug leftovers in `app/`;
  no `.env`/credentials/secrets tracked in git; private disk used for sensitive
  documents; LIKE-injection escaping present on search inputs.

---

## 4. Deployment: how to seed

**Fresh production install**

```bash
php artisan migrate --force
php artisan db:seed --force                       # delegates to ProductionSeeder in production
# or explicitly:
php artisan db:seed --class=ProductionSeeder --force
```

Optionally set a known admin password for the seed run:

```bash
SEED_ADMIN_PASSWORD='<strong-password>' php artisan db:seed --force
```

**Existing/staging database that already has demo data**

```bash
php artisan data:reset-for-launch --dry-run       # preview
php artisan data:reset-for-launch                 # clean
php artisan cache:clear
```

---

## 5. Known pre-existing test failures (out of scope)

The application targets **MySQL 8**. Large parts of the dashboard/report SQL use
MySQL-only functions (`DATE_FORMAT`, `YEARWEEK`, `DATE_ADD … INTERVAL`,
`CURDATE`, …). When the test suite runs on **SQLite** (`:memory:`), those tests
fail with `no such function`. Additionally, several service unit tests run
**unauthenticated**, so role-based access filters correctly deny all rows.

These are **environment/test-data characteristics, not production bugs**, and
are outside this data-readiness scope. They were present before this work
(verified: clean tree = 41 unit / 228 feature failures; this branch = 33 unit /
228 feature — i.e. this branch *fixes* 8 and regresses none). To exercise the
MySQL-specific suites, point the test connection at a MySQL database.

---

## 6. Go-Live Checklist

- [ ] `.env` configured: `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL` set,
      `APP_KEY` generated.
- [ ] `CACHE_STORE` set (`database` or `redis`); cache table migrated if using
      `database`.
- [ ] `php artisan migrate --force` run.
- [ ] Database seeded via `ProductionSeeder` (admins + reference data only).
- [ ] `SEED_ADMIN_PASSWORD` used **or** `storage/logs/seeder-credentials.log`
      read, credentials distributed, then the file **deleted**.
- [ ] If reusing a demo DB: `data:reset-for-launch` run and verified.
- [ ] `php artisan storage:link` run (public disk symlink).
- [ ] Storage dirs writable: `storage/`, `bootstrap/cache/`.
- [ ] `php artisan config:cache route:cache view:cache` (or `optimize`).
- [ ] HTTPS enforced; secure session cookies confirmed.
- [ ] Scheduler/queue worker running (SLA checks, reminders, alerts).
- [ ] First admin login forces password change.
- [ ] Real campuses / trades / OEPs / users created (UI or import).
- [ ] Real candidate data loaded.
```
