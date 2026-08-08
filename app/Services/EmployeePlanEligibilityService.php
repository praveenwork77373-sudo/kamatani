<?php

namespace App\Services;

use App\Models\ContractPlan;
use App\Models\Employee;
use App\Models\EmployeeLoan;
use App\Models\UniformProduct;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class EmployeePlanEligibilityService
{
    /** Excel / client point buckets (上衣・下衣・その他・その他2・その他3). */
    public const POINT_CATEGORIES = ['top', 'bottom', 'other', 'other2', 'other3'];

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
     * Products without a usable size (empty / "-") are hidden from applications.
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

        return $products
            ->filter(fn (UniformProduct $p) => $p->hasUsableSize())
            ->sortBy('product_code')
            ->values();
    }

    /**
     * Max rentable points from all active assigned plans (sum of plan rentable_points).
     * When per-category caps are set, this equals the sum of those caps.
     */
    public function rentableLimit(Employee $employee, $date = null): ?int
    {
        $assignments = $this->activeAssignments($employee, $date);
        if ($assignments->isEmpty()) {
            return null;
        }

        $caps = $this->categoryCaps($employee, $date);
        if ($caps !== null) {
            return (int) array_sum($caps);
        }

        return (int) $assignments->sum(function ($a) {
            /** @var ContractPlan|null $plan */
            $plan = $a->customerContractPlan?->contractPlan;

            return (int) ($plan?->rentable_points ?? 0);
        });
    }

    /**
     * Per-category caps summed across active assigned plans.
     * Returns null when no category caps are configured (legacy total-only mode).
     *
     * @return array<string, int>|null
     */
    public function categoryCaps(Employee $employee, $date = null): ?array
    {
        $assignments = $this->activeAssignments($employee, $date);
        if ($assignments->isEmpty()) {
            return null;
        }

        $caps = array_fill_keys(self::POINT_CATEGORIES, 0);
        $hasCategoryCap = false;

        foreach ($assignments as $assignment) {
            /** @var ContractPlan|null $plan */
            $plan = $assignment->customerContractPlan?->contractPlan;
            if (! $plan) {
                continue;
            }

            foreach (self::POINT_CATEGORIES as $category) {
                $value = (int) ($plan->{'rentable_points_'.$category} ?? 0);
                if ($value > 0) {
                    $hasCategoryCap = true;
                }
                $caps[$category] += $value;
            }
        }

        return $hasCategoryCap ? $caps : null;
    }

    /**
     * Map a product / pivot category onto a point-limit bucket.
     */
    public function normalizeLimitCategory(?string $category): string
    {
        return match ($category) {
            'top' => 'top',
            'bottom' => 'bottom',
            'other2' => 'other2',
            'other3' => 'other3',
            default => 'other', // shoe, other, unknown → その他
        };
    }

    /**
     * Resolve which point bucket a product counts against for this employee.
     * Prefers plan pivot slot_category when present.
     */
    public function limitCategoryForProduct(Employee $employee, int $productId, $date = null): string
    {
        foreach ($this->activeAssignments($employee, $date) as $assignment) {
            $plan = $assignment->customerContractPlan?->contractPlan;
            if (! $plan) {
                continue;
            }
            $linked = $plan->uniformProducts->firstWhere('id', $productId);
            if ($linked && ! empty($linked->pivot?->slot_category)) {
                return $this->normalizeLimitCategory((string) $linked->pivot->slot_category);
            }
        }

        $product = UniformProduct::query()->find($productId);

        return $this->normalizeLimitCategory($product?->category);
    }

    /**
     * Check projected loans against total and/or per-category caps.
     *
     * @param  list<array{uniform_product_id?:int|string, quantity?:int|string}>  $items
     * @param  list<array{uniform_product_id?:int|string, quantity?:int|string}>  $returnItems
     * @param  bool  $includePendingApplications  When true (accept-time), also count other open apps
     * @return array{mode: string, category?: string, limit: int, projected: int}|null
     */
    public function loanLimitViolation(
        Employee $employee,
        array $items,
        array $returnItems = [],
        $date = null,
        bool $includePendingApplications = false,
        ?int $excludeApplicationId = null
    ): ?array {
        $caps = $this->categoryCaps($employee, $date);

        if ($caps !== null) {
            $projected = $this->projectedQuantitiesByCategory(
                $employee,
                $items,
                $returnItems,
                $date,
                $includePendingApplications,
                $excludeApplicationId
            );
            foreach (self::POINT_CATEGORIES as $category) {
                $limit = (int) ($caps[$category] ?? 0);
                $qty = (int) ($projected[$category] ?? 0);
                if ($limit >= 0 && $qty > $limit) {
                    return [
                        'mode' => 'category',
                        'category' => $category,
                        'limit' => $limit,
                        'projected' => $qty,
                    ];
                }
            }

            return null;
        }

        $rentableLimit = $this->rentableLimit($employee, $date);
        if ($rentableLimit === null || $rentableLimit <= 0) {
            return null;
        }

        $current = (int) EmployeeLoan::query()
            ->where('employee_id', $employee->id)
            ->where('status', 'in_use')
            ->sum('quantity');

        if ($includePendingApplications) {
            $current += $this->pendingOutboundQuantity($employee, $excludeApplicationId);
        }

        $add = (int) array_sum(array_column($items, 'quantity'));
        $remove = (int) array_sum(array_column($returnItems, 'quantity'));
        $projected = $current - $remove + $add;

        if ($projected > $rentableLimit) {
            return [
                'mode' => 'total',
                'limit' => $rentableLimit,
                'projected' => $projected,
            ];
        }

        return null;
    }

    /**
     * @param  list<array{uniform_product_id?:int|string, quantity?:int|string}>  $items
     * @param  list<array{uniform_product_id?:int|string, quantity?:int|string}>  $returnItems
     * @return array<string, int>
     */
    public function projectedQuantitiesByCategory(
        Employee $employee,
        array $items,
        array $returnItems = [],
        $date = null,
        bool $includePendingApplications = false,
        ?int $excludeApplicationId = null
    ): array {
        $qty = array_fill_keys(self::POINT_CATEGORIES, 0);

        $loans = EmployeeLoan::query()
            ->with('uniformProduct')
            ->where('employee_id', $employee->id)
            ->where('status', 'in_use')
            ->where('quantity', '>', 0)
            ->get();

        foreach ($loans as $loan) {
            $category = $this->limitCategoryForProduct($employee, (int) $loan->uniform_product_id, $date);
            $qty[$category] += (int) $loan->quantity;
        }

        if ($includePendingApplications) {
            foreach ($this->pendingOutboundByProduct($employee, $excludeApplicationId) as $productId => $pendingQty) {
                $category = $this->limitCategoryForProduct($employee, (int) $productId, $date);
                $qty[$category] += (int) $pendingQty;
            }
        }

        foreach ($returnItems as $item) {
            $productId = (int) ($item['uniform_product_id'] ?? 0);
            $quantity = (int) ($item['quantity'] ?? 0);
            if ($productId <= 0 || $quantity <= 0) {
                continue;
            }
            $category = $this->limitCategoryForProduct($employee, $productId, $date);
            $qty[$category] = max(0, $qty[$category] - $quantity);
        }

        foreach ($items as $item) {
            $productId = (int) ($item['uniform_product_id'] ?? 0);
            $quantity = (int) ($item['quantity'] ?? 0);
            if ($productId <= 0 || $quantity <= 0) {
                continue;
            }
            $category = $this->limitCategoryForProduct($employee, $productId, $date);
            $qty[$category] += $quantity;
        }

        return $qty;
    }

    /**
     * Outbound quantities on open applications (excluding the current accept target when locked separately).
     *
     * @return array<int, int> productId => qty
     */
    public function pendingOutboundByProduct(Employee $employee, ?int $excludeApplicationId = null): array
    {
        $rows = \App\Models\ApplicationItem::query()
            ->select('application_items.uniform_product_id', \Illuminate\Support\Facades\DB::raw('SUM(application_items.quantity) as qty'))
            ->join('applications', 'applications.id', '=', 'application_items.application_id')
            ->where('applications.employee_id', $employee->id)
            ->whereIn('applications.status', [
                \App\Models\Application::STATUS_PENDING,
                \App\Models\Application::STATUS_WAITING_SPECIAL,
            ])
            ->whereIn('application_items.action', ['loan', 'exchange_in', 'transfer_change'])
            ->when($excludeApplicationId, fn ($q) => $q->where('applications.id', '!=', $excludeApplicationId))
            ->groupBy('application_items.uniform_product_id')
            ->pluck('qty', 'uniform_product_id')
            ->all();

        return array_map('intval', $rows);
    }

    public function pendingOutboundQuantity(Employee $employee, ?int $excludeApplicationId = null): int
    {
        return (int) array_sum($this->pendingOutboundByProduct($employee, $excludeApplicationId));
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
