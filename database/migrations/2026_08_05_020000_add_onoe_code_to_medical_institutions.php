<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('medical_institutions', function (Blueprint $table) {
            $table->string('onoe_code', 50)->nullable()->after('code');
        });
    }

    public function down(): void
    {
        Schema::table('medical_institutions', function (Blueprint $table) {
            $table->dropColumn('onoe_code');
        });
    }
};
