<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Employee;
use App\Models\MedicalInstitution;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;
use App\Support\Pagination;

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

        // Filter by department (primary or concurrent)
        if ($departmentId = $request->input('department_id')) {
            $baseQuery->inDepartment((int) $departmentId);
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
            ->paginate(Pagination::perPage($request))
            ->withQueryString();

        // Calculate statistics
        $statsQuery = Employee::query();
        if ($institutionId) {
            $statsQuery->where('medical_institution_id', $institutionId);
        }
        if ($departmentId) {
            $statsQuery->inDepartment((int) $departmentId);
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
            $query->inDepartment((int) $departmentId);
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
            'positions.department',
            'positions.jobType',
            'contractPlanAssignments.customerContractPlan.contractPlan',
        ])->orderBy('staff_no')->get();

        $institution = MedicalInstitution::find($institutionId);
        $safeName = preg_replace('/[^\w\-]+/u', '_', $institution?->name ?? 'institution');
        $filename = 'employees_'.$safeName.'_'.date('YmdHis').'.csv';

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

        DB::beginTransaction();
        try {
            $result = app(\App\Services\EmployeeCsvService::class)->importRows(
                $header,
                $rows,
                $institutionId,
                null
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
}
