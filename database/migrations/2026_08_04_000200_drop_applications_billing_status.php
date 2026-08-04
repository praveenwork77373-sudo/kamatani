<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('applications') && Schema::hasColumn('applications', 'billing_status')) {
            Schema::table('applications', function (Blueprint $table) {
                $table->dropColumn('billing_status');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('applications') && ! Schema::hasColumn('applications', 'billing_status')) {
            Schema::table('applications', function (Blueprint $table) {
                $table->enum('billing_status', ['waiting', 'approved', 'rejected'])->nullable()->default('waiting')->after('status');
            });
        }
    }
};
