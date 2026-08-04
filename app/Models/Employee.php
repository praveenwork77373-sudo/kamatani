<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Employee extends Model
{
    use HasFactory;

    protected $fillable = [
        'medical_institution_id',
        'staff_no',
        'name',
        'gender',
        'top_size',
        'bottom_size',
        'shoe_size',
        'inseam_cm',
        'body_type',
        'status',
        'join_date',
        'retire_date',
        'primary_department_id',
    ];

    protected $casts = [
        'join_date' => 'date',
        'retire_date' => 'date',
        'inseam_cm' => 'integer',
    ];

    public function medicalInstitution(): BelongsTo
    {
        return $this->belongsTo(MedicalInstitution::class);
    }

    public function primaryDepartment(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'primary_department_id');
    }

    public function positions(): HasMany
    {
        return $this->hasMany(EmployeePosition::class);
    }

    public function loans(): HasMany
    {
        return $this->hasMany(EmployeeLoan::class);
    }

    public function statusHistory(): HasMany
    {
        return $this->hasMany(EmployeeStatusHistory::class);
    }

    public function contractPlanAssignments(): HasMany
    {
        return $this->hasMany(EmployeeContractPlan::class);
    }

    /**
     * Primary department OR any concurrent position in the department.
     */
    public function scopeInDepartment($query, int $departmentId)
    {
        return $query->where(function ($q) use ($departmentId) {
            $q->where('primary_department_id', $departmentId)
                ->orWhereHas('positions', fn ($p) => $p->where('department_id', $departmentId));
        });
    }

    public function isAffiliatedWithDepartment(int $departmentId): bool
    {
        if ((int) $this->primary_department_id === $departmentId) {
            return true;
        }

        if ($this->relationLoaded('positions')) {
            return $this->positions->contains(fn ($p) => (int) $p->department_id === $departmentId);
        }

        return $this->positions()->where('department_id', $departmentId)->exists();
    }

    /**
     * Display labels for all affiliations (primary first).
     *
     * @return list<string>
     */
    public function affiliationDepartmentNames(): array
    {
        $names = [];
        if ($this->primaryDepartment?->name) {
            $names[] = $this->primaryDepartment->name;
        }

        $positions = $this->relationLoaded('positions')
            ? $this->positions
            : $this->positions()->with('department')->get();

        foreach ($positions as $position) {
            $name = $position->department?->name;
            if ($name && ! in_array($name, $names, true)) {
                $names[] = $name;
            }
        }

        return $names;
    }

    /**
     * Get employee status as of a specific date
     */
    public function getStatusAsOf($date)
    {
        $history = $this->statusHistory()
            ->where('effective_from', '<=', $date)
            ->where(function($q) use ($date) {
                $q->whereNull('effective_until')
                  ->orWhere('effective_until', '>=', $date);
            })
            ->orderByDesc('effective_from')
            ->first();

        return $history ? $history->status : $this->status;
    }
}


