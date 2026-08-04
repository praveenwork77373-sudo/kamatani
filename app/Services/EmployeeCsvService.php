<?php

namespace App\Services;

use App\Models\CustomerContractPlan;
use App\Models\CustomerUser;
use App\Models\Department;
use App\Models\Employee;
use App\Models\EmployeeContractPlan;
use App\Models\EmployeePosition;
use App\Models\JobType;
use Illuminate\Support\Collection;

class EmployeeCsvService
{
    /**
     * @return list<string>
     */
    public function headers(): array
    {
        return [
            '職員番号', '氏名', '性別', '上衣サイズ', 'ズボンサイズ', '靴サイズ',
            '股下cm', '体型', '部署', '職種', '主所属', '入職日', '退職日', '状態', '契約プランコード',
        ];
    }

    /**
     * Write multi-row CSV (one row per affiliation; wide plans on a 職種-empty row when needed).
     *
     * @param  Collection<int, Employee>  $employees
     */
    public function writeExport($file, Collection $employees): void
    {
        fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
        fputcsv($file, $this->headers());

        $today = now()->toDateString();

        foreach ($employees as $emp) {
            $emp->loadMissing([
                'primaryDepartment',
                'positions.department',
                'positions.jobType',
                'contractPlanAssignments.customerContractPlan.contractPlan',
            ]);

            $active = $emp->contractPlanAssignments->filter(fn ($a) => $a->isActiveOn($today));
            $wideCodes = $active
                ->filter(fn ($a) => $a->employee_position_id === null)
                ->map(fn ($a) => $a->customerContractPlan?->contractPlan?->code)
                ->filter()
                ->unique()
                ->values();

            $positions = $emp->positions;
            if ($positions->isEmpty()) {
                fputcsv($file, $this->baseRow(
                    $emp,
                    $emp->primaryDepartment?->name ?? '',
                    '',
                    'Y',
                    $wideCodes->implode(',')
                ));

                continue;
            }

            foreach ($positions as $index => $position) {
                $isPrimary = (int) $emp->primary_department_id === (int) $position->department_id
                    || ($emp->primary_department_id === null && $index === 0);
                $posCodes = $active
                    ->filter(fn ($a) => (int) $a->employee_position_id === (int) $position->id)
                    ->map(fn ($a) => $a->customerContractPlan?->contractPlan?->code)
                    ->filter()
                    ->unique()
                    ->values();

                // Attach employee-wide plans to the primary affiliation row
                $codes = $posCodes;
                if ($isPrimary && $wideCodes->isNotEmpty()) {
                    $codes = $posCodes->merge($wideCodes)->unique()->values();
                }

                fputcsv($file, $this->baseRow(
                    $emp,
                    $position->department?->name ?? '',
                    $position->jobType?->name ?? '',
                    $isPrimary ? 'Y' : 'N',
                    $codes->implode(',')
                ));
            }

            // Wide-only leftover if no primary-marked row absorbed them (no primary match)
            $hasPrimaryRow = $positions->contains(
                fn ($p) => (int) $emp->primary_department_id === (int) $p->department_id
            );
            if (! $hasPrimaryRow && $wideCodes->isNotEmpty()) {
                fputcsv($file, $this->baseRow(
                    $emp,
                    $emp->primaryDepartment?->name ?? '',
                    '',
                    'Y',
                    $wideCodes->implode(',')
                ));
            }
        }
    }

