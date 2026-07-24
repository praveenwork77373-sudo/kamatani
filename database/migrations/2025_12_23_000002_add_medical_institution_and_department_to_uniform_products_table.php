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
        Schema::table('uniform_products', function (Blueprint $table) {
            $table->foreignId('medical_institution_id')->nullable()->after('id')->constrained('medical_institutions')->nullOnDelete();
            $table->foreignId('department_id')->nullable()->after('medical_institution_id')->constrained('departments')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('uniform_products', function (Blueprint $table) {
            $table->dropForeign(['medical_institution_id']);
            $table->dropForeign(['department_id']);
            $table->dropColumn(['medical_institution_id', 'department_id']);
        });
    }
};

