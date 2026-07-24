<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UniformRecommendation extends Model
{
    use HasFactory;

    protected $fillable = [
        'medical_institution_id',
        'department_id',
        'job_type_id',
        'uniform_product_id',
        'is_required',
        'is_recommended',
    ];

    public function medicalInstitution(): BelongsTo
    {
        return $this->belongsTo(MedicalInstitution::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function jobType(): BelongsTo
    {
        return $this->belongsTo(JobType::class);
    }

    public function uniformProduct(): BelongsTo
    {
        return $this->belongsTo(UniformProduct::class);
    }
}


