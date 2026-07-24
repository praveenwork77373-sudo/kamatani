<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeLoan extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'uniform_product_id',
        'quantity',
        'status',
        'loan_start_date',
        'loan_end_date',
        'application_id',
    ];

    protected $casts = [
        'loan_start_date' => 'date',
        'loan_end_date' => 'date',
    ];

    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function uniformProduct(): BelongsTo
    {
        return $this->belongsTo(UniformProduct::class);
    }
}


