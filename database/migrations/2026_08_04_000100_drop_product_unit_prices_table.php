<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::dropIfExists('product_unit_prices');
    }

    public function down(): void
    {
        // Intentionally empty — cost master was unused and removed.
    }
};
