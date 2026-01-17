<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Create document_tags table
        Schema::create('document_tags', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('slug')->unique();
            $table->string('color', 7)->default('#3b82f6'); // Default blue color
            $table->text('description')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->index('name');
            $table->index('slug');

            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');
        });

        // Create pivot table for many-to-many relationship
        Schema::create('document_tag_pivot', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('document_id');
            $table->unsignedBigInteger('tag_id');
            $table->timestamps();

            $table->unique(['document_id', 'tag_id']);
            $table->index('document_id');
            $table->index('tag_id');

            $table->foreign('document_id')->references('id')->on('document_archives')->onDelete('cascade');
            $table->foreign('tag_id')->references('id')->on('document_tags')->onDelete('cascade');
        });

        // Add tags JSON column to document_archives if it doesn't exist
        // This is for backward compatibility with old tags system
        Schema::table('document_archives', function (Blueprint $table) {
            if (!Schema::hasColumn('document_archives', 'tags')) {
                $table->json('tags')->nullable()->after('description');
            }
        });

        // Seed some common tags
        DB::table('document_tags')->insert([
            [
                'name' => 'Urgent',
                'slug' => 'urgent',
                'color' => '#ef4444',
                'description' => 'Documents requiring immediate attention',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Verified',
                'slug' => 'verified',
                'color' => '#22c55e',
                'description' => 'Documents that have been verified',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Pending Review',
                'slug' => 'pending-review',
                'color' => '#f59e0b',
                'description' => 'Documents awaiting review',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Expired',
                'slug' => 'expired',
                'color' => '#dc2626',
                'description' => 'Documents that have expired',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Personal',
                'slug' => 'personal',
                'color' => '#8b5cf6',
                'description' => 'Personal documents (CNIC, Passport, etc.)',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Medical',
                'slug' => 'medical',
                'color' => '#ec4899',
                'description' => 'Medical certificates and records',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Training',
                'slug' => 'training',
                'color' => '#3b82f6',
                'description' => 'Training-related documents',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Visa',
                'slug' => 'visa',
                'color' => '#06b6d4',
                'description' => 'Visa and immigration documents',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Confidential',
                'slug' => 'confidential',
                'color' => '#64748b',
                'description' => 'Confidential documents with restricted access',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Archived',
                'slug' => 'archived',
                'color' => '#9ca3af',
                'description' => 'Archived documents for historical reference',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('document_tag_pivot');
        Schema::dropIfExists('document_tags');

        Schema::table('document_archives', function (Blueprint $table) {
            if (Schema::hasColumn('document_archives', 'tags')) {
                $table->dropColumn('tags');
            }
        });
    }
};
