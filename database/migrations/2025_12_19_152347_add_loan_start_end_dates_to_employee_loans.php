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
        Schema::table('employee_loans', function (Blueprint $table) {
            $table->date('loan_start_date')->nullable()->after('employee_id');
            $table->date('loan_end_date')->nullable()->after('loan_start_date');
            $table->foreignId('application_id')->nullable()->constrained()->nullOnDelete()->after('loan_end_date');
            
            // Index for efficient historical queries
            $table->index(['employee_id', 'loan_start_date']);
            $table->index(['employee_id', 'loan_end_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employee_loans', function (Blueprint $table) {
            $table->dropForeign(['application_id']);
            $table->dropIndex(['employee_id', 'loan_start_date']);
            $table->dropIndex(['employee_id', 'loan_end_date']);
            $table->dropColumn(['loan_start_date', 'loan_end_date', 'application_id']);
        });
    }
};
