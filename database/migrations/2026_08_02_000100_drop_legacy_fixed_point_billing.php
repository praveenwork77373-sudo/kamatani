<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Remove legacy FIXED/POINT billing columns and contracts table.
 * Billing is Contract Plan only (assigned staff × unit_price_ex_tax).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('medical_institutions', function (Blueprint $table) {
            if (Schema::hasColumn('medical_institutions', 'billing_model')) {
                $table->dropColumn('billing_model');
            }
            if (Schema::hasColumn('medical_institutions', 'per_person_loan_limit')) {
                $table->dropColumn('per_person_loan_limit');
            }
            if (Schema::hasColumn('medical_institutions', 'point_unit_price')) {
                $table->dropColumn('point_unit_price');
            }
        });

        Schema::dropIfExists('contracts');
    }

    public function down(): void
    {
        Schema::table('medical_institutions', function (Blueprint $table) {
            if (! Schema::hasColumn('medical_institutions', 'billing_model')) {
                $table->enum('billing_model', ['fixed_per_person', 'point_usage'])->default('fixed_per_person')->after('code');
            }
            if (! Schema::hasColumn('medical_institutions', 'per_person_loan_limit')) {
                $table->unsignedInteger('per_person_loan_limit')->nullable()->after('notification_email');
            }
            if (! Schema::hasColumn('medical_institutions', 'point_unit_price')) {
                $table->decimal('point_unit_price', 10, 2)->nullable()->after('per_person_loan_limit');
            }
        });

        if (! Schema::hasTable('contracts')) {
            Schema::create('contracts', function (Blueprint $table) {
                $table->id();
                $table->foreignId('medical_institution_id')->constrained()->cascadeOnDelete();
                $table->enum('billing_model', ['fixed_per_person', 'point_usage'])->default('fixed_per_person');
                $table->enum('contract_type', ['FIXED', 'POINT_BASED'])->default('FIXED');
                $table->unsignedInteger('per_person_loan_limit')->nullable();
                $table->unsignedInteger('loan_limit_per_item')->nullable();
                $table->decimal('point_unit_price', 10, 2)->nullable();
                $table->decimal('fixed_unit_price', 10, 2)->nullable();
                $table->decimal('cleaning_multiplier', 5, 2)->default(1.67);
                $table->string('contract_number')->nullable();
                $table->json('billing_rules')->nullable();
                $table->json('inventory_constraints')->nullable();
                $table->date('valid_from')->nullable();
                $table->date('valid_until')->nullable();
                $table->timestamps();
            });
        }
    }
};
