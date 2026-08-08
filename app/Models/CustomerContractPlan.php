<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CustomerContractPlan extends Model
{
    use HasFactory;

    protected $fillable = [
        'medical_institution_id',
        'contract_plan_id',
        'contract_count',
        'start_date',
        'end_date',
        'status',
        'confirmed_at',
        'confirmed_by_admin_user_id',
        'notes',
    ];

    protected $casts = [
        'contract_count' => 'integer',
        'start_date' => 'date',
        'end_date' => 'date',
        'confirmed_at' => 'datetime',
    ];

    public function medicalInstitution(): BelongsTo
    {
        return $this->belongsTo(MedicalInstitution::class);
    }

    public function contractPlan(): BelongsTo
    {
        return $this->belongsTo(ContractPlan::class);
    }

    public function confirmedByAdmin(): BelongsTo
    {
        return $this->belongsTo(AdminUser::class, 'confirmed_by_admin_user_id');
    }

    public function employeeAssignments(): HasMany
    {
        return $this->hasMany(EmployeeContractPlan::class);
    }

    public function isConfirmed(): bool
    {
        return $this->status === 'confirmed';
    }

    public function isActiveOn($date): bool
    {
        if (! $this->isConfirmed()) {
            return false;
        }

        $date = \Carbon\Carbon::parse($date)->toDateString();

        // Client 8/7 Q6: no open-ended (無期限) contracts — start and end required
        if (! $this->start_date || ! $this->end_date) {
            return false;
        }

        if ($this->start_date->toDateString() > $date) {
            return false;
        }

        if ($this->end_date->toDateString() < $date) {
            return false;
        }

        return true;
    }
}