    /**
     * @return array{imported:int, updated:int, errors:list<string>}
     */
    public function importRows(
        array $header,
        array $rows,
        int $institutionId,
        ?CustomerUser $customer = null
    ): array {
        $headerJoined = implode(',', $header);
        $isMulti = str_contains($headerJoined, '職種') || str_contains($headerJoined, '主所属');
        $isNewFormat = $isMulti || str_contains($headerJoined, '股下') || str_contains($headerJoined, '契約プラン');

        $imported = 0;
        $updated = 0;
        $errors = [];

        if ($isMulti) {
            $grouped = [];
            foreach ($rows as $index => $row) {
                if (count($row) < 3) {
                    continue;
                }
                $staffNo = trim($row[0] ?? '');
                if ($staffNo === '') {
                    continue;
                }
                $grouped[$staffNo][] = ['index' => $index, 'row' => $row];
            }

            foreach ($grouped as $staffNo => $entries) {
                try {
                    $result = $this->importMultiAffiliationEmployee(
                        $staffNo,
                        $entries,
                        $institutionId,
                        $customer
                    );
                    if ($result === 'imported') {
                        $imported++;
                    } elseif ($result === 'updated') {
                        $updated++;
                    } elseif (is_string($result)) {
                        $errors[] = $result;
                    }
                } catch (\Throwable $e) {
                    $errors[] = "職員番号 {$staffNo}: ".$e->getMessage();
                }
            }

            return compact('imported', 'updated', 'errors');
        }

        // Legacy / single-row (backward compatible)
        foreach ($rows as $index => $row) {
            if (count($row) < 3) {
                continue;
            }

            $staffNo = trim($row[0] ?? '');
            $name = trim($row[1] ?? '');
            if ($staffNo === '' || $name === '') {
                continue;
            }

            $gender = $this->parseGender(trim($row[2] ?? ''));

            $employee = Employee::where('staff_no', $staffNo)
                ->where('medical_institution_id', $institutionId)
                ->first();

            if ($isNewFormat) {
                $deptName = trim($row[8] ?? '');
                $statusRaw = trim($row[11] ?? '');
                $status = $this->parseStatus($statusRaw);
                $empData = [
                    'staff_no' => $staffNo,
                    'name' => $name,
                    'gender' => $gender,
                    'top_size' => trim($row[3] ?? '') ?: null,
                    'bottom_size' => trim($row[4] ?? '') ?: null,
                    'shoe_size' => trim($row[5] ?? '') ?: null,
                    'inseam_cm' => is_numeric(trim($row[6] ?? '')) ? (int) trim($row[6]) : null,
                    'body_type' => trim($row[7] ?? '') ?: null,
                    'join_date' => trim($row[9] ?? '') ?: null,
                    'retire_date' => trim($row[10] ?? '') ?: null,
                    'status' => $status,
                    'medical_institution_id' => $institutionId,
                ];
                $planCodesRaw = trim($row[12] ?? '');
            } else {
                $deptName = trim($row[6] ?? '');
                $statusRaw = trim($row[7] ?? '');
                $empData = [
                    'staff_no' => $staffNo,
                    'name' => $name,
                    'gender' => $gender,
                    'top_size' => trim($row[3] ?? '') ?: null,
                    'bottom_size' => trim($row[4] ?? '') ?: null,
                    'shoe_size' => trim($row[5] ?? '') ?: null,
                    'status' => str_contains($statusRaw, '退職') ? 'retired' : 'employed',
                    'medical_institution_id' => $institutionId,
                ];
                $planCodesRaw = '';
            }

            if ($employee) {
                if ($customer && $customer->role === 'department_admin' && $customer->department_id
                    && ! $employee->isAffiliatedWithDepartment((int) $customer->department_id)) {
                    $errors[] = '行'.($index + 2).": 権限外の部署の職員のため更新できません（職員番号: {$staffNo}）";
                    continue;
                }
                $employee->update($empData);
                $updated++;
            } else {
                if ($customer && $customer->role === 'department_admin' && $customer->department_id) {
                    $empData['primary_department_id'] = $customer->department_id;
                } elseif ($deptName !== '') {
                    $dept = Department::where('name', $deptName)
                        ->where('medical_institution_id', $institutionId)
                        ->first();
                    if ($dept) {
                        $empData['primary_department_id'] = $dept->id;
                    }
                }

                if (empty($empData['primary_department_id'] ?? null)) {
                    $errors[] = '行'.($index + 2).": 部署が判定できないためスキップしました（職員番号: {$staffNo}）";
                    continue;
                }
                $employee = Employee::create($empData);
                $imported++;
            }

            if ($isNewFormat && $employee) {
                $planIds = $this->resolvePlanIds($institutionId, $planCodesRaw);
                $this->syncEmployeeWidePlans($employee, $planIds, $institutionId);
            }
        }

        return compact('imported', 'updated', 'errors');
    }

