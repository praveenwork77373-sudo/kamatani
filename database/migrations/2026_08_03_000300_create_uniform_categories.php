<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('uniform_categories', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('name');
            $table->string('size_scheme', 20)->default('letter')
                ->comment('letter=XS-XXL, shoe=numeric cm, none=no standard sizes');
            $table->string('employee_size_field', 30)->nullable()
                ->comment('Optional employee default size field: top_size|bottom_size|shoe_size');
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        $now = now();
        DB::table('uniform_categories')->insert([
            [
                'code' => 'top',
                'name' => '上衣',
                'size_scheme' => 'letter',
                'employee_size_field' => 'top_size',
                'sort_order' => 10,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'code' => 'bottom',
                'name' => 'ズボン',
                'size_scheme' => 'letter',
                'employee_size_field' => 'bottom_size',
                'sort_order' => 20,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'code' => 'shoe',
                'name' => '靴',
                'size_scheme' => 'shoe',
                'employee_size_field' => 'shoe_size',
                'sort_order' => 30,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'code' => 'other',
                'name' => 'その他',
                'size_scheme' => 'none',
                'employee_size_field' => null,
                'sort_order' => 90,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);

        $driver = Schema::getConnection()->getDriverName();
        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE uniform_products MODIFY category VARCHAR(50) NOT NULL DEFAULT 'other'");
        } elseif ($driver === 'pgsql') {
            DB::statement('ALTER TABLE uniform_products ALTER COLUMN category TYPE VARCHAR(50)');
            DB::statement("ALTER TABLE uniform_products ALTER COLUMN category SET DEFAULT 'other'");
        }
        // sqlite: enum is already stored as varchar — no ALTER needed
    }

    public function down(): void
    {
        Schema::dropIfExists('uniform_categories');

        $driver = Schema::getConnection()->getDriverName();
        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE uniform_products MODIFY category ENUM('top','bottom','shoe','other') NOT NULL DEFAULT 'other'");
        }
    }
};
