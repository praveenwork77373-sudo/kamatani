<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('makers', function (Blueprint $table) {
            if (! Schema::hasColumn('makers', 'tel')) {
                $table->string('tel', 50)->nullable()->after('name');
            }
            if (! Schema::hasColumn('makers', 'fax')) {
                $table->string('fax', 50)->nullable()->after('tel');
            }
            if (! Schema::hasColumn('makers', 'notes')) {
                $table->text('notes')->nullable()->after('fax');
            }
        });

        if (! Schema::hasTable('product_unit_prices')) {
            Schema::create('product_unit_prices', function (Blueprint $table) {
                $table->id();
                $table->string('product_code', 50)->comment('リースシステム商品コード');
                $table->string('size_name', 50)->nullable();
                $table->decimal('purchase_unit_price', 12, 2)->default(0);
                $table->text('notes')->nullable();
                $table->timestamps();
                $table->unique(['product_code', 'size_name'], 'product_unit_price_unique');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('product_unit_prices');
        Schema::table('makers', function (Blueprint $table) {
            foreach (['tel', 'fax', 'notes'] as $col) {
                if (Schema::hasColumn('makers', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
