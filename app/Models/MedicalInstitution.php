<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class MedicalInstitution extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'notification_email',
        'billing_closing_day',
        'sales_person_name',
    ];

    protected $casts = [
        'billing_closing_day' => 'integer',
    ];

    public function departments(): HasMany
    {
        return $this->hasMany(Department::class);
    }

    public function jobTypes(): HasMany
    {
        return $this->hasMany(JobType::class);
    }

    public function deliveryLocations(): HasMany
    {
        return $this->hasMany(DeliveryLocation::class);
    }

    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class);
    }

    public function customerContractPlans(): HasMany
    {
        return $this->hasMany(CustomerContractPlan::class);
    }

    public function institutionAdmin(): HasOne
    {
        return $this->hasOne(CustomerUser::class)
            ->where('role', 'institution_admin')
            ->whereNull('department_id');
    }
}
