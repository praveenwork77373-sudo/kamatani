<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ContractPlan extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'rentable_points',
        'unit_price_ex_tax',
        'job_type_label',
        'work_style',
        'notes',
        'is_active',
    ];

    protected $casts = [
        'rentable_points' => 'integer',
        'unit_price_ex_tax' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function uniformProducts(): BelongsToMany
    {
        return $this->belongsToMany(UniformProduct::class, 'contract_plan_uniform_product')
            ->withTimestamps();
    }

    public function customerContractPlans(): HasMany
    {
        return $this->hasMany(CustomerContractPlan::class);
    }
}
