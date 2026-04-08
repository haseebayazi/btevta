<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('success_stories', function (Blueprint $table) {
            if (! Schema::hasColumn('success_stories', 'story_type')) {
                $table->enum('story_type', ['employment', 'career_growth', 'skill_achievement', 'remittance', 'other'])
                    ->default('employment')
                    ->after('candidate_id');
            }
            if (! Schema::hasColumn('success_stories', 'headline')) {
                $table->string('headline', 200)->nullable()->after('story_type');
            }
            if (! Schema::hasColumn('success_stories', 'employer_name')) {
                $table->string('employer_name', 200)->nullable()->after('written_note');
            }
            if (! Schema::hasColumn('success_stories', 'position_achieved')) {
                $table->string('position_achieved', 100)->nullable()->after('employer_name');
            }
            if (! Schema::hasColumn('success_stories', 'country_id')) {
                $table->foreignId('country_id')->nullable()->after('position_achieved')
                    ->constrained()->nullOnDelete();
            }
            if (! Schema::hasColumn('success_stories', 'salary_achieved')) {
                $table->decimal('salary_achieved', 12, 2)->nullable()->after('country_id');
            }
            if (! Schema::hasColumn('success_stories', 'salary_currency')) {
                $table->string('salary_currency', 10)->default('SAR')->after('salary_achieved');
            }
            if (! Schema::hasColumn('success_stories', 'employment_start_date')) {
                $table->date('employment_start_date')->nullable()->after('salary_currency');
            }
            if (! Schema::hasColumn('success_stories', 'time_to_employment_days')) {
                $table->integer('time_to_employment_days')->nullable()->after('employment_start_date');
            }
            if (! Schema::hasColumn('success_stories', 'views_count')) {
                $table->integer('views_count')->default(0)->after('is_featured');
            }
            if (! Schema::hasColumn('success_stories', 'published_at')) {
                $table->timestamp('published_at')->nullable()->after('views_count');
            }
            if (! Schema::hasColumn('success_stories', 'status')) {
                $table->enum('status', ['draft', 'pending_review', 'approved', 'published', 'rejected'])
                    ->default('draft')
                    ->after('published_at');
            }
            if (! Schema::hasColumn('success_stories', 'approved_by')) {
                $table->foreignId('approved_by')->nullable()->after('status')
                    ->constrained('users')->nullOnDelete();
            }
            if (! Schema::hasColumn('success_stories', 'approved_at')) {
                $table->timestamp('approved_at')->nullable()->after('approved_by');
            }
            if (! Schema::hasColumn('success_stories', 'rejection_reason')) {
                $table->text('rejection_reason')->nullable()->after('approved_at');
            }
        });

        // Add indexes (wrapped in try/catch for idempotency across DB drivers)
        try {
            Schema::table('success_stories', function (Blueprint $table) {
                $table->index('story_type');
            });
        } catch (\Throwable $e) {
            // Index already exists — skip
        }
        try {
            Schema::table('success_stories', function (Blueprint $table) {
                $table->index('status');
            });
        } catch (\Throwable $e) {
            // Index already exists — skip
        }
    }

    public function down(): void
    {
        Schema::table('success_stories', function (Blueprint $table) {
            $table->dropForeign(['country_id']);
            $table->dropForeign(['approved_by']);
            $cols = [
                'story_type', 'headline', 'employer_name', 'position_achieved',
                'country_id', 'salary_achieved', 'salary_currency',
                'employment_start_date', 'time_to_employment_days',
                'views_count', 'published_at', 'status',
                'approved_by', 'approved_at', 'rejection_reason',
            ];
            foreach ($cols as $col) {
                if (Schema::hasColumn('success_stories', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
