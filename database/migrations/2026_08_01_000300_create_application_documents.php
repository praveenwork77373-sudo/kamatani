<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            if (! Schema::hasColumn('applications', 'memo1')) {
                $table->string('memo1', 500)->nullable()->after('special_approval_reason');
            }
            if (! Schema::hasColumn('applications', 'memo2')) {
                $table->string('memo2', 500)->nullable()->after('memo1');
            }
        });

        if (! Schema::hasTable('application_documents')) {
            Schema::create('application_documents', function (Blueprint $table) {
                $table->id();
                $table->foreignId('application_id')->constrained()->cascadeOnDelete();
                $table->string('form_type', 50); // order_slip, delivery_notice, shipping_instruction, work_slip (+ legacy shipping_sales / shipping_warehouse)
                $table->unsignedInteger('version')->default(1);
                $table->boolean('is_latest')->default(true);
                $table->string('file_path');
                $table->string('original_name')->nullable();
                $table->foreignId('issued_by_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('issued_at')->nullable();
                $table->timestamps();

                $table->index(['application_id', 'form_type', 'is_latest']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('application_documents');

        Schema::table('applications', function (Blueprint $table) {
            foreach (['memo1', 'memo2'] as $col) {
                if (Schema::hasColumn('applications', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