    /**
     * @param  list<array{index:int,row:array}>  $entries
     */
    private function importMultiAffiliationEmployee(
        string $staffNo,
        array $entries,
        int $institutionId,
        ?CustomerUser $customer
    ): string {
        $firstRow = $entries[0]['row'];
        $name = trim($firstRow[1] ?? '');
        if ($name === '') {
            return "職員番号 {$staffNo}: 氏名が空のためスキップ";
        }

        $employee = Employee::where('staff_no', $staffNo)
            ->where('medical_institution_id', $institutionId)
            ->first();

        if ($employee && $customer && $customer->role === 'department_admin' && $customer->department_id
            && ! $employee->isAffiliatedWithDepartment((int) $customer->department_id)) {
            return '行'.($entries[0]['index'] + 2).": 権限外の部署の職員のため更新できません（職員番号: {$staffNo}）";
        }

        // Multi headers: 0番号 1氏名 2性別 3上衣 4ズボン 5靴 6股下 7体型 8部署 9職種 10主所属 11入職 12退職 13状態 14プラン
        $gender = null;
        $empData = [
            'staff_no' => $staffNo,
            'name' => $name,
            'medical_institution_id' => $institutionId,
        ];
        foreach ($entries as $entry) {
            $row = $entry['row'];
            $gender = $this->parseGender(trim($row[2] ?? '')) ?? $gender;
            foreach ([
                3 => 'top_size',
                4 => 'bottom_size',
                5 => 'shoe_size',
                7 => 'body_type',
            ] as $col => $field) {
                $val = trim($row[$col] ?? '');
                if ($val !== '') {
                    $empData[$field] = $val;
                }
            }
            if (is_numeric(trim($row[6] ?? ''))) {
                $empData['inseam_cm'] = (int) trim($row[6]);
            }
            if (trim($row[11] ?? '') !== '') {
                $empData['join_date'] = trim($row[11]) ?: null;
            }
            if (trim($row[12] ?? '') !== '') {
                $empData['retire_date'] = trim($row[12]) ?: null;
            }
            if (trim($row[13] ?? '') !== '') {
                $empData['status'] = $this->parseStatus(trim($row[13]));
            }
        }
        $empData['gender'] = $gender;
        $empData['status'] = $empData['status'] ?? 'employed';

        $wasNew = ! $employee;
        if ($employee) {
            $employee->update($empData);
        } else {
            $employee = Employee::create($empData);
        }

        $desiredPositionKeys = [];
        $desiredPlanPairs = [];
        $primaryDeptId = null;
        $widePlanIds = [];

        foreach ($entries as $entry) {
            $row = $entry['row'];
            $deptName = trim($row[8] ?? '');
            $jobName = trim($row[9] ?? '');
            $isPrimary = in_array(mb_strtoupper(trim($row[10] ?? '')), ['Y', 'YES', '1', 'TRUE', '主', '主所属'], true);
            $planCodesRaw = trim($row[14] ?? '');

            if ($customer && $customer->role === 'department_admin' && $customer->department_id) {
                // Dept admin may only upsert their own department rows
                if ($deptName !== '') {
                    $dept = Department::where('name', $deptName)
                        ->where('medical_institution_id', $institutionId)
                        ->first();
                    if (! $dept || (int) $dept->id !== (int) $customer->department_id) {
                        continue;
                    }
                } else {
                    continue;
                }
            }

            if ($deptName === '' && $jobName === '') {
                // Wide plans only
                $widePlanIds = array_merge($widePlanIds, $this->resolvePlanIds($institutionId, $planCodesRaw));
                continue;
            }

            if ($deptName === '') {
                continue;
            }

            $dept = Department::where('name', $deptName)
                ->where('medical_institution_id', $institutionId)
                ->first();
            if (! $dept) {
                continue;
            }

            if ($isPrimary || $primaryDeptId === null) {
                $primaryDeptId = (int) $dept->id;
            }

            if ($jobName === '') {
                // Department without job type → treat plans as employee-wide, still set primary
                $widePlanIds = array_merge($widePlanIds, $this->resolvePlanIds($institutionId, $planCodesRaw));
                continue;
            }

            $jobType = JobType::where('name', $jobName)
                ->where('medical_institution_id', $institutionId)
                ->first();
            if (! $jobType) {
                continue;
            }

            $position = EmployeePosition::firstOrCreate([
                'employee_id' => $employee->id,
                'department_id' => $dept->id,
                'job_type_id' => $jobType->id,
            ]);

            $desiredPositionKeys[] = $position->id;

            foreach ($this->resolvePlanIds($institutionId, $planCodesRaw) as $ccpId) {
                $desiredPlanPairs[] = [
                    'customer_contract_plan_id' => $ccpId,
                    'employee_position_id' => (int) $position->id,
                ];
            }
        }

        if ($customer && $customer->role === 'department_admin' && $customer->department_id) {
            // Preserve foreign positions + their plans
            $foreignPositions = EmployeePosition::where('employee_id', $employee->id)
                ->where('department_id', '!=', $customer->department_id)
                ->get();
            foreach ($foreignPositions as $fp) {
                $desiredPositionKeys[] = $fp->id;
            }
            $today = now()->toDateString();
            $foreignPairs = $employee->contractPlanAssignments()
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
            $desiredPlanPairs = array_merge($desiredPlanPairs, $foreignPairs);

            // Preserve existing wide plans for dept admin imports
            $existingWide = $employee->contractPlanAssignments()
                ->whereNull('employee_position_id')
                ->where(function ($q) use ($today) {
                    $q->whereNull('assigned_until')->orWhere('assigned_until', '>=', $today);
                })
                ->pluck('customer_contract_plan_id')
                ->map(fn ($id) => (int) $id)
                ->all();
            $widePlanIds = array_merge($widePlanIds, $existingWide);
        } else {
            // Institution/admin: replace positions not listed
            $keep = array_unique($desiredPositionKeys);
            if (! empty($keep)) {
                EmployeePosition::where('employee_id', $employee->id)
                    ->whereNotIn('id', $keep)
                    ->delete();
            } elseif (! empty($entries)) {
                // Only delete all if we intentionally had affiliation rows but none resolved
            }
        }

        if ($primaryDeptId && (! $customer || $customer->role !== 'department_admin')) {
            $employee->primary_department_id = $primaryDeptId;
            $employee->save();
        } elseif ($primaryDeptId && ! $employee->primary_department_id) {
            $employee->primary_department_id = $primaryDeptId;
            $employee->save();
        } elseif (! $employee->primary_department_id && $customer?->department_id) {
            $employee->primary_department_id = $customer->department_id;
            $employee->save();
        }

        if (empty($employee->primary_department_id)) {
            return "職員番号 {$staffNo}: 主所属部署が判定できないためスキップ";
        }

        foreach (array_unique($widePlanIds) as $ccpId) {
            $desiredPlanPairs[] = [
                'customer_contract_plan_id' => (int) $ccpId,
                'employee_position_id' => null,
            ];
        }

        $this->syncPlanAssignments($employee, $desiredPlanPairs, $institutionId);

        return $wasNew ? 'imported' : 'updated';
    }

