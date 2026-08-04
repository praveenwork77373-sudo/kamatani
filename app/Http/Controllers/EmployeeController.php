<?php

namespace App\Http\Controllers;

use App\Models\CustomerUser;
use App\Models\CustomerContractPlan;
use App\Models\Department;
use App\Models\Employee;
use App\Models\EmployeeContractPlan;
use App\Models\EmployeePosition;
use App\Models\JobType;
use App\Models\MedicalInstitution;
use App\Services\EmployeeStatusHistoryService;
use App\Support\MasterCodeGenerator;
use App\Support\Pagination;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class EmployeeController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        /** @var CustomerUser|null $customer */
        $customer = $user->customerUser;

        if (!$customer) {
            abort(403, __('pages.flash_5c50f8ee3f'));
        }

        $query = Employee::query()
            ->with(['primaryDepartment', 'positions.department'])
            ->where('medical_institution_id', $customer->medical_institution_id);

        // Department admin: primary OR concurrent affiliation
        if ($customer->role === 'department_admin' && $customer->department_id) {
            $query->inDepartment((int) $customer->department_id);
        }

        // Institution admin can filter by department (includes concurrent posts)
        if ($customer->role === 'institution_admin' && $departmentId = $request->input('department_id')) {
            $query->inDepartment((int) $departmentId);
        }

        if ($search = $request->string('q')->toString()) {
            $exactIds = (clone $query)->where('staff_no', $search)->pluck('id');
            if ($exactIds->isNotEmpty()) {
                $query->whereIn('id', $exactIds);
            } else {
                $prefixIds = (clone $query)->where('staff_no', 'like', $search.'%')->pluck('id');
                if ($prefixIds->isNotEmpty()) {
                    $query->whereIn('id', $prefixIds);
                } else {
                    $query->where(function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%")
                            ->orWhere('staff_no', 'like', "%{$search}%");
                    });
                }
            }
        }
        
        // Status filter
        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        $employees = $query->orderBy('staff_no')->paginate(Pagination::perPage($request))->withQueryString();

        // Calculate statistics
        $statsQuery = Employee::query()
            ->where('medical_institution_id', $customer->medical_institution_id);
        
        if ($customer->role === 'department_admin' && $customer->department_id) {
            $statsQuery->inDepartment((int) $customer->department_id);
        }

        if ($customer->role === 'institution_admin' && $departmentId = $request->input('department_id')) {
            $statsQuery->inDepartment((int) $departmentId);
        }
        
        // Note: Statistics should always show all statuses, not filtered by status
        $totalEmployees = (clone $statsQuery)->count();
        $employedEmployees = (clone $statsQuery)->where('status', 'employed')->count();
        $onLeaveEmployees = (clone $statsQuery)->where('status', 'on_leave')->count();
        $retiredEmployees = (clone $statsQuery)->where('status', 'retired')->count();
        $newEmployeesThisMonth = (clone $statsQuery)
            ->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()])
            ->count();

        return view('employees.index', [
            'employees' => $employees,
            'customer' => $customer,
            'departments' => Department::where('medical_institution_id', $customer->medical_institution_id)->orderBy('name')->get(),
            'jobTypes' => JobType::where('medical_institution_id', $customer->medical_institution_id)->get(),
            'selectedDepartmentId' => $request->input('department_id'),
            'searchQuery' => $request->input('q'),
            'stats' => [
                'total_employees' => $totalEmployees,
                'employed_employees' => $employedEmployees,
                'on_leave_employees' => $onLeaveEmployees,
                'retired_employees' => $retiredEmployees,
                'new_employees_this_month' => $newEmployeesThisMonth,
            ],
        ]);
    }

    public function create(Request $request): View
    {
        $customer = $request->user()->customerUser;

        if (! $customer || ! in_array($customer->role, ['institution_admin', 'department_admin'], true)) {
            abort(403, __('pages.flash_fd0a7f1a92'));
        }

        $institutionId = $customer->medical_institution_id;

        $departmentsQuery = Department::where('medical_institution_id', $institutionId);
        if ($customer->role === 'department_admin' && $customer->department_id) {
            $departmentsQuery->where('id', $customer->department_id);
        }

        return view('employees.create', [
            'departments' => $departmentsQuery->orderBy('name')->get(),
            'jobTypes' => JobType::where('medical_institution_id', $institutionId)->orderBy('name')->get(),
            'customer' => $customer,
            'availablePlans' => CustomerContractPlan::with('contractPlan')
                ->where('medical_institution_id', $institutionId)
                ->where('status', 'confirmed')
                ->orderBy('id')
                ->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $customer = $request->user()->customerUser;
        $isCustomer = true;

        if (! $customer || ! in_array($customer->role, ['institution_admin', 'department_admin'], true)) {
            abort(403);
        }

        $institutionId = $customer->medical_institution_id;

        $rules = [
            'staff_no' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('employees', 'staff_no')->where(fn ($q) => $q->where('medical_institution_id', $institutionId)),
            ],
            'name' => ['required', 'string', 'max:255'],
            'gender' => ['nullable', 'in:male,female,other'],
            'top_size' => ['nullable', 'max:50'],
            'bottom_size' => ['nullable', 'max:50'],
            'shoe_size' => ['nullable', 'max:50'],
            'inseam_cm' => ['nullable', 'integer', 'min:50', 'max:120'],
            'body_type' => ['nullable', 'string', 'max:50'],
            'status' => ['required', 'in:employed,on_leave,retired'],
            'join_date' => ['nullable', 'date'],
            'retire_date' => ['nullable', 'date', 'after_or_equal:join_date'],
            'customer_contract_plan_ids' => ['nullable', 'array'],
            'customer_contract_plan_ids.*' => ['integer', 'exists:customer_contract_plans,id'],
            'positions' => ['nullable', 'array'],
            'positions.*.id' => ['nullable', 'integer'],
            'positions.*.department_id' => ['nullable', 'integer', 'exists:departments,id'],
            'positions.*.job_type_id' => ['nullable', 'integer', 'exists:job_types,id'],
            'positions.*.customer_contract_plan_ids' => ['nullable', 'array'],
            'positions.*.customer_contract_plan_ids.*' => ['integer', 'exists:customer_contract_plans,id'],
        ];

        if ($customer->role === 'institution_admin') {
            $rules['primary_department_id'] = ['required', 'exists:departments,id'];
        }

        $data = $request->validate($rules);

        $data['medical_institution_id'] = $institutionId;
        $data['staff_no'] = MasterCodeGenerator::next('staff_no', (int) $institutionId);
        $widePlanIds = $data['customer_contract_plan_ids'] ?? [];
        unset($data['customer_contract_plan_ids'], $data['positions']);

        if ($isCustomer && $customer->role === 'department_admin') {
            $data['primary_department_id'] = $customer->department_id;
        }

        DB::transaction(function () use ($data, $request, $customer, $isCustomer, $institutionId, $widePlanIds, &$employee) {
            $employee = Employee::create($data);

            EmployeeStatusHistoryService::record($employee, $employee->status, 'initial');

            $desiredPairs = $this->syncPositionsAndCollectPlanPairs(
                $employee,
                $request->input('positions', []),
                $widePlanIds,
                $institutionId,
                $isCustomer ? $customer : null
            );

            $this->syncEmployeeContractPlanAssignments($employee, $desiredPairs, $institutionId);
        });

        return redirect()
            ->route('employees.index')
            ->with('status', __('pages.flash_a3eda47eaf'));
    }

    public function show(Employee $employee): View
    {
        $this->authorize('view', $employee);

        $user = request()->user();
        $customer = $user->customerUser;

        $employee->load(['primaryDepartment', 'positions.department', 'positions.jobType', 'contractPlanAssignments.customerContractPlan.contractPlan']);

        $loanInventory = app(\App\Services\LoanInventoryService::class);
        $eligibility = app(\App\Services\EmployeePlanEligibilityService::class);
        $activeLoans = $loanInventory->activeLoansForEmployee($employee);

        return view('employees.show', [
            'employee' => $employee,
            'customer' => $customer,
            'activeLoans' => $activeLoans,
            'activeLoanQuantity' => (int) $activeLoans->sum('quantity'),
            'rentableLimit' => $eligibility->rentableLimit($employee),
        ]);
    }

    public function edit(Employee $employee): View
    {
        $this->authorize('update', $employee);

        $user = request()->user();
        $customer = $user->customerUser;

        $employee->load(['positions.contractPlanAssignments', 'contractPlanAssignments']);

        $availablePlans = CustomerContractPlan::with('contractPlan')
            ->where('medical_institution_id', $customer->medical_institution_id)
            ->where('status', 'confirmed')
            ->orderBy('id')
            ->get();

        return view('employees.edit', [
            'employee' => $employee,
            'customer' => $customer,
            'departments' => Department::where('medical_institution_id', $customer->medical_institution_id)->get(),
            'jobTypes' => JobType::where('medical_institution_id', $customer->medical_institution_id)->get(),
            'availablePlans' => $availablePlans,
        ]);
    }

    public function update(Request $request, Employee $employee): RedirectResponse
    {
        $user = $request->user();
        $customer = $user->customerUser;

        if (!$customer) {
            abort(403);
        }

        if ((int) $employee->medical_institution_id !== (int) $customer->medical_institution_id) {
            abort(403);
        }

        // 部署管理者は自部署に所属（主所属または兼務）する職員のみ編集可能
        if ($customer->role === 'department_admin' && $customer->department_id
            && ! $employee->isAffiliatedWithDepartment((int) $customer->department_id)) {
            abort(403);
        }

        $institutionId = (int) $customer->medical_institution_id;
        $rules = [
            'staff_no' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('employees', 'staff_no')
                    ->ignore($employee->id)
                    ->where(fn ($q) => $q->where('medical_institution_id', $institutionId)),
            ],
            'name' => ['required', 'string', 'max:255'],
            'gender' => ['nullable', 'in:male,female,other'],
            'top_size' => ['nullable', 'max:50'],
            'bottom_size' => ['nullable', 'max:50'],
            'shoe_size' => ['nullable', 'max:50'],
            'inseam_cm' => ['nullable', 'integer', 'min:50', 'max:120'],
            'body_type' => ['nullable', 'string', 'max:50'],
            'status' => ['required', 'in:employed,on_leave,retired'],
            'join_date' => ['nullable', 'date'],
            'retire_date' => ['nullable', 'date', 'after_or_equal:join_date'],
            'customer_contract_plan_ids' => ['nullable', 'array'],
            'customer_contract_plan_ids.*' => ['integer', 'exists:customer_contract_plans,id'],
            'positions' => ['nullable', 'array'],
            'positions.*.id' => ['nullable', 'integer'],
            'positions.*.department_id' => ['nullable', 'integer', 'exists:departments,id'],
            'positions.*.job_type_id' => ['nullable', 'integer', 'exists:job_types,id'],
            'positions.*.customer_contract_plan_ids' => ['nullable', 'array'],
            'positions.*.customer_contract_plan_ids.*' => ['integer', 'exists:customer_contract_plans,id'],
        ];

        // 全体管理者のみ部署変更可能
        if ($customer->role === 'institution_admin') {
            $rules['primary_department_id'] = ['required', 'exists:departments,id'];
        }

        $data = $request->validate($rules);
        $data['staff_no'] = $employee->staff_no ?: MasterCodeGenerator::next('staff_no', $institutionId);
        $widePlanIds = $data['customer_contract_plan_ids'] ?? [];
        unset($data['customer_contract_plan_ids'], $data['positions']);

        DB::transaction(function () use ($data, $request, $employee, $customer, $widePlanIds) {
            $oldStatus = $employee->status;
            $employee->update($data);

            if ($oldStatus !== $employee->status) {
                EmployeeStatusHistoryService::record($employee, $employee->status, 'status_change');
            }

            $desiredPairs = $this->syncPositionsAndCollectPlanPairs(
                $employee,
                $request->input('positions', []),
                $widePlanIds,
                (int) $customer->medical_institution_id,
                $customer
            );

            $this->syncEmployeeContractPlanAssignments(
                $employee,
                $desiredPairs,
                (int) $customer->medical_institution_id
            );
        });

        return redirect()
            ->route('employees.index')
            ->with('status', __('pages.flash_d54eb6564f'));
    }

    /**
     * Upsert concurrent positions and build desired plan assignment pairs.
     *
     * @param  array<int, array<string, mixed>>  $positionsInput
     * @param  array<int, int|string>  $widePlanIds
     * @return array<int, array{customer_contract_plan_id:int, employee_position_id:?int}>
     */
    private function syncPositionsAndCollectPlanPairs(
        Employee $employee,
        array $positionsInput,
        array $widePlanIds,
        int $institutionId,
        ?CustomerUser $customer = null
    ): array {
        $desiredPairs = [];
        foreach ($widePlanIds as $ccpId) {
            $desiredPairs[] = [
                'customer_contract_plan_id' => (int) $ccpId,
                'employee_position_id' => null,
            ];
        }

        $keptPositionIds = [];
        $index = 0;

        foreach ($positionsInput as $pos) {
            $deptId = $pos['department_id'] ?? null;
            $jobTypeId = $pos['job_type_id'] ?? null;
            if (! $deptId || ! $jobTypeId) {
                continue;
            }

            if ($customer && $customer->role === 'department_admin' && $customer->department_id
                && (int) $deptId !== (int) $customer->department_id) {
                continue;
            }

            $dept = Department::find($deptId);
            if (! $dept || (int) $dept->medical_institution_id !== $institutionId) {
                continue;
            }

            $jobType = JobType::find($jobTypeId);
            if (! $jobType || (int) $jobType->medical_institution_id !== $institutionId) {
                continue;
            }

            $positionId = isset($pos['id']) ? (int) $pos['id'] : null;
            $position = null;
            if ($positionId) {
                $position = EmployeePosition::where('employee_id', $employee->id)
                    ->where('id', $positionId)
                    ->first();
            }

            if ($position) {
                $position->update([
                    'department_id' => $deptId,
                    'job_type_id' => $jobTypeId,
                ]);
            } else {
                $position = EmployeePosition::create([
                    'employee_id' => $employee->id,
                    'department_id' => $deptId,
                    'job_type_id' => $jobTypeId,
                ]);
            }

            $keptPositionIds[] = $position->id;

            if ($index === 0) {
                if (! $employee->primary_department_id || ($customer && $customer->role === 'institution_admin')) {
                    $employee->primary_department_id = $deptId;
                    $employee->save();
                }
            }

            foreach (($pos['customer_contract_plan_ids'] ?? []) as $ccpId) {
                $desiredPairs[] = [
                    'customer_contract_plan_id' => (int) $ccpId,
                    'employee_position_id' => (int) $position->id,
                ];
            }

            $index++;
        }

        // Department admins must not wipe concurrent posts outside their department
        if ($customer && $customer->role === 'department_admin' && $customer->department_id) {
            $foreignPositions = EmployeePosition::where('employee_id', $employee->id)
                ->where('department_id', '!=', $customer->department_id)
                ->get();
            foreach ($foreignPositions as $fp) {
                $keptPositionIds[] = $fp->id;
            }

            $today = now()->toDateString();
            $foreignPlanPairs = $employee->contractPlanAssignments()
                ->whereNotNull('employee_position_id')
                ->whereIn('employee_position_id', $foreignPositions->pluck('id'))
                ->where(function ($q) use ($today) {
                    $q->whereNull('assigned_until')->orWhere('assigned_until', '>=', $today);
                })
                ->get()
                ->map(fn (EmployeeContractPlan $a) => [
                    'customer_contract_plan_id' => (int) $a->customer_contract_plan_id,
                    'employee_position_id' => (int) $a->employee_position_id,
                ])
                ->all();
            $desiredPairs = array_merge($desiredPairs, $foreignPlanPairs);
        }

        // Remove dropped positions (nulls FK on plan rows via nullOnDelete)
        $keptPositionIds = array_values(array_unique($keptPositionIds));
        if (! empty($keptPositionIds)) {
            EmployeePosition::where('employee_id', $employee->id)
                ->whereNotIn('id', $keptPositionIds)
                ->delete();
        } elseif (! ($customer && $customer->role === 'department_admin' && $customer->department_id)) {
            EmployeePosition::where('employee_id', $employee->id)->delete();
        }

        return $desiredPairs;
    }

    /**
     * Assign confirmed customer contract plans (optionally per concurrent position).
     * Preserves history by closing prior assignments with assigned_until instead of delete.
     *
     * @param  array<int, array{customer_contract_plan_id:int, employee_position_id:?int}|int>  $desiredPairsOrPlanIds
     */
    private function syncEmployeeContractPlanAssignments(Employee $employee, array $desiredPairsOrPlanIds, int $institutionId): void
    {
        $validIds = CustomerContractPlan::where('medical_institution_id', $institutionId)
            ->where('status', 'confirmed')
            ->pluck('id')
            ->all();

        $desired = [];
        foreach ($desiredPairsOrPlanIds as $item) {
            if (is_array($item)) {
                $ccpId = (int) ($item['customer_contract_plan_id'] ?? 0);
                $posId = $item['employee_position_id'] ?? null;
                $posId = $posId !== null && $posId !== '' ? (int) $posId : null;
            } else {
                $ccpId = (int) $item;
                $posId = null;
            }

            if ($ccpId <= 0 || ! in_array($ccpId, $validIds, true)) {
                continue;
            }

            $desired[$this->planAssignmentKey($ccpId, $posId)] = [
                'customer_contract_plan_id' => $ccpId,
                'employee_position_id' => $posId,
            ];
        }

        $today = now()->toDateString();
        $yesterday = now()->subDay()->toDateString();

        $activeAssignments = $employee->contractPlanAssignments()
            ->where(function ($q) use ($today) {
                $q->whereNull('assigned_until')
                    ->orWhere('assigned_until', '>=', $today);
            })
            ->get();

        foreach ($activeAssignments as $assignment) {
            $key = $this->planAssignmentKey(
                (int) $assignment->customer_contract_plan_id,
                $assignment->employee_position_id !== null ? (int) $assignment->employee_position_id : null
            );
            if (! isset($desired[$key])) {
                $assignment->update(['assigned_until' => $yesterday]);
            }
        }

        foreach ($desired as $pair) {
            $hasActive = $activeAssignments->contains(function (EmployeeContractPlan $a) use ($pair, $today) {
                if (! $a->isActiveOn($today)) {
                    return false;
                }
                if ((int) $a->customer_contract_plan_id !== (int) $pair['customer_contract_plan_id']) {
                    return false;
                }
                $aPos = $a->employee_position_id !== null ? (int) $a->employee_position_id : null;
                $dPos = $pair['employee_position_id'];

                return $aPos === $dPos;
            });

            if (! $hasActive) {
                EmployeeContractPlan::create([
                    'employee_id' => $employee->id,
                    'customer_contract_plan_id' => $pair['customer_contract_plan_id'],
                    'employee_position_id' => $pair['employee_position_id'],
                    'assigned_from' => $today,
                    'assigned_until' => null,
                ]);
            }
        }
    }

    private function planAssignmentKey(int $ccpId, ?int $positionId): string
    {
        return $ccpId.':'.($positionId ?? '0');
    }

    /**
     * @deprecated Use syncEmployeeContractPlanAssignments
     */
    private function syncEmployeeContractPlans(Employee $employee, array $planIds, int $institutionId): void
    {
        // CSV / legacy: replace employee-wide (null position) plans only; keep position-linked.
        $today = now()->toDateString();
        $existingPositionPairs = $employee->contractPlanAssignments()
            ->whereNotNull('employee_position_id')
            ->where(function ($q) use ($today) {
                $q->whereNull('assigned_until')
                    ->orWhere('assigned_until', '>=', $today);
            })
            ->get()
            ->map(fn (EmployeeContractPlan $a) => [
                'customer_contract_plan_id' => (int) $a->customer_contract_plan_id,
                'employee_position_id' => (int) $a->employee_position_id,
            ])
            ->all();

        $widePairs = array_map(
            fn ($id) => [
                'customer_contract_plan_id' => (int) $id,
                'employee_position_id' => null,
            ],
            $planIds
        );

        $this->syncEmployeeContractPlanAssignments(
            $employee,
            array_merge($existingPositionPairs, $widePairs),
            $institutionId
        );
    }

    public function destroy(Employee $employee): RedirectResponse
    {
        $user = request()->user();
        $customer = $user->customerUser;

        if (!$customer) {
            abort(403);
        }

        // 医療機関を跨いだ削除は禁止
        if ((int) $employee->medical_institution_id !== (int) $customer->medical_institution_id) {
            abort(403);
        }

        // 部署管理者は自部署に所属（主所属または兼務）する職員のみ削除可能
        if ($customer->role === 'department_admin' && $customer->department_id
            && ! $employee->isAffiliatedWithDepartment((int) $customer->department_id)) {
            abort(403);
        }

        $employee->delete();

        return redirect()
            ->route('employees.index')
            ->with('status', __('pages.flash_21f268905d'));
    }

    public function exportCsv(Request $request)
    {
        $user = $request->user();
        $customer = $user->customerUser;

        if (!$customer || !in_array($customer->role, ['institution_admin', 'department_admin'], true)) {
            abort(403);
        }

        $query = Employee::where('medical_institution_id', $customer->medical_institution_id);

        if ($customer->role === 'department_admin' && $customer->department_id) {
            $query->inDepartment((int) $customer->department_id);
        }

        if ($search = $request->string('q')->toString()) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('staff_no', 'like', "%{$search}%");
            });
        }

        $employees = $query->with([
            'primaryDepartment',
            'positions.department',
            'positions.jobType',
            'contractPlanAssignments.customerContractPlan.contractPlan',
        ])->orderBy('staff_no')->get();

        $filename = 'employees_' . date('YmdHis') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $csv = app(\App\Services\EmployeeCsvService::class);
        $callback = function () use ($employees, $csv) {
            $file = fopen('php://output', 'w');
            $csv->writeExport($file, $employees);
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function importCsv(Request $request): RedirectResponse
    {
        $user = $request->user();
        $customer = $user->customerUser;

        if (!$customer || !in_array($customer->role, ['institution_admin', 'department_admin'], true)) {
            abort(403);
        }

        $request->validate([
            'csv_file' => ['required', 'file', 'mimes:csv,txt', 'max:10240'],
        ]);

        $file = $request->file('csv_file');
        $path = $file->getRealPath();
        $rows = array_map('str_getcsv', file($path));
        $header = array_shift($rows) ?? [];

        DB::beginTransaction();
        try {
            $result = app(\App\Services\EmployeeCsvService::class)->importRows(
                $header,
                $rows,
                (int) $customer->medical_institution_id,
                $customer
            );
            $imported = $result['imported'];
            $updated = $result['updated'];
            $errors = $result['errors'];

            DB::commit();

            $message = "CSVインポート完了: 新規 {$imported} 件、更新 {$updated} 件";
            if (! empty($errors)) {
                $message .= '（一部スキップ: '.count($errors).' 件）';
            }

            return redirect()
                ->route('employees.index')
                ->with('status', $message);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('CSV import error: '.$e->getMessage());

            return redirect()
                ->route('employees.index')
                ->with('error', 'CSVインポート中にエラーが発生しました: '.$e->getMessage());
        }
    }

    public function getDepartmentsAndJobTypes(MedicalInstitution $institution)
    {
        $departments = Department::where('medical_institution_id', $institution->id)
            ->orderBy('name')
            ->get(['id', 'name']);

        $jobTypes = JobType::where('medical_institution_id', $institution->id)
            ->orderBy('name')
            ->get(['id', 'name']);

        return response()->json([
            'departments' => $departments,
            'job_types' => $jobTypes,
        ]);
    }
}
