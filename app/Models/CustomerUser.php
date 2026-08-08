<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerUser extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'medical_institution_id',
        'role',
        'department_id',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function medicalInstitution(): BelongsTo
    {
        return $this->belongsTo(MedicalInstitution::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function isInstitutionAdmin(): bool
    {
        return $this->role === 'institution_admin';
    }

    public function isDepartmentAdmin(): bool
    {
        return $this->role === 'department_admin';
    }

    /**
     * Department admins without department_id must not get institution-wide access.
     */
    public function isMisconfiguredDepartmentAdmin(): bool
    {
        return $this->isDepartmentAdmin() && ! $this->department_id;
    }

    public function scopedDepartmentId(): ?int
    {
        if (! $this->isDepartmentAdmin()) {
            return null;
        }

        return $this->department_id ? (int) $this->department_id : null;
    }
}


