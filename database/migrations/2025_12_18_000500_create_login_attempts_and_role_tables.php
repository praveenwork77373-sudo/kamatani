<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('type')->default('customer_admin'); // customer_admin, department_admin, kamatani_admin
            $table->timestamp('locked_until')->nullable();
        });

        Schema::create('customer_users', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('medical_institution_id')->constrained()->cascadeOnDelete();
            $table->enum('role', ['institution_admin', 'department_admin']);
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('admin_users', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->enum('role', ['system_admin', 'operation', 'sales', 'accounting']);
            $table->timestamps();
        });

        Schema::create('login_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('email')->nullable();
            $table->boolean('is_success')->default(false);
            $table->timestamp('attempted_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('login_attempts');
        Schema::dropIfExists('admin_users');
        Schema::dropIfExists('customer_users');

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['type', 'locked_until']);
        });
    }
};


