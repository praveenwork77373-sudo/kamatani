<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Application extends Model
{
    use HasFactory;

    protected $fillable = [
        'medical_institution_id',
        'department_id',
        'employee_id',
        'transfer_to_employee_id',
        'type',
        'status',
        'billing_status',
        'start_date',
        'end_date',
        'return_date',
        'delivery_location_id',
        'requested_by_user_id',
        'special_approval_reason',
        'rejection_reason',
        'approved_at',
        'approved_by_admin_user_id',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'return_date' => 'date',
        'approved_at' => 'datetime',
    ];

    public function medicalInstitution(): BelongsTo
    {
        return $this->belongsTo(MedicalInstitution::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function transferToEmployee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'transfer_to_employee_id');
    }

    public function deliveryLocation(): BelongsTo
    {
        return $this->belongsTo(DeliveryLocation::class);
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by_user_id');
    }
    
    public function requester(): BelongsTo
    {
        return $this->requestedBy();
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by_admin_user_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(ApplicationItem::class);
    }
}


