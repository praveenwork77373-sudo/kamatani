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

    /**
     * Latest snapshot id per institution + target_date (+ department).
     * Prevents duplicate regenerations from inflating KPI totals.
     *
     * @return \Illuminate\Support\Collection<int, int>
     */
    public static function latestIds(?string $fromDate = null, ?string $toDate = null)
    {
        $query = static::query()
            ->selectRaw('MAX(id) as id')
            ->groupBy('medical_institution_id', 'target_date', 'department_id');

        if ($fromDate && $toDate) {
            $query->whereBetween('target_date', [$fromDate, $toDate]);
        }

        return $query->pluck('id');
    }

    public static function sumBillingAmount(?string $fromDate = null, ?string $toDate = null): float
    {
        $ids = static::latestIds($fromDate, $toDate);
        if ($ids->isEmpty()) {
            return 0.0;
        }

        return (float) static::query()
            ->whereIn('id', $ids)
            ->get()
            ->sum(function (self $snapshot) {
                return $snapshot->data['summary']['billing_amount']
                    ?? $snapshot->data['summary']['total_amount_ex_tax']
                    ?? 0;
            });
    }
}


