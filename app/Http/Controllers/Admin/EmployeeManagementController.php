<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Employee;
use App\Models\MedicalInstitution;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EmployeeManagementController extends Controller
{
    /**
     * Check if user is system admin
     */
    private function checkSystemAdmin()
    {
        $user = request()->user();
        $admin = $user->adminUser;
        
        if (!$admin || $admin->role !== 'system_admin') {
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
        ]);
        
        return view('admin.employees.show', [
            'employee' => $employee,
        ]);
    }
}

