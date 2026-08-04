<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\MedicalInstitution;
use App\Services\LoanInventoryService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LoanStatusController extends Controller
{
    public function index(Request $request, LoanInventoryService $loanInventory): View
    {
        $admin = $request->user()->adminUser;
        if (! $admin || ! in_array($admin->role, ['system_admin', 'operation'], true)) {
            abort(403, __('pages.flash_2fb39bebc0'));
        }

        $institutionId = $request->integer('medical_institution_id') ?: null;
        $departmentId = $request->integer('department_id') ?: null;

        $filters = [
            'medical_institution_id' => $institutionId,
            'department_id' => $departmentId,
            'q' => $request->string('q')->toString() ?: null,
            'product_q' => $request->string('product_q')->toString() ?: null,
        ];

        $result = $loanInventory->searchActiveLoans($request, $filters);

        $institutions = MedicalInstitution::query()->orderBy('name')->get();
        $departments = $institutionId
            ? Department::query()->where('medical_institution_id', $institutionId)->orderBy('name')->get()
            : collect();

        return view('loan-status.index', [
            'mode' => 'admin',
            'loans' => $result['loans'],
            'stats' => [
                'total_quantity' => $result['total_quantity'],
                'employee_count' => $result['employee_count'],
                'institution_count' => $result['institution_count'],
                'department_count' => $result['department_count'],
                'sku_count' => $result['sku_count'],
            ],
            'departments' => $departments,
            'institutions' => $institutions,
            'filters' => [
                'medical_institution_id' => $institutionId,
                'department_id' => $departmentId,
                'q' => $filters['q'] ?? '',
                'product_q' => $filters['product_q'] ?? '',
            ],
            'lockDepartment' => false,
            'employeeShowRoute' => 'admin.employees.show',
            'departmentShowRoute' => 'admin.departments.loans',
            'formAction' => route('admin.loan-status.index'),
        ]);
    }
}
