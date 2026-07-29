<?php

namespace App\Http\Controllers;

use App\Models\CustomerUser;
use App\Models\Department;
use App\Models\Employee;
use App\Models\EmployeePosition;
use App\Models\JobType;
use App\Models\MedicalInstitution;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;

class EmployeeController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        /** @var CustomerUser|null $customer */
        $customer = $user->customerUser;

        if (!$customer) {
            abort(403, '医療機関ユーザーとしてログインしてください。');
        }

        $query = Employee::query()
            ->with(['primaryDepartment'])
            ->where('medical_institution_id', $customer->medical_institution_id);

        // Department admin can only see their own department
        if ($customer->role === 'department_admin' && $customer->department_id) {
            $query->where('primary_department_id', $customer->department_id);
        }
        
        // Institution admin can filter by department
        if ($customer->role === 'institution_admin' && $departmentId = $request->input('department_id')) {
            $query->where('primary_department_id', $departmentId);
        }

        if ($search = $request->string('q')->toString()) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('staff_no', 'like', "%{$search}%");
            });
        }
        
        // Status filter
        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        $employees = $query->orderBy('staff_no')->paginate(20)->withQueryString();

        // Calculate statistics
        $statsQuery = Employee::query()
            ->where('medical_institution_id', $customer->medical_institution_id);
        
        // Department admin can only see their own department
        if ($customer->role === 'department_admin' && $customer->department_id) {
            $statsQuery->where('primary_department_id', $customer->department_id);
        }
        
        // Institution admin can filter by department
        if ($customer->role === 'institution_admin' && $departmentId = $request->input('department_id')) {
            $statsQuery->where('primary_department_id', $departmentId);
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
        $user = $request->user();
        $customer = $user->customerUser;
        $adminUser = $user->adminUser;

        // Check if user is customer user (institution_admin or department_admin) or admin user
        $isCustomer = $customer && in_array($customer->role, ['institution_admin', 'department_admin'], true);
        $isAdmin = $adminUser && in_array($adminUser->role, ['system_admin', 'operation', 'sales', 'accounting'], true);

        if (!$isCustomer && !$isAdmin) {
            abort(403, '職員を追加できる権限がありません。');
        }

        // Get institution ID
        $institutionId = null;
        $institutions = collect();

        if ($isCustomer) {
            // Customer users: use their institution
            $institutionId = $customer->medical_institution_id;
        } elseif ($isAdmin) {
            // Admin users: can select any institution
            $institutions = MedicalInstitution::orderBy('name')->get();
            // If institution is provided in request, use it
            $institutionId = $request->input('medical_institution_id');
        }

        // Get departments and job types based on institution
        $departments = collect();
        $jobTypes = collect();

        if ($institutionId) {
            $departmentsQuery = Department::where('medical_institution_id', $institutionId);

            // 部署管理者は自部署のみ選択肢として表示
            if ($isCustomer && $customer->role === 'department_admin' && $customer->department_id) {
                $departmentsQuery->where('id', $customer->department_id);
            }

            $departments = $departmentsQuery->get();
            $jobTypes = JobType::where('medical_institution_id', $institutionId)->get();
        }

        return view('employees.create', [
            'departments' => $departments,
            'jobTypes' => $jobTypes,
            'customer' => $customer,
            'adminUser' => $adminUser,
            'institutions' => $institutions,
            'selectedInstitutionId' => $institutionId,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();
        $customer = $user->customerUser;
        $adminUser = $user->adminUser;

        // Check if user is customer user or admin user
        $isCustomer = $customer && in_array($customer->role, ['institution_admin', 'department_admin'], true);
        $isAdmin = $adminUser && in_array($adminUser->role, ['system_admin', 'operation', 'sales', 'accounting'], true);

        if (!$isCustomer && !$isAdmin) {
            abort(403);
        }

        // Determine institution ID
        $institutionId = null;
        if ($isCustomer) {
            $institutionId = $customer->medical_institution_id;
        } elseif ($isAdmin) {
            $institutionId = $request->input('medical_institution_id');
            if (!$institutionId) {
                return back()->withErrors(['medical_institution_id' => '医療機関を選択してください。']);
            }
            // Validate institution exists
            if (!MedicalInstitution::find($institutionId)) {
                return back()->withErrors(['medical_institution_id' => '選択された医療機関が存在しません。']);
            }
        }

        $rules = [
            'staff_no' => ['required', 'string', 'max:50', 'unique:employees,staff_no,NULL,id,medical_institution_id,' . $institutionId],
            'name' => ['required', 'string', 'max:255'],
            'gender' => ['nullable', 'in:male,female,other'],
            'top_size' => ['nullable', 'max:50'],
            'bottom_size' => ['nullable', 'max:50'],
            'shoe_size' => ['nullable', 'max:50'],
            'status' => ['required', 'in:employed,on_leave,retired'],
        ];

        // Add institution validation for admin users
        if ($isAdmin) {
            $rules['medical_institution_id'] = ['required', 'exists:medical_institutions,id'];
        }

        // 全体管理者のみ部署選択可能。部署管理者は自部署に固定。
        if ($isCustomer && $customer->role === 'institution_admin') {
            $rules['primary_department_id'] = ['required', 'exists:departments,id'];
        } elseif ($isAdmin) {
            $rules['primary_department_id'] = ['required', 'exists:departments,id'];
        }

        $data = $request->validate($rules);

        $data['medical_institution_id'] = $institutionId;

        if ($isCustomer && $customer->role === 'department_admin') {
            $data['primary_department_id'] = $customer->department_id;
        }

        DB::transaction(function () use ($data, $request, $customer, $isCustomer, $institutionId, &$employee) {
            $employee = Employee::create($data);

            $positions = $request->input('positions', []);
            $index = 0;
            foreach ($positions as $pos) {
                $deptId = $pos['department_id'] ?? null;
                $jobTypeId = $pos['job_type_id'] ?? null;
                if (!$deptId || !$jobTypeId) {
                    continue;
                }

                // 部署管理者は自部署のみ
                if ($isCustomer && $customer->role === 'department_admin' && $customer->department_id && (int)$deptId !== (int)$customer->department_id) {
                    continue;
                }

                // Validate department belongs to the institution
                $dept = Department::find($deptId);
                if (!$dept || $dept->medical_institution_id != $institutionId) {
                    continue;
                }

                // Validate job type belongs to the institution
                $jobType = JobType::find($jobTypeId);
                if (!$jobType || $jobType->medical_institution_id != $institutionId) {
                    continue;
                }

                EmployeePosition::create([
                    'employee_id' => $employee->id,
                    'department_id' => $deptId,
                    'job_type_id' => $jobTypeId,
                ]);

                // 最初のポジションを主部署として設定（まだ設定されていない場合）
                if ($index === 0 && !$employee->primary_department_id) {
                    $employee->primary_department_id = $deptId;
                    $employee->save();
                }
                $index++;
            }
        });

        return redirect()
            ->route('employees.index')
            ->with('status', '職員を登録しました。');
    }

    public function show(Employee $employee): View
    {
        $this->authorize('view', $employee);

        $user = request()->user();
        $customer = $user->customerUser;

        $employee->load(['primaryDepartment', 'positions.department', 'positions.jobType']);

        return view('employees.show', [
            'employee' => $employee,
            'customer' => $customer,
        ]);
    }

    public function edit(Employee $employee): View
    {
        $this->authorize('update', $employee);

        $user = request()->user();
        $customer = $user->customerUser;

        return view('employees.edit', [
            'employee' => $employee,
            'customer' => $customer,
            'departments' => Department::where('medical_institution_id', $customer->medical_institution_id)->get(),
            'jobTypes' => JobType::where('medical_institution_id', $customer->medical_institution_id)->get(),
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

        // 部署管理者は自部署の職員のみ編集可能
        if ($customer->role === 'department_admin' && (int) $employee->primary_department_id !== (int) $customer->department_id) {
            abort(403);
        }

        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'gender' => ['nullable', 'in:male,female,other'],
            'top_size' => ['nullable', 'max:50'],
            'bottom_size' => ['nullable', 'max:50'],
            'shoe_size' => ['nullable', 'max:50'],
            'status' => ['required', 'in:employed,on_leave,retired'],
        ];

        // 全体管理者のみ部署変更可能
        if ($customer->role === 'institution_admin') {
            $rules['primary_department_id'] = ['required', 'exists:departments,id'];
        }

        $data = $request->validate($rules);

        DB::transaction(function () use ($data, $request, $employee, $customer) {
            $employee->update($data);

            // 職種・兼務ポジションを更新（既存を一旦削除して再作成）
            $employee->positions()->delete();

            $positions = $request->input('positions', []);
            $index = 0;
            foreach ($positions as $pos) {
                $deptId = $pos['department_id'] ?? null;
                $jobTypeId = $pos['job_type_id'] ?? null;
                if (!$deptId || !$jobTypeId) {
                    continue;
                }

                // 部署管理者は自部署のみ
                if ($customer->role === 'department_admin' && $customer->department_id && (int)$deptId !== (int)$customer->department_id) {
                    continue;
                }

                EmployeePosition::create([
                    'employee_id' => $employee->id,
                    'department_id' => $deptId,
                    'job_type_id' => $jobTypeId,
                ]);

                // 最初のポジションを主部署として設定（全体管理者のみ変更可能）
                if ($index === 0 && $customer->role === 'institution_admin') {
                    $employee->primary_department_id = $deptId;
                    $employee->save();
                }
                $index++;
            }
        });

        return redirect()
            ->route('employees.index')
            ->with('status', '職員情報を更新しました。');
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

        // 部署管理者は自部署の職員のみ削除可能
        if ($customer->role === 'department_admin' && $customer->department_id
            && (int) $employee->primary_department_id !== (int) $customer->department_id) {
            abort(403);
        }

        $employee->delete();

        return redirect()
            ->route('employees.index')
            ->with('status', '職員を削除しました。');
    }

    public function exportCsv(Request $request)
    {
        $user = $request->user();
        $customer = $user->customerUser;

        if (!$customer || !in_array($customer->role, ['institution_admin', 'department_admin'], true)) {
            abort(403);
        }

        $query = Employee::where('medical_institution_id', $customer->medical_institution_id);

        // 部署管理者は自部署のみエクスポート
        if ($customer->role === 'department_admin' && $customer->department_id) {
            $query->where('primary_department_id', $customer->department_id);
        }

        if ($search = $request->string('q')->toString()) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('staff_no', 'like', "%{$search}%");
            });
        }

        $employees = $query->with('primaryDepartment')->orderBy('staff_no')->get();

        $filename = 'employees_' . date('YmdHis') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($employees) {
            $file = fopen('php://output', 'w');
            // BOM for Excel
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
            // Header
            fputcsv($file, [
                '職員番号', '氏名', '性別', '上衣サイズ', 'ズボンサイズ', '靴サイズ', '部署', '状態'
            ]);
            // Data
            foreach ($employees as $emp) {
                fputcsv($file, [
                    $emp->staff_no,
                    $emp->name,
                    $emp->gender === 'male' ? '男性' : ($emp->gender === 'female' ? '女性' : 'その他'),
                    $emp->top_size ?? '',
                    $emp->bottom_size ?? '',
                    $emp->shoe_size ?? '',
                    $emp->primaryDepartment?->name ?? '',
                    $emp->status === 'employed' ? '在籍' : ($emp->status === 'on_leave' ? '休職' : '退職'),
                ]);
            }
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
        $data = array_map('str_getcsv', file($path));

        // Skip header
        array_shift($data);

        DB::beginTransaction();
        try {
            $imported = 0;
            $updated = 0;
            $errors = [];

            foreach ($data as $index => $row) {
                if (count($row) < 3) continue;

                $staffNo = trim($row[0] ?? '');
                $name = trim($row[1] ?? '');
                if (empty($staffNo) || empty($name)) continue;

                $gender = null;
                if (isset($row[2])) {
                    $g = trim($row[2]);
                    if (strpos($g, '男') !== false) $gender = 'male';
                    elseif (strpos($g, '女') !== false) $gender = 'female';
                }

                $employee = Employee::where('staff_no', $staffNo)
                    ->where('medical_institution_id', $customer->medical_institution_id)
                    ->first();

                $data = [
                    'staff_no' => $staffNo,
                    'name' => $name,
                    'gender' => $gender,
                    'top_size' => trim($row[3] ?? ''),
                    'bottom_size' => trim($row[4] ?? ''),
                    'shoe_size' => trim($row[5] ?? ''),
                    'status' => isset($row[7]) && strpos($row[7], '退職') !== false ? 'retired' : 'employed',
                    'medical_institution_id' => $customer->medical_institution_id,
                ];

                if ($employee) {
                    // 部署管理者は自部署の職員のみ更新
                    if ($customer->role === 'department_admin' && $customer->department_id && $employee->primary_department_id !== $customer->department_id) {
                        $errors[] = "行" . ($index + 2) . ": 権限外の部署の職員のため更新できません（職員番号: {$staffNo}）";
                        continue;
                    }
                    $employee->update($data);
                    $updated++;
                } else {
                    // 新規作成時の部署決定
                    if ($customer->role === 'department_admin' && $customer->department_id) {
                        // 部署管理者は自部署に固定
                        $data['primary_department_id'] = $customer->department_id;
                    } else {
                        // 全体管理者の場合はCSVの部署名から取得（任意）
                        if (isset($row[6]) && !empty(trim($row[6]))) {
                            $dept = Department::where('name', trim($row[6]))
                                ->where('medical_institution_id', $customer->medical_institution_id)
                                ->first();
                            if ($dept) {
                                $data['primary_department_id'] = $dept->id;
                            }
                        }
                    }

                    if (empty($data['primary_department_id'] ?? null)) {
                        $errors[] = "行" . ($index + 2) . ": 部署が判定できないためスキップしました（職員番号: {$staffNo}）";
                        continue;
                    }
                    Employee::create($data);
                    $imported++;
                }
            }

            DB::commit();

            $message = "CSVインポート完了: 新規 {$imported} 件、更新 {$updated} 件";
            if (!empty($errors)) {
                $message .= '（一部スキップ: ' . count($errors) . ' 件）';
            }

            return redirect()
                ->route('employees.index')
                ->with('status', $message);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('CSV import error: ' . $e->getMessage());
            return redirect()
                ->route('employees.index')
                ->with('error', 'CSVインポート中にエラーが発生しました: ' . $e->getMessage());
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
