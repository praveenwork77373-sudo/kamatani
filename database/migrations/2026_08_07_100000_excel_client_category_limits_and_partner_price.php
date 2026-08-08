<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contract_plans', function (Blueprint $table) {
            if (! Schema::hasColumn('contract_plans', 'rentable_points_top')) {
                $table->unsignedInteger('rentable_points_top')->default(0)->after('rentable_points');
            }
            if (! Schema::hasColumn('contract_plans', 'rentable_points_bottom')) {
                $table->unsignedInteger('rentable_points_bottom')->default(0)->after('rentable_points_top');
            }
            if (! Schema::hasColumn('contract_plans', 'rentable_points_other')) {
                $table->unsignedInteger('rentable_points_other')->default(0)->after('rentable_points_bottom');
            }
            if (! Schema::hasColumn('contract_plans', 'rentable_points_other2')) {
                $table->unsignedInteger('rentable_points_other2')->default(0)->after('rentable_points_other');
            }
            if (! Schema::hasColumn('contract_plans', 'rentable_points_other3')) {
                $table->unsignedInteger('rentable_points_other3')->default(0)->after('rentable_points_other2');
            }
            if (! Schema::hasColumn('contract_plans', 'partner_unit_price_ex_tax')) {
                $table->decimal('partner_unit_price_ex_tax', 10, 2)->nullable()->after('unit_price_ex_tax')
                    ->comment('対帳合先単価（登録用・請求計算には未使用）');
            }
        });

        if (Schema::hasTable('contract_plan_uniform_product')
            && ! Schema::hasColumn('contract_plan_uniform_product', 'slot_category')) {
            Schema::table('contract_plan_uniform_product', function (Blueprint $table) {
                $table->string('slot_category', 20)->nullable()->after('uniform_product_id');
            });
        }

        if (Schema::hasTable('uniform_categories')) {
            $now = now();
            DB::table('uniform_categories')->where('code', 'bottom')->update([
                'name' => '下衣',
                'updated_at' => $now,
            ]);

            foreach ([
                ['code' => 'other2', 'name' => 'その他2', 'sort_order' => 91],
                ['code' => 'other3', 'name' => 'その他3', 'sort_order' => 92],
            ] as $row) {
                $exists = DB::table('uniform_categories')->where('code', $row['code'])->exists();
                if (! $exists) {
                    DB::table('uniform_categories')->insert([
                        'code' => $row['code'],
                        'name' => $row['name'],
                        'size_scheme' => 'none',
                        'employee_size_field' => null,
                        'sort_order' => $row['sort_order'],
                        'is_active' => true,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('uniform_categories')) {
            DB::table('uniform_categories')->whereIn('code', ['other2', 'other3'])->delete();
            DB::table('uniform_categories')->where('code', 'bottom')->update([
                'name' => 'ズボン',
                'updated_at' => now(),
            ]);
        }

        if (Schema::hasTable('contract_plan_uniform_product')
            && Schema::hasColumn('contract_plan_uniform_product', 'slot_category')) {
            Schema::table('contract_plan_uniform_product', function (Blueprint $table) {
                $table->dropColumn('slot_category');
            });
        }

        Schema::table('contract_plans', function (Blueprint $table) {
            $cols = [
                'rentable_points_top',
                'rentable_points_bottom',
                'rentable_points_other',
                'rentable_points_other2',
                'rentable_points_other3',
                'partner_unit_price_ex_tax',
            ];
            foreach ($cols as $col) {
                if (Schema::hasColumn('contract_plans', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
