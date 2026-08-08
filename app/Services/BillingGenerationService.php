<?php

namespace App\Services;

use App\Models\BillingSnapshot;
use App\Models\CustomerContractPlan;
use App\Models\MedicalInstitution;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class BillingGenerationService
{
    public function __construct(
        private PlanBillingService $planBilling
    ) {}

    /**
     * Upsert institution-level snapshot for a target (締め) date.
     *
     * @return array{snapshot: BillingSnapshot, created: bool}
     */
    public function generateForInstitution(
        MedicalInstitution $institution,
        Carbon|string $targetDate,
        ?int $generatedByUserId = null
    ): array {
        $targetDate = Carbon::parse($targetDate)->startOfDay();
        $dateStr = $targetDate->toDateString();

        $hasConfirmedPlans = CustomerContractPlan::query()
            ->where('medical_institution_id', $institution->id)
            ->where('status', 'confirmed')
            ->exists();

        if (! $hasConfirmedPlans) {
            throw new \RuntimeException(__('pages.billing_no_confirmed_plans'));
        }

        $billingData = $this->planBilling->build($institution, $targetDate);

        $existing = BillingSnapshot::query()
            ->where('medical_institution_id', $institution->id)
            ->whereDate('target_date', $dateStr)
            ->whereNull('department_id')
            ->orderByDesc('id')
            ->first();

        if ($existing) {
            $existing->update([
                'data' => $billingData,
                'generated_by_admin_user_id' => $generatedByUserId,
            ]);

            BillingSnapshot::query()
                ->where('medical_institution_id', $institution->id)
                ->whereDate('target_date', $dateStr)
                ->whereNull('department_id')
                ->where('id', '!=', $existing->id)
                ->delete();

            return ['snapshot' => $existing->fresh(), 'created' => false];
        }

        $snapshot = BillingSnapshot::create([
            'medical_institution_id' => $institution->id,
            'department_id' => null,
            'target_date' => $dateStr,
            'data' => $billingData,
            'generated_by_admin_user_id' => $generatedByUserId,
        ]);

        return ['snapshot' => $snapshot, 'created' => true];
    }

    /**
     * Institutions whose billing_closing_day matches $asOf's day-of-month
     * (capped to month length / stored day max 28).
     *
     * @return Collection<int, MedicalInstitution>
     */
    public function institutionsDueOn(Carbon|string $asOf): Collection
    {
        $asOf = Carbon::parse($asOf)->startOfDay();
        $day = (int) $asOf->day;

        return MedicalInstitution::query()
            ->orderBy('id')
            ->get()
            ->filter(function (MedicalInstitution $institution) use ($asOf, $day) {
                $closing = (int) ($institution->billing_closing_day ?: 1);
                $closing = max(1, min(28, $closing));
                $lastDay = $asOf->copy()->endOfMonth()->day;
                $effective = min($closing, $lastDay);

                return $effective === $day;
            })
            ->values();
    }

    /**
     * Auto-generate for all hospitals due on $asOf (default today).
     * Skips institutions without confirmed CCPs.
     *
     * @return array{generated: int, updated: int, skipped: int, errors: list<string>}
     */
    public function generateDueClosingDay(?Carbon $asOf = null, bool $overwrite = true): array
    {
        $asOf = ($asOf ?? now())->copy()->startOfDay();
        $generated = 0;
        $updated = 0;
        $skipped = 0;
        $errors = [];

        foreach ($this->institutionsDueOn($asOf) as $institution) {
            $hasConfirmed = CustomerContractPlan::query()
                ->where('medical_institution_id', $institution->id)
                ->where('status', 'confirmed')
                ->exists();

            if (! $hasConfirmed) {
                $skipped++;
                continue;
            }

            $exists = BillingSnapshot::query()
                ->where('medical_institution_id', $institution->id)
                ->whereDate('target_date', $asOf->toDateString())
                ->whereNull('department_id')
                ->exists();

            if ($exists && ! $overwrite) {
                $skipped++;
                continue;
            }

            try {
                $result = $this->generateForInstitution($institution, $asOf, null);
                if ($result['created']) {
                    $generated++;
                } else {
                    $updated++;
                }
            } catch (\Throwable $e) {
                $errors[] = "{$institution->code}: {$e->getMessage()}";
            }
        }

        return compact('generated', 'updated', 'skipped', 'errors');
    }
}
