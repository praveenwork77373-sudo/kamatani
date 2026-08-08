<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('contract_plans', 'work_style')) {
            Schema::table('contract_plans', function (Blueprint $table) {
                $table->dropColumn('work_style');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasColumn('contract_plans', 'work_style')) {
            Schema::table('contract_plans', function (Blueprint $table) {
                $table->string('work_style')->nullable()->after('job_type_label');
            });
        }
    }
};
