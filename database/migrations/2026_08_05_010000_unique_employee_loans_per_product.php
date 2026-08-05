<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Collapse accidental duplicate rows before unique index
        $duplicates = DB::table('employee_loans')
            ->select('employee_id', 'uniform_product_id', DB::raw('COUNT(*) as c'))
            ->groupBy('employee_id', 'uniform_product_id')
            ->having('c', '>', 1)
            ->get();

        foreach ($duplicates as $dup) {
            $rows = DB::table('employee_loans')
                ->where('employee_id', $dup->employee_id)
                ->where('uniform_product_id', $dup->uniform_product_id)
                ->orderByDesc('id')
                ->get();

            $keep = $rows->first();
            $inUseQty = $rows->where('status', 'in_use')->sum('quantity');
            $anyInUse = $rows->contains(fn ($r) => $r->status === 'in_use' && (int) $r->quantity > 0);

            DB::table('employee_loans')->where('id', $keep->id)->update([
                'quantity' => $anyInUse ? (int) $inUseQty : 0,
                'status' => $anyInUse ? 'in_use' : 'returned',
                'loan_end_date' => $anyInUse ? null : ($keep->loan_end_date ?? now()),
            ]);

            DB::table('employee_loans')
                ->where('employee_id', $dup->employee_id)
                ->where('uniform_product_id', $dup->uniform_product_id)
                ->where('id', '!=', $keep->id)
                ->delete();
        }

        Schema::table('employee_loans', function (Blueprint $table) {
            $table->unique(['employee_id', 'uniform_product_id'], 'employee_loans_employee_product_unique');
        });
    }

    public function down(): void
    {
        Schema::table('employee_loans', function (Blueprint $table) {
            $table->dropUnique('employee_loans_employee_product_unique');
        });
    }
};
