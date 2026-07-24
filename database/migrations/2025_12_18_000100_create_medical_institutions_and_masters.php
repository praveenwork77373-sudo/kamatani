<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('medical_institutions', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->enum('billing_model', ['fixed_per_person', 'point_usage'])->default('fixed_per_person');
            $table->string('notification_email')->nullable();
            $table->unsignedInteger('per_person_loan_limit')->nullable();
            $table->decimal('point_unit_price', 10, 2)->nullable();
            $table->timestamps();
        });

        Schema::create('departments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('medical_institution_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('code')->nullable();
            $table->timestamps();
        });

        Schema::create('job_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('medical_institution_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('code')->nullable();
            $table->timestamps();
        });

        Schema::create('delivery_locations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('medical_institution_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('note')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('delivery_locations');
        Schema::dropIfExists('job_types');
        Schema::dropIfExists('departments');
        Schema::dropIfExists('medical_institutions');
    }
};


