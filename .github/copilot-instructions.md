# Copilot Instructions for WASL (btevta)

## Project Overview
- **WASL** is a Laravel 11.x (PHP 8.2+) application for managing the full lifecycle of overseas employment candidates for TheLeap/BTEVTA.
- The system covers candidate import, screening, training, visa processing, departure, post-deployment, and remittance management.
- Key modules: Candidates, Screening, Registration, Training, Visa, Departure, Correspondence, Complaints, Document Archive, Reports.

## Architecture & Key Patterns
- **Backend:** Laravel MVC, with clear separation: Controllers (request handling), Services (business logic), Models (Eloquent ORM), Policies (authorization), Observers (model events).
- **Frontend:** Blade templates, Tailwind CSS, Alpine.js. Chart.js for analytics.
- **API:** RESTful, with Laravel Sanctum for authentication.
- **Activity Logging:** Spatie Activity Log package.
- **Document Generation:** PhpSpreadsheet, DomPDF.
- **Notifications:** Real-time via WebSockets or polling.
- **Bulk Operations:** Supported in candidate and admin modules.

## Directory Structure Highlights
- `app/Http/Controllers/` — 30+ controllers, one per major workflow/module.
- `app/Services/` — Business logic, 14+ services.
- `app/Models/` — 34+ Eloquent models, soft-deletes used for most entities.
- `app/Policies/` — 40+ policies, strict role-based access.
- `routes/web.php` and `routes/api.php` — Web and API endpoints.
- `resources/views/` — Blade templates, organized by module.
- `config/` — Custom config for remittance, statuses, activitylog, etc.

## Developer Workflows
- **Install:** `composer install` (PHP), `npm install && npm run build` (assets)
- **Setup:** Copy `.env.example` to `.env`, run `php artisan key:generate`
- **Migrate/Seed:** `php artisan migrate`, `php artisan db:seed` (admin credentials in `storage/logs/seeder-credentials.log`)
- **Serve:** `php artisan serve` (dev server)
- **Build assets:** `npm run build` (Tailwind, Alpine.js)
- **Testing:** `php artisan test` (uses `phpunit.xml.dist`)
- **Storage:** `php artisan storage:link` for file uploads

## Project-Specific Conventions
- **Role/Permission:** 9 roles, enforced via policies. See `app/Policies/` and `README.md` for matrix.
- **Soft Deletes:** Used for users, candidates, and most workflow entities.
- **Document Storage:** Secure files in `storage/app/private/`, public via symlink.
- **Bulk Actions:** Use provided service methods for multi-entity operations.
- **Audit Trail:** All critical actions logged via Spatie Activity Log.
- **Remittance:** Custom logic in `app/Services/RemittanceService.php` and `config/remittance.php`.
- **Security:** Password policy and session settings in `.env` and `config/`.

## Integration & External Dependencies
- **Mail:** Configured via `.env`, supports Gmail (App Password required).
- **APIs:** External OEP, Takamol, GAMCA integrations via service classes.
- **Queue/Cache:** Redis recommended for production (`QUEUE_CONNECTION=redis`).

## Examples
- **Add a new candidate:** Use `CandidateController@store`, validate with `StoreCandidateRequest`, business logic in `CandidateService`.
- **Bulk import:** Use Excel import in UI, handled by `Imports/` classes.
- **Remittance tracking:** See `RemittanceService` and `routes/api.php` endpoints.

## References
- See `README.md` for full setup, module, and workflow details.
- Key configs: `config/remittance.php`, `config/statuses.php`, `config/activitylog.php`.
- For new modules, follow the existing Controller-Service-Model-Policy pattern.

---
_Keep these instructions up to date as the codebase evolves. For major changes, update both this file and the main README._
