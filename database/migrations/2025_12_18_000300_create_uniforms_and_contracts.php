<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('uniform_products', function (Blueprint $table) {
            $table->id();
            $table->string('product_code')->unique();
            $table->string('name');
            $table->enum('category', ['top', 'bottom', 'shoe', 'other'])->default('other');
            $table->string('size');
            $table->enum('gender', ['male', 'female', 'unisex'])->default('unisex');
            $table->unsignedInteger('stock_quantity')->default(0);
            $table->string('image_path')->nullable();
            $table->timestamps();
        });

        Schema::create('uniform_recommendations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('medical_institution_id')->constrained()->cascadeOnDelete();
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('job_type_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('uniform_product_id')->constrained()->cascadeOnDelete();
            $table->boolean('is_required')->default(false);
            $table->boolean('is_recommended')->default(true);
            $table->timestamps();
        });

        Schema::create('contracts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('medical_institution_id')->constrained()->cascadeOnDelete();
            $table->enum('billing_model', ['fixed_per_person', 'point_usage'])->default('fixed_per_person');
            $table->unsignedInteger('per_person_loan_limit')->nullable();
            $table->decimal('point_unit_price', 10, 2)->nullable();
            $table->date('valid_from')->nullable();
            $table->date('valid_until')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contracts');
        Schema::dropIfExists('uniform_recommendations');
        Schema::dropIfExists('uniform_products');
    }
};


