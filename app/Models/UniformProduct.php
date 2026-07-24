<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UniformProduct extends Model
{
    use HasFactory;

    protected $fillable = [
        'medical_institution_id',
        'department_id',
        'product_code',
        'name',
        'category',
        'size',
        'gender',
        'stock_quantity',
        'image_path',
    ];

    protected $casts = [
        'size' => 'string', // Ensure size is always stored as string (letter format: XS, S, M, L, XL, XXL for tops/bottoms, numeric strings for shoes)
    ];

    /**
     * Get the medical institution that owns the uniform product.
     */
    public function medicalInstitution()
    {
        return $this->belongsTo(\App\Models\MedicalInstitution::class);
    }

    /**
     * Get the department that owns the uniform product.
     */
    public function department()
    {
        return $this->belongsTo(\App\Models\Department::class);
    }
}


