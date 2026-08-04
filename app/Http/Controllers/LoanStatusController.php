<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Services\LoanInventoryService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LoanStatusController extends Controller
{
    public function index(Request $request, LoanInventoryService $loanInventory): View
    {
        $user = $request->user();
        $customer = $user->customerUser;

        if (! $customer) {
            abort(403, __('pages.flash_5c50f8ee3f'));
        }

        $departmentId = $request->integer('department_id') ?: null;
        if ($customer->role === 'department_admin' && $customer->department_id) {
            $departmentId = (int) $customer->department_id;
        }

        $filters = [
            'medical_institution_id' => (int) $customer->medical_institution_id,
            'department_id' => $departmentId,
            'q' => $request->string('q')->toString() ?: null,
            'product_q' => $request->string('product_q')->toString() ?: null,
        ];

        $result = $loanInventory->searchActiveLoans($request, $filters);

        $departments = Department::query()
            ->where('medical_institution_id', $customer->medical_institution_id)
            ->when($customer->role === 'department_admin' && $customer->department_id, function ($q) use ($customer) {
                $q->where('id', $customer->department_id);
            })
            ->orderBy('name')
            ->get();

        return view('loan-status.index', [
            'mode' => 'hospital',
            'loans' => $result['loans'],
            'stats' => [
                'total_quantity' => $result['total_quantity'],
                'employee_count' => $result['employee_count'],
                'department_count' => $result['department_count'],
                'sku_count' => $result['sku_count'],
            ],
            'departments' => $departments,
            'institutions' => collect(),
            'filters' => [
                'medical_institution_id' => $filters['medical_institution_id'],
                'department_id' => $departmentId,
                'q' => $filters['q'] ?? '',
                'product_q' => $filters['product_q'] ?? '',
            ],
            'lockDepartment' => $customer->role === 'department_admin',
            'employeeShowRoute' => 'employees.show',
            'departmentShowRoute' => 'departments.loans',
            'formAction' => route('loan-status.index'),
        ]);
    }
}
