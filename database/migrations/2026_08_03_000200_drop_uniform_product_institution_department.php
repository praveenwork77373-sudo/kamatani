<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Products are a global catalog. Hospital/dept scoping is via Contract Plan links,
 * not columns on uniform_products (same leftover pattern as institution on plans).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('uniform_products', function (Blueprint $table) {
            if (Schema::hasColumn('uniform_products', 'department_id')) {
                $table->dropConstrainedForeignId('department_id');
            }
            if (Schema::hasColumn('uniform_products', 'medical_institution_id')) {
                $table->dropConstrainedForeignId('medical_institution_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('uniform_products', function (Blueprint $table) {
            if (! Schema::hasColumn('uniform_products', 'medical_institution_id')) {
                $table->foreignId('medical_institution_id')->nullable()->constrained()->nullOnDelete();
            }
            if (! Schema::hasColumn('uniform_products', 'department_id')) {
                $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            }
        });
    }
};
