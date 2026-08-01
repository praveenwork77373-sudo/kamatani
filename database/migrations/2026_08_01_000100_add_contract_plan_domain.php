<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * SPEC 2026-07-31: Contract Plan–centric domain.
 * Adds makers, contract plans, customer plans, employee assignment,
 * employee body/inseam/dates, hospital closing day, and contract_plan_manager role.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('makers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('contract_plans', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->unsignedInteger('rentable_points')->default(0)->comment('Rentable piece/set count');
            $table->decimal('unit_price_ex_tax', 10, 2)->default(0)->comment('Monthly amount ex-tax per assignment');
            $table->string('job_type_label')->nullable()->comment('Display label e.g. 医師（男女）');
            $table->string('work_style')->nullable()->comment('e.g. 常勤 / 非常勤');
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('contract_plan_uniform_product', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contract_plan_id')->constrained()->cascadeOnDelete();
            $table->foreignId('uniform_product_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['contract_plan_id', 'uniform_product_id'], 'plan_product_unique');
        });

        Schema::create('customer_contract_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('medical_institution_id')->constrained()->cascadeOnDelete();
            $table->foreignId('contract_plan_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('contract_count')->default(0)->comment('Contracted headcount slots for this plan');
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->enum('status', ['draft', 'confirmed'])->default('draft');
            $table->timestamp('confirmed_at')->nullable();
            $table->foreignId('confirmed_by_admin_user_id')->nullable()->constrained('admin_users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['medical_institution_id', 'status']);
        });

        Schema::create('employee_contract_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_contract_plan_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_position_id')->nullable()->constrained()->nullOnDelete()
                ->comment('Optional: plan linked to a concurrent affiliation');
            $table->date('assigned_from')->nullable();
            $table->date('assigned_until')->nullable();
            $table->timestamps();

            $table->unique(['employee_id', 'customer_contract_plan_id', 'employee_position_id'], 'emp_ccp_pos_unique');
        });

        Schema::table('medical_institutions', function (Blueprint $table) {
            $table->unsignedTinyInteger('billing_closing_day')->default(1)->after('point_unit_price')
                ->comment('Day of month for billing close (1–28 typical)');
            $table->string('sales_person_name')->nullable()->after('billing_closing_day');
        });

        Schema::table('employees', function (Blueprint $table) {
            $table->unsignedSmallInteger('inseam_cm')->nullable()->after('shoe_size');
            $table->string('body_type')->nullable()->after('inseam_cm')->comment('e.g. size 7, 11');
            $table->date('join_date')->nullable()->after('status');
            $table->date('retire_date')->nullable()->after('join_date');
        });

        Schema::table('uniform_products', function (Blueprint $table) {
            $table->foreignId('maker_id')->nullable()->after('id')->constrained('makers')->nullOnDelete();
            $table->string('maker_product_code')->nullable()->after('product_code');
            $table->string('color_name')->nullable()->after('name');
        });

        // Add contract_plan_manager to admin_users.role
        $driver = Schema::getConnection()->getDriverName();
        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE admin_users MODIFY COLUMN role ENUM('system_admin','operation','sales','accounting','contract_plan_manager') NOT NULL");
        } elseif ($driver === 'pgsql') {
            // Laravel enums are CHECK constraints on Postgres; drop and recreate allowing new role
            DB::statement('ALTER TABLE admin_users DROP CONSTRAINT IF EXISTS admin_users_role_check');
            DB::statement("ALTER TABLE admin_users ADD CONSTRAINT admin_users_role_check CHECK (role::text = ANY (ARRAY['system_admin'::character varying, 'operation'::character varying, 'sales'::character varying, 'accounting'::character varying, 'contract_plan_manager'::character varying]::text[]))");
        }
    }

    public function down(): void
    {
        $driver = Schema::getConnection()->getDriverName();
        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE admin_users MODIFY COLUMN role ENUM('system_admin','operation','sales','accounting') NOT NULL");
        } elseif ($driver === 'pgsql') {
            DB::statement('ALTER TABLE admin_users DROP CONSTRAINT IF EXISTS admin_users_role_check');
            DB::statement("ALTER TABLE admin_users ADD CONSTRAINT admin_users_role_check CHECK (role::text = ANY (ARRAY['system_admin'::character varying, 'operation'::character varying, 'sales'::character varying, 'accounting'::character varying]::text[]))");
        }

        Schema::table('uniform_products', function (Blueprint $table) {
            $table->dropConstrainedForeignId('maker_id');
            $table->dropColumn(['maker_product_code', 'color_name']);
        });

        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn(['inseam_cm', 'body_type', 'join_date', 'retire_date']);
        });

        Schema::table('medical_institutions', function (Blueprint $table) {
            $table->dropColumn(['billing_closing_day', 'sales_person_name']);
        });

        Schema::dropIfExists('employee_contract_plans');
        Schema::dropIfExists('customer_contract_plans');
        Schema::dropIfExists('contract_plan_uniform_product');
        Schema::dropIfExists('contract_plans');
        Schema::dropIfExists('makers');
    }
};
