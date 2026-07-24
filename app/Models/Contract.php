<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Contract extends Model
{
    use HasFactory;

    protected $fillable = [
        'medical_institution_id',
        'contract_number',
        'contract_type',
        'billing_model',
        'per_person_loan_limit',
        'loan_limit_per_item',
        'point_unit_price',
        'fixed_unit_price',
        'cleaning_multiplier',
        'valid_from',
        'valid_until',
        'billing_rules',
        'inventory_constraints',
    ];

    protected $casts = [
        'valid_from' => 'date',
        'valid_until' => 'date',
        'cleaning_multiplier' => 'decimal:2',
        'billing_rules' => 'array',
        'inventory_constraints' => 'array',
    ];

    protected static function boot()
    {
        parent::boot();

        // Auto-generate contract number if not provided
        static::creating(function ($contract) {
            if (empty($contract->contract_number)) {
                $year = now()->format('Y');
                $count = static::whereYear('created_at', $year)->count() + 1;
                $contract->contract_number = 'CT-' . $year . '-' . str_pad($count, 4, '0', STR_PAD_LEFT);
            }
        });
    }

    public function medicalInstitution(): BelongsTo
    {
        return $this->belongsTo(MedicalInstitution::class);
    }
}


