<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('admin_users', function (Blueprint $table) {
            $table->timestamp('blocked_at')->nullable()->after('role');
            $table->text('blocked_reason')->nullable()->after('blocked_at');
            $table->timestamp('deleted_at')->nullable()->after('blocked_reason');
            $table->text('deleted_reason')->nullable()->after('deleted_at');
        });
    }

    public function down(): void
    {
        Schema::table('admin_users', function (Blueprint $table) {
            $table->dropColumn(['blocked_at', 'blocked_reason', 'deleted_at', 'deleted_reason']);
        });
    }
};
