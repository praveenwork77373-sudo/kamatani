<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BillingSnapshot extends Model
{
    use HasFactory;

    protected $fillable = [
        'medical_institution_id',
        'department_id',
        'target_date',
        'data',
        'generated_by_admin_user_id',
    ];

    protected $casts = [
        'target_date' => 'date',
        'data' => 'array',
    ];

    public function medicalInstitution(): BelongsTo
    {
        return $this->belongsTo(MedicalInstitution::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function generatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by_admin_user_id');
    }
    
    public function generator(): BelongsTo
    {
        return $this->generatedBy();
    }
}


