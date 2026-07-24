<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Department extends Model
{
    use HasFactory;

    protected $fillable = [
        'medical_institution_id',
        'name',
        'code',
    ];

    public function medicalInstitution(): BelongsTo
    {
        return $this->belongsTo(MedicalInstitution::class);
    }

    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class, 'primary_department_id');
    }

    public function applications(): HasMany
    {
        return $this->hasMany(Application::class);
    }
}


