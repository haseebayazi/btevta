<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

/**
 * ExportAuditLogs Command
 *
 * Exports audit logs for compliance and archival purposes.
 * Supports multiple formats (CSV, JSON) and filtering options.
 *
 * Compliance: Government audit trail requirements
 */
class ExportAuditLogs extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'audit:export
                            {--days=30 : Number of days to export (default: 30)}
                            {--from= : Start date (YYYY-MM-DD)}
                            {--to= : End date (YYYY-MM-DD)}
                            {--format=csv : Export format (csv, json)}
                            {--type= : Filter by log type (e.g., created, updated, deleted)}
                            {--user= : Filter by user ID}
                            {--output= : Output file path (default: storage/app/exports)}';

    /**
     * The console command description.
     */
    protected $description = 'Export audit logs for compliance (Government standard)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->info('  AUDIT LOG EXPORT - Government Compliance');
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->newLine();

        try {
            // Determine date range
            $dates = $this->getDateRange();
            $format = $this->option('format');

            $this->info("Date range: {$dates['from']->toDateString()} to {$dates['to']->toDateString()}");
            $this->info("Format: {$format}");
            $this->newLine();

            // Build query
            $query = $this->buildQuery($dates);

            // Get count first
            $count = $query->count();
            $this->info("Records to export: {$count}");

            if ($count === 0) {
                $this->warn('No records found for the specified criteria.');
                return Command::SUCCESS;
            }

            // Confirm for large exports
            if ($count > 10000 && !$this->option('no-interaction')) {
                if (!$this->confirm("This will export {$count} records. Continue?")) {
                    $this->info('Export cancelled.');
                    return Command::SUCCESS;
                }
            }

            // Generate file path
            $filePath = $this->getOutputPath($dates, $format);

            // Export data
            $this->info('Exporting...');
            $progressBar = $this->output->createProgressBar($count);

            if ($format === 'csv') {
                $this->exportToCsv($query, $filePath, $progressBar);
            } else {
                $this->exportToJson($query, $filePath, $progressBar);
            }

            $progressBar->finish();
            $this->newLine(2);

            // Get file size
            $fileSize = filesize($filePath);
            $fileSizeMb = round($fileSize / 1024 / 1024, 2);

            $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
            $this->info('  EXPORT COMPLETE');
            $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
            $this->line("  File: {$filePath}");
            $this->line("  Size: {$fileSizeMb} MB");
            $this->line("  Records: {$count}");

            // Log the export
            Log::info('Audit logs exported', [
                'file' => $filePath,
                'records' => $count,
                'date_from' => $dates['from']->toDateString(),
                'date_to' => $dates['to']->toDateString(),
                'format' => $format,
            ]);

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error("Export failed: {$e->getMessage()}");
            Log::error('Audit log export failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return Command::FAILURE;
        }
    }

    /**
     * Get the date range for export
     */
    private function getDateRange(): array
    {
        $from = $this->option('from');
        $to = $this->option('to');
        $days = (int) $this->option('days');

        if ($from && $to) {
            return [
                'from' => Carbon::parse($from)->startOfDay(),
                'to' => Carbon::parse($to)->endOfDay(),
            ];
        }

        if ($from) {
            return [
                'from' => Carbon::parse($from)->startOfDay(),
                'to' => Carbon::now()->endOfDay(),
            ];
        }

        return [
            'from' => Carbon::now()->subDays($days)->startOfDay(),
            'to' => Carbon::now()->endOfDay(),
        ];
    }

    /**
     * Build the query with filters
     */
    private function buildQuery(array $dates)
    {
        $query = DB::table('activity_log')
            ->whereBetween('created_at', [$dates['from'], $dates['to']])
            ->orderBy('created_at', 'desc');

        // Filter by type
        if ($type = $this->option('type')) {
            $query->where('description', 'like', "%{$type}%");
        }

        // Filter by user
        if ($userId = $this->option('user')) {
            $query->where('causer_id', $userId);
        }

        return $query;
    }

    /**
     * Get output file path
     */
    private function getOutputPath(array $dates, string $format): string
    {
        if ($customPath = $this->option('output')) {
            return $customPath;
        }

        $exportDir = storage_path('app/exports/audit');

        if (!is_dir($exportDir)) {
            mkdir($exportDir, 0755, true);
        }

        $filename = sprintf(
            'audit_logs_%s_to_%s_%s.%s',
            $dates['from']->format('Ymd'),
            $dates['to']->format('Ymd'),
            now()->format('His'),
            $format
        );

        return $exportDir . '/' . $filename;
    }

    /**
     * Export to CSV format
     */
    private function exportToCsv($query, string $filePath, $progressBar): void
    {
        $handle = fopen($filePath, 'w');

        // Write header
        fputcsv($handle, [
            'ID',
            'Log Name',
            'Description',
            'Subject Type',
            'Subject ID',
            'Causer Type',
            'Causer ID',
            'Properties',
            'Created At',
            'Updated At',
        ]);

        // Write data in chunks
        $query->orderBy('id')->chunk(1000, function ($logs) use ($handle, $progressBar) {
            foreach ($logs as $log) {
                fputcsv($handle, [
                    $log->id,
                    $log->log_name ?? '',
                    $log->description ?? '',
                    $log->subject_type ?? '',
                    $log->subject_id ?? '',
                    $log->causer_type ?? '',
                    $log->causer_id ?? '',
                    $log->properties ?? '{}',
                    $log->created_at ?? '',
                    $log->updated_at ?? '',
                ]);
                $progressBar->advance();
            }
        });

        fclose($handle);
    }

    /**
     * Export to JSON format
     */
    private function exportToJson($query, string $filePath, $progressBar): void
    {
        $handle = fopen($filePath, 'w');
        fwrite($handle, "[\n");

        $first = true;

        // Write data in chunks
        $query->orderBy('id')->chunk(1000, function ($logs) use ($handle, $progressBar, &$first) {
            foreach ($logs as $log) {
                if (!$first) {
                    fwrite($handle, ",\n");
                }
                $first = false;

                $entry = [
                    'id' => $log->id,
                    'log_name' => $log->log_name,
                    'description' => $log->description,
                    'subject' => [
                        'type' => $log->subject_type,
                        'id' => $log->subject_id,
                    ],
                    'causer' => [
                        'type' => $log->causer_type,
                        'id' => $log->causer_id,
                    ],
                    'properties' => json_decode($log->properties ?? '{}', true),
                    'created_at' => $log->created_at,
                    'updated_at' => $log->updated_at,
                ];

                fwrite($handle, json_encode($entry, JSON_PRETTY_PRINT));
                $progressBar->advance();
            }
        });

        fwrite($handle, "\n]");
        fclose($handle);
    }
}
