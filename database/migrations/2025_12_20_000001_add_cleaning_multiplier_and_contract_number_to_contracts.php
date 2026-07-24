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
        // Check if columns exist before adding
        $hasContractNumber = Schema::hasColumn('contracts', 'contract_number');
        $hasCleaningMultiplier = Schema::hasColumn('contracts', 'cleaning_multiplier');
        
        Schema::table('contracts', function (Blueprint $table) use ($hasContractNumber, $hasCleaningMultiplier) {
            // Add contract_number field
            if (!$hasContractNumber) {
                $table->string('contract_number')->nullable()->unique()->after('medical_institution_id');
            }
            
            // Add cleaning_multiplier for calculating cleaning count from return points
            if (!$hasCleaningMultiplier) {
                $table->decimal('cleaning_multiplier', 5, 2)->default(1.67)->after('fixed_unit_price')
                    ->comment('Multiplier to calculate cleaning count from return points (default: 1.67)');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            if (Schema::hasColumn('contracts', 'contract_number')) {
                $table->dropColumn('contract_number');
            }
            if (Schema::hasColumn('contracts', 'cleaning_multiplier')) {
                $table->dropColumn('cleaning_multiplier');
            }
        });
    }
};