    /**
     * @param  list<array{customer_contract_plan_id:int, employee_position_id:?int}>  $desiredPairs
     */
    public function syncPlanAssignments(Employee $employee, array $desiredPairs, int $institutionId): void
    {
        $validIds = CustomerContractPlan::where('medical_institution_id', $institutionId)
            ->where('status', 'confirmed')
            ->pluck('id')
            ->all();

        $desired = [];
        foreach ($desiredPairs as $item) {
            $ccpId = (int) ($item['customer_contract_plan_id'] ?? 0);
            $posId = $item['employee_position_id'] ?? null;
            $posId = $posId !== null && $posId !== '' ? (int) $posId : null;
            if ($ccpId <= 0 || ! in_array($ccpId, $validIds, true)) {
                continue;
            }
            $desired[$ccpId.':'.($posId ?? '0')] = [
                'customer_contract_plan_id' => $ccpId,
                'employee_position_id' => $posId,
            ];
        }

        $today = now()->toDateString();
        $yesterday = now()->subDay()->toDateString();

        $activeAssignments = $employee->contractPlanAssignments()
            ->where(function ($q) use ($today) {
                $q->whereNull('assigned_until')->orWhere('assigned_until', '>=', $today);
            })
            ->get();

        foreach ($activeAssignments as $assignment) {
            $key = ((int) $assignment->customer_contract_plan_id).':'
                .($assignment->employee_position_id !== null ? (int) $assignment->employee_position_id : '0');
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

                return $aPos === $pair['employee_position_id'];
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

    /**
     * @param  list<int>  $planIds
     */
    public function syncEmployeeWidePlans(Employee $employee, array $planIds, int $institutionId): void
    {
        $today = now()->toDateString();
        $existingPositionPairs = $employee->contractPlanAssignments()
            ->whereNotNull('employee_position_id')
            ->where(function ($q) use ($today) {
                $q->whereNull('assigned_until')->orWhere('assigned_until', '>=', $today);
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

        $this->syncPlanAssignments($employee, array_merge($existingPositionPairs, $widePairs), $institutionId);
    }

    /**
     * @return list<int>
     */
    private function resolvePlanIds(int $institutionId, string $planCodesRaw): array
    {
        if ($planCodesRaw === '') {
            return [];
        }
        $codes = array_filter(array_map('trim', explode(',', $planCodesRaw)));
        if (empty($codes)) {
            return [];
        }

        return CustomerContractPlan::where('medical_institution_id', $institutionId)
            ->where('status', 'confirmed')
            ->whereHas('contractPlan', fn ($q) => $q->whereIn('code', $codes))
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    private function parseGender(string $g): ?string
    {
        if (str_contains($g, '男')) {
            return 'male';
        }
        if (str_contains($g, '女')) {
            return 'female';
        }
        if ($g !== '') {
            return 'other';
        }

        return null;
    }

    private function parseStatus(string $statusRaw): string
    {
        if (str_contains($statusRaw, '退職')) {
            return 'retired';
        }
        if (str_contains($statusRaw, '休職')) {
            return 'on_leave';
        }

        return 'employed';
    }

    /**
     * @return list<string|int|null>
     */
    private function baseRow(Employee $emp, string $dept, string $job, string $primary, string $plans): array
    {
        return [
            $emp->staff_no,
            $emp->name,
            $emp->gender === 'male' ? '男性' : ($emp->gender === 'female' ? '女性' : 'その他'),
            $emp->top_size ?? '',
            $emp->bottom_size ?? '',
            $emp->shoe_size ?? '',
            $emp->inseam_cm ?? '',
            $emp->body_type ?? '',
            $dept,
            $job,
            $primary,
            optional($emp->join_date)->format('Y-m-d') ?? '',
            optional($emp->retire_date)->format('Y-m-d') ?? '',
            $emp->status === 'employed' ? '在籍' : ($emp->status === 'on_leave' ? '休職' : '退職'),
            $plans,
        ];
    }
}
