<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('medical_institution_id')->constrained()->cascadeOnDelete();
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['new_loan', 'resize', 'return', 'exchange', 'transfer']);
            $table->enum('status', [
                'pending',
                'waiting_special_approval',
                'approved',
                'rejected',
                'cancel_request',
                'cancel_approved',
                'cancel_rejected',
            ])->default('pending');
            $table->date('start_date')->nullable();
            $table->date('return_date')->nullable();
            $table->foreignId('delivery_location_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('requested_by_user_id')->constrained('users')->cascadeOnDelete();
            $table->text('special_approval_reason')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('approved_by_admin_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('application_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->constrained()->cascadeOnDelete();
            $table->foreignId('uniform_product_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('quantity')->default(1);
            $table->enum('action', ['loan', 'return', 'exchange_out', 'exchange_in', 'transfer_change'])->default('loan');
            $table->timestamps();
        });

        Schema::create('employee_loans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('uniform_product_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('quantity')->default(0);
            $table->enum('status', ['in_use', 'scheduled_return', 'returned'])->default('in_use');
            $table->timestamps();
        });

        Schema::create('billing_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('medical_institution_id')->constrained()->cascadeOnDelete();
            $table->date('target_date');
            $table->json('data');
            $table->foreignId('generated_by_admin_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('billing_snapshots');
        Schema::dropIfExists('employee_loans');
        Schema::dropIfExists('application_items');
        Schema::dropIfExists('applications');
    }
};


