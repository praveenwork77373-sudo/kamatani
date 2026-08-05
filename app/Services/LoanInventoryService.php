<?php

namespace App\Services;

use App\Models\Department;
use App\Models\Employee;
use App\Models\EmployeeLoan;
use App\Models\UniformCategory;
use App\Support\Pagination;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class LoanInventoryService
{
    /**
     * Active (in_use) loans for one employee, newest first.
     *
     * @return Collection<int, EmployeeLoan>
     */
    public function activeLoansForEmployee(Employee $employee): Collection
    {
        return EmployeeLoan::query()
            ->with(['uniformProduct.maker'])
            ->where('employee_id', $employee->id)
            ->where('status', 'in_use')
            ->orderByDesc('loan_start_date')
            ->orderByDesc('id')
            ->get();
    }

    public function activeLoanQuantityForEmployee(Employee $employee): int
    {
        return (int) EmployeeLoan::query()
            ->where('employee_id', $employee->id)
            ->where('status', 'in_use')
            ->sum('quantity');
    }

    public function hasActiveLoans(Employee $employee): bool
    {
        return EmployeeLoan::query()
            ->where('employee_id', $employee->id)
            ->where('status', 'in_use')
            ->where('quantity', '>', 0)
            ->exists();
    }

    /**
     * Map uniform_product_id => held quantity for in_use loans.
     *
     * @return array<int, int>
     */
    public function activeLoanQuantitiesByProduct(int $employeeId): array
    {
        return EmployeeLoan::query()
            ->where('employee_id', $employeeId)
            ->where('status', 'in_use')
            ->where('quantity', '>', 0)
            ->selectRaw('uniform_product_id, SUM(quantity) as quantity')
            ->groupBy('uniform_product_id')
            ->pluck('quantity', 'uniform_product_id')
            ->map(fn ($q) => (int) $q)
            ->all();
    }

    /**
     * Validate return/exchange-out lines against held loan quantities.
     *
     * @param  array<int, array{uniform_product_id?: mixed, quantity?: mixed}>  $returnItems
     * @return string|null  Translation key for error, or null when valid
     */
    public function returnItemsValidationError(int $employeeId, array $returnItems): ?string
    {
        $held = $this->activeLoanQuantitiesByProduct($employeeId);
        $requestedByProduct = [];

        foreach ($returnItems as $item) {
            $productId = (int) ($item['uniform_product_id'] ?? 0);
            $qty = (int) ($item['quantity'] ?? 0);
            if ($productId <= 0 || $qty <= 0) {
                continue;
            }
            $requestedByProduct[$productId] = ($requestedByProduct[$productId] ?? 0) + $qty;
        }

        foreach ($requestedByProduct as $productId => $qty) {
            if (! isset($held[$productId])) {
                return 'pages.flash_bcd90f582a';
            }
            if ($qty > $held[$productId]) {
                return 'pages.return_quantity_exceeds_loan';
            }
        }

        return null;
    }

    /**
     * Filtered active-loan board for hospital / Kamatani.
     *
     * @param  array{
     *     medical_institution_id?: int|null,
     *     department_id?: int|null,
     *     q?: string|null,
     *     product_q?: string|null
     * }  $filters
     * @return array{
     *     loans: LengthAwarePaginator,
     *     total_quantity: int,
     *     employee_count: int,
     *     institution_count: int,
     *     department_count: int,
     *     sku_count: int
     * }
     */
    public function searchActiveLoans(Request $request, array $filters = []): array
    {
        $base = $this->filteredActiveLoanQuery($filters);

        $totalQuantity = (int) (clone $base)->sum('employee_loans.quantity');
        $employeeCount = (int) (clone $base)->distinct('employee_loans.employee_id')->count('employee_loans.employee_id');
        $skuCount = (int) (clone $base)->distinct('employee_loans.uniform_product_id')->count('employee_loans.uniform_product_id');

        $institutionCount = (int) (clone $base)
            ->distinct('employees.medical_institution_id')
            ->count('employees.medical_institution_id');

        $departmentCount = (int) (clone $base)
            ->whereNotNull('employees.primary_department_id')
            ->distinct('employees.primary_department_id')
            ->count('employees.primary_department_id');

        $loans = $this->filteredActiveLoanQuery($filters)
            ->with([
                'uniformProduct.maker',
                'employee.primaryDepartment.medicalInstitution',
                'employee.medicalInstitution',
            ])
            ->orderByDesc('employee_loans.loan_start_date')
            ->orderByDesc('employee_loans.id')
            ->paginate(Pagination::perPage($request))
            ->withQueryString();

        return [
            'loans' => $loans,
            'total_quantity' => $totalQuantity,
            'employee_count' => $employeeCount,
            'institution_count' => $institutionCount,
            'department_count' => $departmentCount,
            'sku_count' => $skuCount,
        ];
    }

    /**
     * @param  array{
     *     medical_institution_id?: int|null,
     *     department_id?: int|null,
     *     q?: string|null,
     *     product_q?: string|null
     * }  $filters
     */
    public function filteredActiveLoanQuery(array $filters = []): Builder
    {
        $query = EmployeeLoan::query()
            ->select('employee_loans.*')
            ->where('employee_loans.status', 'in_use')
            ->join('employees', 'employees.id', '=', 'employee_loans.employee_id');

        if (! empty($filters['medical_institution_id'])) {
            $query->where('employees.medical_institution_id', (int) $filters['medical_institution_id']);
        }

        if (! empty($filters['department_id'])) {
            $departmentId = (int) $filters['department_id'];
            $query->where(function ($q) use ($departmentId) {
                $q->where('employees.primary_department_id', $departmentId)
                    ->orWhereExists(function ($sub) use ($departmentId) {
                        $sub->selectRaw('1')
                            ->from('employee_positions')
                            ->whereColumn('employee_positions.employee_id', 'employees.id')
                            ->where('employee_positions.department_id', $departmentId);
                    });
            });
        }

        if (! empty($filters['q'])) {
            $search = trim((string) $filters['q']);
            $query->where(function ($q) use ($search) {
                $q->where('employees.name', 'like', "%{$search}%")
                    ->orWhere('employees.staff_no', 'like', "%{$search}%");
            });
        }

        if (! empty($filters['product_q'])) {
            $productQ = trim((string) $filters['product_q']);
            $query->whereExists(function ($sub) use ($productQ) {
                $sub->selectRaw('1')
                    ->from('uniform_products')
                    ->whereColumn('uniform_products.id', 'employee_loans.uniform_product_id')
                    ->where(function ($pq) use ($productQ) {
                        $pq->where('uniform_products.product_code', 'like', "%{$productQ}%")
                            ->orWhere('uniform_products.name', 'like', "%{$productQ}%");
                    });
            });
        }

        return $query;
    }

    /**
     * Department loan inventory: product totals + per-employee breakdown.
     *
     * @return array{
     *     total_quantity: int,
     *     employee_count_with_loans: int,
     *     by_product: Collection<int, object>,
     *     by_employee: Collection<int, array>
     * }
     */
    public function departmentInventory(Department $department): array
    {
        $employeeIds = Employee::query()
            ->inDepartment((int) $department->id)
            ->pluck('id');

        $loans = EmployeeLoan::query()
            ->with(['uniformProduct.maker', 'employee'])
            ->whereIn('employee_id', $employeeIds)
            ->where('status', 'in_use')
            ->orderBy('employee_id')
            ->get();

        $byProduct = $loans
            ->groupBy('uniform_product_id')
            ->map(function (Collection $group) {
                /** @var EmployeeLoan $first */
                $first = $group->first();
                $product = $first->uniformProduct;

                return (object) [
                    'uniform_product_id' => $first->uniform_product_id,
                    'product_code' => $product?->product_code ?? '-',
                    'product_name' => $product?->name ?? '-',
                    'size' => $product?->size ?? '-',
                    'category' => $product?->category ?? '-',
                    'category_label' => UniformCategory::labelFor($product?->category),
                    'maker_name' => $product?->maker?->name ?? '-',
                    'quantity' => (int) $group->sum('quantity'),
                    'holder_count' => $group->pluck('employee_id')->unique()->count(),
                ];
            })
            ->sortBy('product_code')
            ->values();

        $byEmployee = $loans
            ->groupBy('employee_id')
            ->map(function (Collection $group) {
                /** @var EmployeeLoan $first */
                $first = $group->first();
                $employee = $first->employee;

                return [
                    'employee' => $employee,
                    'total_quantity' => (int) $group->sum('quantity'),
                    'loans' => $group->values(),
                ];
            })
            ->sortBy(fn (array $row) => $row['employee']?->staff_no ?? '')
            ->values();

        return [
            'total_quantity' => (int) $loans->sum('quantity'),
            'employee_count_with_loans' => $byEmployee->count(),
            'by_product' => $byProduct,
            'by_employee' => $byEmployee,
        ];
    }
}
