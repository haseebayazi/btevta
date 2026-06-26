<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * ResetForLaunch Command
 *
 * Prepares an existing (already-seeded / staging) database for go-live by
 * removing all candidate and transactional data while PRESERVING:
 *   - Admin accounts (and, by default, all other user accounts)
 *   - System reference / lookup data (countries, payment methods, programs,
 *     courses, document checklists, complaint templates, document tags,
 *     system settings)
 *
 * By default it removes only candidate + transactional records. Use the
 * flags to widen the scope:
 *   --with-users : also delete non-admin user accounts
 *   --with-org   : also delete org master data (campuses, batches, trades,
 *                  OEPs, visa partners, instructors, employers)
 *
 * Always preview first:
 *   php artisan data:reset-for-launch --dry-run
 */
class ResetForLaunch extends Command
{
    protected $signature = 'data:reset-for-launch
                            {--dry-run : Show what would be deleted without deleting anything}
                            {--force : Skip confirmation prompts}
                            {--with-users : Also delete non-admin user accounts}
                            {--with-org : Also delete org master data (campuses, batches, trades, OEPs, visa partners, instructors, employers)}';

    protected $description = 'Remove candidate & transactional data to make the database data-ready for launch (preserves admins + reference data)';

    /** Roles that are always retained. */
    private const PRESERVED_ROLES = [
        User::ROLE_SUPER_ADMIN,
        User::ROLE_ADMIN,
        User::ROLE_PROJECT_DIRECTOR,
    ];

    /**
     * Candidate + transactional tables (always purged). Ordered children-first
     * so the purge also works on connections without FK toggling.
     */
    private const TRANSACTIONAL_TABLES = [
        // Document / evidence children
        'document_tag_pivot',
        'pre_departure_document_pages',
        'success_story_evidence',
        'complaint_evidence',
        'complaint_updates',
        'remittance_receipts',
        'remittance_usage_breakdown',
        'remittance_beneficiaries',
        'remittance_alerts',
        'employer_documents',
        // Training children
        'training_attendances',
        'training_assessments',
        'training_certificates',
        'training_schedules',
        'class_enrollments',
        'trainings',
        'training_classes',
        // Candidate lifecycle records
        'candidate_screenings',
        'registration_documents',
        'registrations',
        'pre_departure_documents',
        'document_renewal_requests',
        'next_of_kins',
        'undertakings',
        'candidate_licenses',
        'candidate_courses',
        'visa_processes',
        'post_departure_details',
        'company_switch_logs',
        'employment_histories',
        'candidate_employer',
        'departures',
        'success_stories',
        'remittances',
        'complaints',
        'correspondences',
        'correspondence',
        'document_archives',
        'candidate_status_logs',
        'equipment_usage_logs',
        'campus_kpis',
        'scheduled_notifications',
        'notifications',
        'audit_logs',
        // Parent
        'candidates',
    ];

    /** Org master tables (purged only with --with-org), children-first. */
    private const ORG_TABLES = [
        'campus_equipment',
        'batches',
        'instructors',
        'employers',
        'trades',
        'oeps',
        'visa_partners',
        'campuses',
    ];

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $withUsers = (bool) $this->option('with-users');
        $withOrg = (bool) $this->option('with-org');

        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->info('  RESET FOR LAUNCH — make database data-ready');
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->newLine();
        $this->line('Environment : '.app()->environment());
        $this->line('Mode        : '.($dryRun ? 'DRY RUN (no deletions)' : 'LIVE'));
        $this->line('Delete users: '.($withUsers ? 'yes (non-admins)' : 'no'));
        $this->line('Delete org  : '.($withOrg ? 'yes' : 'no'));
        $this->newLine();

        // Build the working table list (only tables that actually exist).
        $tables = array_values(array_filter(
            self::TRANSACTIONAL_TABLES,
            fn ($t) => Schema::hasTable($t)
        ));

        if ($withOrg) {
            foreach (self::ORG_TABLES as $t) {
                if (Schema::hasTable($t)) {
                    $tables[] = $t;
                }
            }
        }

        // Preview counts.
        $rows = [];
        $grandTotal = 0;
        foreach ($tables as $table) {
            $count = DB::table($table)->count();
            if ($count > 0) {
                $rows[] = [$table, number_format($count)];
                $grandTotal += $count;
            }
        }

        $nonAdminUsers = User::whereNotIn('role', self::PRESERVED_ROLES)->count();
        if ($withUsers && $nonAdminUsers > 0) {
            $rows[] = ['users (non-admin)', number_format($nonAdminUsers)];
            $grandTotal += $nonAdminUsers;
        }

        if (empty($rows)) {
            $this->info('Nothing to delete — database is already clean. ✅');

            return self::SUCCESS;
        }

        $this->table(['Table', 'Rows to delete'], $rows);
        $this->warn('Total rows to delete: '.number_format($grandTotal));
        $this->line('Preserved: admin accounts'.($withUsers ? '' : ' + all other users')
            .', reference/lookup data'.($withOrg ? '' : ', org master data').'.');
        $this->newLine();

        if ($dryRun) {
            $this->info('DRY RUN complete — no data was deleted.');

            return self::SUCCESS;
        }

        // Confirmations.
        if (! $this->option('force')) {
            if (app()->environment('production')) {
                $this->error('⚠️  PRODUCTION environment detected. This permanently deletes data.');
                if (! $this->confirm('Are you ABSOLUTELY SURE you want to proceed in PRODUCTION?')) {
                    $this->info('Cancelled.');

                    return self::SUCCESS;
                }
            }
            if (! $this->confirm('This action CANNOT be undone. Continue?')) {
                $this->info('Cancelled.');

                return self::SUCCESS;
            }
        }

        $driver = DB::connection()->getDriverName();
        $deleted = [];

        $this->toggleForeignKeys($driver, false);

        try {
            foreach ($tables as $table) {
                $count = DB::table($table)->delete();
                if ($count > 0) {
                    $deleted[$table] = $count;
                }
            }

            if ($withUsers) {
                $count = User::whereNotIn('role', self::PRESERVED_ROLES)->forceDelete();
                if ($count > 0) {
                    $deleted['users (non-admin)'] = $count;
                }
            }
        } finally {
            $this->toggleForeignKeys($driver, true);
        }

        Log::warning('data:reset-for-launch executed', [
            'environment' => app()->environment(),
            'with_users' => $withUsers,
            'with_org' => $withOrg,
            'deleted' => $deleted,
            'by' => 'console',
        ]);

        $this->newLine();
        $this->info('✅ Reset complete. Database is ready for real candidate data.');
        $this->line('Remember to clear caches: php artisan cache:clear');

        return self::SUCCESS;
    }

    private function toggleForeignKeys(string $driver, bool $on): void
    {
        try {
            match ($driver) {
                'mysql', 'mariadb' => DB::statement('SET FOREIGN_KEY_CHECKS='.($on ? '1' : '0')),
                'sqlite' => DB::statement('PRAGMA foreign_keys = '.($on ? 'ON' : 'OFF')),
                'pgsql' => null, // handled by session_replication_role if needed
                default => null,
            };
        } catch (\Throwable $e) {
            // Non-fatal: deletion is ordered children-first as a fallback.
            Log::info('toggleForeignKeys skipped', ['driver' => $driver, 'error' => $e->getMessage()]);
        }
    }
}
