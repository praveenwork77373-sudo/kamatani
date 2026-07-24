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
        Schema::table('contracts', function (Blueprint $table) {
            // Add contract_type field (FIXED or POINT_BASED)
            $table->enum('contract_type', ['FIXED', 'POINT_BASED'])->default('FIXED')->after('medical_institution_id');
            
            // Rename valid_from/valid_until to contract_start_date/contract_end_date for clarity
            // But keep them as they are already correct
            
            // Add loan_limit_per_item (optional per-item limit)
            $table->unsignedInteger('loan_limit_per_item')->nullable()->after('per_person_loan_limit');
            
            // Add fixed_unit_price for FIXED contracts
            $table->decimal('fixed_unit_price', 10, 2)->nullable()->after('point_unit_price');
            
            // Add billing rules as JSON
            $table->json('billing_rules')->nullable()->after('fixed_unit_price');
            
            // Add inventory constraints as JSON
            $table->json('inventory_constraints')->nullable()->after('billing_rules');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            $table->dropColumn([
                'contract_type',
                'loan_limit_per_item',
                'fixed_unit_price',
                'billing_rules',
                'inventory_constraints',
            ]);
        });
    }
};
