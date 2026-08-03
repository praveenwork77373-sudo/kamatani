<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductUnitPrice extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_code',
        'size_name',
        'purchase_unit_price',
        'notes',
    ];

    protected $casts = [
        'purchase_unit_price' => 'decimal:2',
    ];
}
