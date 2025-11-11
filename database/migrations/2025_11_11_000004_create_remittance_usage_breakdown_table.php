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
        Schema::create('remittance_usage_breakdown', function (Blueprint $table) {
            $table->id();

            // Foreign Keys
            $table->foreignId('remittance_id')->constrained('remittances')->onDelete('cascade');

            // Usage Details
            $table->enum('usage_category', [
                'education',
                'health',
                'rent',
                'food',
                'savings',
                'debt_repayment',
                'family_support',
                'business_investment',
                'utilities',
                'transportation',
                'clothing',
                'other'
            ]);
            $table->decimal('amount', 10, 2); // Amount allocated to this category
            $table->decimal('percentage', 5, 2)->nullable(); // Percentage of total
            $table->text('description')->nullable(); // Specific usage details
            $table->boolean('has_proof')->default(false); // Has proof for this usage

            $table->timestamps();

            // Indexes
            $table->index('remittance_id');
            $table->index('usage_category');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('remittance_usage_breakdown');
    }
};
