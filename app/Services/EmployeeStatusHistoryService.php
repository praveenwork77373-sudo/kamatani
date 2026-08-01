<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\EmployeeStatusHistory;
use Illuminate\Support\Facades\Auth;

class EmployeeStatusHistoryService
{
    /**
     * Record status change for as-of billing. Closes previous open history row.
     */
    public static function record(Employee $employee, string $newStatus, ?string $notes = null): void
    {
        $today = now()->toDateString();

        EmployeeStatusHistory::where('employee_id', $employee->id)
            ->whereNull('effective_until')
            ->update(['effective_until' => $today]);

        EmployeeStatusHistory::create([
            'employee_id' => $employee->id,
            'status' => $newStatus,
            'effective_from' => $today,
            'effective_until' => null,
            'notes' => $notes,
            'changed_by_user_id' => Auth::id(),
        ]);
    }
}
