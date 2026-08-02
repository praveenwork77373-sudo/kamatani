<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CustomerContractPlan;
use App\Models\Department;
use App\Models\Employee;
use App\Models\EmployeeContractPlan;
use App\Models\MedicalInstitution;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EmployeeManagementController extends Controller
{
    /**
     * Check if user is system admin
     */
    private function checkSystemAdmin(): void
    {
        $user = request()->user();
        $admin = $user->adminUser;

        if (! $admin || $admin->role !== 'system_admin') {
            abort(403, 'システム管理者のみアクセス可能です。');
        }
    }

    public function index(Request $request): View
    {
        $this->checkSystemAdmin();

        $baseQuery = Employee::with(['primaryDepartment.medicalInstitution', 'positions.department', 'positions.jobType']);

        // Filter by medical institution
        if ($institutionId = $request->input('medical_institution_id')) {
            $baseQuery->where('medical_institution_id', $institutionId);
        }

        // Filter by department
        if ($departmentId = $request->input('department_id')) {
            $baseQuery->where('primary_department_id', $departmentId);
        }

        // Search
        if ($search = $request->string('q')->toString()) {
            $baseQuery->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('staff_no', 'like', "%{$search}%");
            });
        }

        // Status filter
        if ($status = $request->input('status')) {
            $baseQuery->where('status', $status);
        }

        $employees = (clone $baseQuery)->orderBy('medical_institution_id')
            ->orderBy('staff_no')
            ->paginate(30)
            ->withQueryString();

        // Calculate statistics
        $statsQuery = Employee::query();
        if ($institutionId) {
            $statsQuery->where('medical_institution_id', $institutionId);
        }
        if ($departmentId) {
            $statsQuery->where('primary_department_id', $departmentId);
        }

        $totalEmployees = (clone $statsQuery)->count();
        $employedEmployees = (clone $statsQuery)->where('status', 'employed')->count();
        $onLeaveEmployees = (clone $statsQuery)->where('status', 'on_leave')->count();
        $retiredEmployees = (clone $statsQuery)->where('status', 'retired')->count();
        $newEmployeesThisMonth = (clone $statsQuery)
            ->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()])
            ->count();

        $institutions = MedicalInstitution::orderBy('name')->get();
        $departments = collect();

        // Load departments for selected institution
        if ($institutionId) {
            $departments = Department::where('medical_institution_id', $institutionId)
                ->orderBy('name')
                ->get();
        }

        return view('admin.employees.index', [
            'employees' => $employees,
            'institutions' => $institutions,
            'departments' => $departments,
            'selectedInstitutionId' => $institutionId,
            'selectedDepartmentId' => $request->input('department_id'),
            'selectedStatus' => $status,
            'searchQuery' => $search,
            'stats' => [
                'total_employees' => $totalEmployees,
                'employed_employees' => $employedEmployees,
                'on_leave_employees' => $onLeaveEmployees,
                'retired_employees' => $retiredEmployees,
                'new_employees_this_month' => $newEmployeesThisMonth,
            ],
        ]);
    }

    public function show(Employee $employee): View
    {
        $this->checkSystemAdmin();

        $employee->load([
            'primaryDepartment.medicalInstitution',
            'positions.department',
            'positions.jobType',
            'contractPlanAssignments.customerContractPlan.contractPlan',
        ]);

        return view('admin.employees.show', [
            'employee' => $employee,
        ]);
    }

    public function exportCsv(Request $request): StreamedResponse
    {
        $this->checkSystemAdmin();

        $request->validate([
            'medical_institution_id' => ['required', 'exists:medical_institutions,id'],
        ], [
            'medical_institution_id.required' => 'CSV出力には医療機関の選択が必要です。',
        ]);

        $institutionId = (int) $request->input('medical_institution_id');

        $query = Employee::where('medical_institution_id', $institutionId);

        if ($departmentId = $request->input('department_id')) {
            $query->where('primary_department_id', $departmentId);
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        if ($search = $request->string('q')->toString()) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('staff_no', 'like', "%{$search}%");
            });
        }

        $employees = $query->with([
            'primaryDepartment',
            'contractPlanAssignments.customerContractPlan.contractPlan',
        ])->orderBy('staff_no')->get();

        $institution = MedicalInstitution::find($institutionId);
        $safeName = preg_replace('/[^\w\-]+/u', '_', $institution?->name ?? 'institution');
        $filename = 'employees_'.$safeName.'_'.date('YmdHis').'.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($employees) {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
            fputcsv($file, [
                '職員番号', '氏名', '性別', '上衣サイズ', 'ズボンサイズ', '靴サイズ',
                '股下cm', '体型', '部署', '入職日', '退職日', '状態', '契約プランコード',
            ]);
            $today = now()->toDateString();
            foreach ($employees as $emp) {
                $planCodes = $emp->contractPlanAssignments
                    ->filter(fn ($a) => $a->isActiveOn($today))
                    ->map(fn ($a) => $a->customerContractPlan?->contractPlan?->code)
                    ->filter()
                    ->unique()
                    ->implode(',');

                fputcsv($file, [
                    $emp->staff_no,
                    $emp->name,
                    $emp->gender === 'male' ? '男性' : ($emp->gender === 'female' ? '女性' : 'その他'),
                    $emp->top_size ?? '',
                    $emp->bottom_size ?? '',
                    $emp->shoe_size ?? '',
                    $emp->inseam_cm ?? '',
                    $emp->body_type ?? '',
                    $emp->primaryDepartment?->name ?? '',
                    optional($emp->join_date)->format('Y-m-d') ?? '',
                    optional($emp->retire_date)->format('Y-m-d') ?? '',
                    $emp->status === 'employed' ? '在籍' : ($emp->status === 'on_leave' ? '休職' : '退職'),
                    $planCodes,
                ]);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function importCsv(Request $request): RedirectResponse
    {
        $this->checkSystemAdmin();

        $request->validate([
            'medical_institution_id' => ['required', 'exists:medical_institutions,id'],
            'csv_file' => ['required', 'file', 'mimes:csv,txt', 'max:10240'],
        ], [
            'medical_institution_id.required' => 'CSV取込には医療機関の選択が必要です。',
        ]);

        $institutionId = (int) $request->input('medical_institution_id');
        $file = $request->file('csv_file');
        $path = $file->getRealPath();
        $rows = array_map('str_getcsv', file($path));

        $header = array_shift($rows) ?? [];
        $headerJoined = implode(',', $header);
        $isNewFormat = str_contains($headerJoined, '股下') || str_contains($headerJoined, '契約プラン');

        DB::beginTransaction();
        try {
            $imported = 0;
            $updated = 0;
            $errors = [];

            foreach ($rows as $index => $row) {
                if (count($row) < 3) {
                    continue;
                }

                $staffNo = trim($row[0] ?? '');
                $name = trim($row[1] ?? '');
                if ($staffNo === '' || $name === '') {
                    continue;
                }

                $gender = null;
                if (isset($row[2])) {
                    $g = trim($row[2]);
                    if (str_contains($g, '男')) {
                        $gender = 'male';
                    } elseif (str_contains($g, '女')) {
                        $gender = 'female';
                    }
                }

                $employee = Employee::where('staff_no', $staffNo)
                    ->where('medical_institution_id', $institutionId)
                    ->first();

                if ($isNewFormat) {
                    $deptName = trim($row[8] ?? '');
                    $statusRaw = trim($row[11] ?? '');
                    $status = str_contains($statusRaw, '退職') ? 'retired'
                        : (str_contains($statusRaw, '休職') ? 'on_leave' : 'employed');
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

                if ($deptName !== '') {
                    $dept = Department::where('name', $deptName)
                        ->where('medical_institution_id', $institutionId)
                        ->first();
                    if ($dept) {
                        $empData['primary_department_id'] = $dept->id;
                    }
                }

                if ($employee) {
                    $employee->update($empData);
                    $updated++;
                } else {
                    if (empty($empData['primary_department_id'] ?? null)) {
                        $errors[] = '行'.($index + 2).": 部署が判定できないためスキップしました（職員番号: {$staffNo}）";
                        continue;
                    }
                    $employee = Employee::create($empData);
                    $imported++;
                }

                if ($isNewFormat && $employee) {
                    $planIds = [];
                    if ($planCodesRaw !== '') {
                        $codes = array_filter(array_map('trim', explode(',', $planCodesRaw)));
                        if (! empty($codes)) {
                            $planIds = CustomerContractPlan::where('medical_institution_id', $institutionId)
                                ->where('status', 'confirmed')
                                ->whereHas('contractPlan', fn ($q) => $q->whereIn('code', $codes))
                                ->pluck('id')
                                ->all();
                        }
                    }
                    $this->syncEmployeeWidePlans($employee, $planIds, $institutionId);
                }
            }

            DB::commit();

            $message = "CSVインポート完了: 新規 {$imported} 件、更新 {$updated} 件";
            if (! empty($errors)) {
                $message .= '（一部スキップ: '.count($errors).' 件）';
            }

            return redirect()
                ->route('admin.employees.index', ['medical_institution_id' => $institutionId])
                ->with('status', $message);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Admin CSV import error: '.$e->getMessage());

            return redirect()
                ->route('admin.employees.index', ['medical_institution_id' => $institutionId])
                ->with('error', 'CSVインポート中にエラーが発生しました: '.$e->getMessage());
        }
    }

    /**
     * Replace employee-wide (null position) plan assignments; keep position-linked.
     *
     * @param  array<int, int>  $planIds
     */
    private function syncEmployeeWidePlans(Employee $employee, array $planIds, int $institutionId): void
    {
        $validIds = CustomerContractPlan::where('medical_institution_id', $institutionId)
            ->where('status', 'confirmed')
            ->whereIn('id', $planIds)
            ->pluck('id')
            ->all();

        $today = now()->toDateString();
        $yesterday = now()->subDay()->toDateString();

        $activeWide = $employee->contractPlanAssignments()
            ->whereNull('employee_position_id')
            ->where(function ($q) use ($today) {
                $q->whereNull('assigned_until')
                    ->orWhere('assigned_until', '>=', $today);
            })
            ->get();

        foreach ($activeWide as $assignment) {
            if (! in_array((int) $assignment->customer_contract_plan_id, $validIds, true)) {
                $assignment->update(['assigned_until' => $yesterday]);
            }
        }

        foreach ($validIds as $ccpId) {
            $hasActive = $activeWide->contains(
                fn (EmployeeContractPlan $a) => (int) $a->customer_contract_plan_id === (int) $ccpId
                    && $a->isActiveOn($today)
            );

            if (! $hasActive) {
                EmployeeContractPlan::create([
                    'employee_id' => $employee->id,
                    'customer_contract_plan_id' => $ccpId,
                    'employee_position_id' => null,
                    'assigned_from' => $today,
                    'assigned_until' => null,
                ]);
            }
        }
    }
}
