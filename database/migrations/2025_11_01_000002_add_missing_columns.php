<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Add to oeps - with proper NULL handling
        Schema::table('oeps', function (Blueprint $table) {
            if (!Schema::hasColumn('oeps', 'code')) {
                $table->string('code')->nullable()->after('name');
            }
            if (!Schema::hasColumn('oeps', 'country')) {
                $table->string('country')->nullable()->after('address');
            }
            if (!Schema::hasColumn('oeps', 'city')) {
                $table->string('city')->nullable()->after('country');
            }
        });

        // Fix duplicate code values - fill empty ones with unique values
        try {
            $oeps = DB::table('oeps')->whereNull('code')->orWhere('code', '')->get();
            foreach ($oeps as $oep) {
                DB::table('oeps')
                    ->where('id', $oep->id)
                    ->update(['code' => 'OEP-' . $oep->id]);
            }
        } catch (\Exception $e) {
            // Silently continue if this fails
        }

        // Now add unique constraint
        Schema::table('oeps', function (Blueprint $table) {
            if (Schema::hasColumn('oeps', 'code')) {
                try {
                    DB::statement('ALTER TABLE oeps ADD UNIQUE KEY oeps_code_unique (code)');
                } catch (\Exception $e) {
                    // Unique constraint might already exist
                }
            }
        });

        // Add to batches
        Schema::table('batches', function (Blueprint $table) {
            if (!Schema::hasColumn('batches', 'batch_code')) {
                $table->string('batch_code')->nullable()->after('id');
            }
            if (!Schema::hasColumn('batches', 'trainer_id')) {
                $table->unsignedBigInteger('trainer_id')->nullable()->after('oep_id');
            }
            if (!Schema::hasColumn('batches', 'trainer_name')) {
                $table->string('trainer_name')->nullable()->after('trainer_id');
            }
        });

        // Fix duplicate batch_code values
        try {
            $batches = DB::table('batches')->whereNull('batch_code')->orWhere('batch_code', '')->get();
            foreach ($batches as $batch) {
                DB::table('batches')
                    ->where('id', $batch->id)
                    ->update(['batch_code' => 'BATCH-' . $batch->id]);
            }
        } catch (\Exception $e) {
            // Silently continue if this fails
        }

        // Now add unique constraint to batch_code
        Schema::table('batches', function (Blueprint $table) {
            if (Schema::hasColumn('batches', 'batch_code')) {
                try {
                    DB::statement('ALTER TABLE batches ADD UNIQUE KEY batches_batch_code_unique (batch_code)');
                } catch (\Exception $e) {
                    // Unique constraint might already exist
                }
            }
        });

        // Add to users
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'phone')) {
                $table->string('phone')->nullable();
            }
        });

        // Add to complaints
        Schema::table('complaints', function (Blueprint $table) {
            if (!Schema::hasColumn('complaints', 'assigned_to')) {
                $table->unsignedBigInteger('assigned_to')->nullable();
            }
            if (!Schema::hasColumn('complaints', 'priority')) {
                $table->string('priority')->default('medium');
            }
        });

        // Fix correspondence
        if (Schema::hasTable('correspondence')) {
            Schema::table('correspondence', function (Blueprint $table) {
                if (!Schema::hasColumn('correspondence', 'requires_reply')) {
                    $table->boolean('requires_reply')->default(false);
                }
                if (!Schema::hasColumn('correspondence', 'replied')) {
                    $table->boolean('replied')->default(false);
                }
            });
        }
    }

    public function down(): void
    {
        Schema::table('correspondence', function (Blueprint $table) {
            if (Schema::hasColumn('correspondence', 'requires_reply')) {
                $table->dropColumn('requires_reply');
            }
            if (Schema::hasColumn('correspondence', 'replied')) {
                $table->dropColumn('replied');
            }
        });

        Schema::table('complaints', function (Blueprint $table) {
            if (Schema::hasColumn('complaints', 'assigned_to')) {
                $table->dropColumn('assigned_to');
            }
            if (Schema::hasColumn('complaints', 'priority')) {
                $table->dropColumn('priority');
            }
        });

        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'phone')) {
                $table->dropColumn('phone');
            }
        });

        Schema::table('batches', function (Blueprint $table) {
            if (Schema::hasColumn('batches', 'batch_code')) {
                try {
                    DB::statement('ALTER TABLE batches DROP INDEX batches_batch_code_unique');
                } catch (\Exception $e) {
                    // Index might not exist
                }
                $table->dropColumn('batch_code');
            }
            if (Schema::hasColumn('batches', 'trainer_id')) {
                $table->dropColumn('trainer_id');
            }
            if (Schema::hasColumn('batches', 'trainer_name')) {
                $table->dropColumn('trainer_name');
            }
        });

        Schema::table('oeps', function (Blueprint $table) {
            if (Schema::hasColumn('oeps', 'code')) {
                try {
                    DB::statement('ALTER TABLE oeps DROP INDEX oeps_code_unique');
                } catch (\Exception $e) {
                    // Index might not exist
                }
                $table->dropColumn('code');
            }
            if (Schema::hasColumn('oeps', 'country')) {
                $table->dropColumn('country');
            }
            if (Schema::hasColumn('oeps', 'city')) {
                $table->dropColumn('city');
            }
        });
    }
};