<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UniformProduct extends Model
{
    use HasFactory;

    protected $fillable = [
        'maker_id',
        'product_code',
        'maker_product_code',
        'name',
        'color_name',
        'category',
        'size',
        'gender',
        'stock_quantity',
        'image_path',
    ];

    protected $casts = [
        'size' => 'string',
    ];

    public function maker()
    {
        return $this->belongsTo(Maker::class);
    }

    public function uniformCategory()
    {
        return $this->belongsTo(UniformCategory::class, 'category', 'code');
    }

    public function contractPlans()
    {
        return $this->belongsToMany(ContractPlan::class, 'contract_plan_uniform_product')
            ->withTimestamps();
    }

    public function sizeScheme(): string
    {
        return UniformCategory::sizeSchemeFor($this->category);
    }

    public function categoryLabel(): string
    {
        return UniformCategory::labelFor($this->category);
    }
}
