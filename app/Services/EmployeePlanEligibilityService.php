<?php

namespace App\Services;

use App\Models\ContractPlan;
use App\Models\Employee;
use App\Models\UniformProduct;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class EmployeePlanEligibilityService
{
    /**
     * Active confirmed customer-plan assignments for the employee as of $date.
     */
    public function activeAssignments(Employee $employee, $date = null): Collection
    {
        $date = Carbon::parse($date ?? now())->toDateString();

        return $employee->contractPlanAssignments()
            ->with(['customerContractPlan.contractPlan.uniformProducts'])
            ->get()
            ->filter(function ($assignment) use ($date) {
                if (! $assignment->isActiveOn($date)) {
                    return false;
                }
                $ccp = $assignment->customerContractPlan;
                if (! $ccp || $ccp->status !== 'confirmed') {
                    return false;
                }

                return $ccp->isActiveOn($date);
            })
            ->values();
    }

    /**
     * Uniform products allowed for this employee via assigned contract plans.
     *
     * @return Collection<int, UniformProduct>
     */
    public function eligibleProducts(Employee $employee, $date = null, ?string $gender = null): Collection
    {
        $products = $this->activeAssignments($employee, $date)
            ->flatMap(fn ($a) => $a->customerContractPlan?->contractPlan?->uniformProducts ?? collect())
            ->unique('id')
            ->values();

        if ($gender) {
            $products = $products->filter(function (UniformProduct $p) use ($gender) {
                return $p->gender === $gender || $p->gender === 'unisex' || $p->gender === null;
            })->values();
        }

        return $products->sortBy('product_code')->values();
    }

    /**
     * Max rentable points from all active assigned plans (sum of plan rentable_points).
     */
    public function rentableLimit(Employee $employee, $date = null): ?int
    {
        $assignments = $this->activeAssignments($employee, $date);
        if ($assignments->isEmpty()) {
            return null;
        }

        return (int) $assignments->sum(function ($a) {
            /** @var ContractPlan|null $plan */
            $plan = $a->customerContractPlan?->contractPlan;

            return (int) ($plan?->rentable_points ?? 0);
        });
    }

    public function hasActivePlan(Employee $employee, $date = null): bool
    {
        return $this->activeAssignments($employee, $date)->isNotEmpty();
    }

    public function productIdsAllowed(Employee $employee, $date = null): array
    {
        return $this->eligibleProducts($employee, $date)->pluck('id')->all();
    }
}
