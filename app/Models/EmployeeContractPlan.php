<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeContractPlan extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'customer_contract_plan_id',
        'employee_position_id',
        'assigned_from',
        'assigned_until',
    ];

    protected $casts = [
        'assigned_from' => 'date',
        'assigned_until' => 'date',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function customerContractPlan(): BelongsTo
    {
        return $this->belongsTo(CustomerContractPlan::class);
    }

    public function employeePosition(): BelongsTo
    {
        return $this->belongsTo(EmployeePosition::class);
    }

    public function isActiveOn($date): bool
    {
        $date = \Carbon\Carbon::parse($date)->toDateString();
        if ($this->assigned_from && $this->assigned_from->toDateString() > $date) {
            return false;
        }
        if ($this->assigned_until && $this->assigned_until->toDateString() < $date) {
            return false;
        }
        return true;
    }
}
