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
        'rentable_points_top',
        'rentable_points_bottom',
        'rentable_points_other',
        'rentable_points_other2',
        'rentable_points_other3',
        'unit_price_ex_tax',
        'partner_unit_price_ex_tax',
        'job_type_label',
        'work_style',
        'notes',
        'is_active',
    ];

    protected $casts = [
        'rentable_points' => 'integer',
        'rentable_points_top' => 'integer',
        'rentable_points_bottom' => 'integer',
        'rentable_points_other' => 'integer',
        'rentable_points_other2' => 'integer',
        'rentable_points_other3' => 'integer',
        'unit_price_ex_tax' => 'decimal:2',
        'partner_unit_price_ex_tax' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function uniformProducts(): BelongsToMany
    {
        return $this->belongsToMany(UniformProduct::class, 'contract_plan_uniform_product')
            ->withPivot('slot_category')
            ->withTimestamps();
    }

    public function syncRentablePointsTotal(): void
    {
        $categoryTotal = (int) $this->rentable_points_top
            + (int) $this->rentable_points_bottom
            + (int) $this->rentable_points_other
            + (int) $this->rentable_points_other2
            + (int) $this->rentable_points_other3;

        if ($categoryTotal > 0) {
            $this->rentable_points = $categoryTotal;
        }
    }

    public function hasCategoryCaps(): bool
    {
        return ((int) $this->rentable_points_top
            + (int) $this->rentable_points_bottom
            + (int) $this->rentable_points_other
            + (int) $this->rentable_points_other2
            + (int) $this->rentable_points_other3) > 0;
    }

    public function customerContractPlans(): HasMany
    {
        return $this->hasMany(CustomerContractPlan::class);
    }
}
