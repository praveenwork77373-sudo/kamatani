<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\CustomerContractPlan;
use App\Models\EmployeeContractPlan;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Hospital read-only view of contracted plans and limits (SPEC: fix 403 menu).
 */
class ContractPlanController extends Controller
{
    public function index(Request $request): View
    {
        $customer = $request->user()->customerUser;
        if (!$customer || $customer->role !== 'institution_admin') {
            abort(403, __('pages.flash_4b6ac15711'));
        }

        $plans = CustomerContractPlan::with(['contractPlan.uniformProducts'])
            ->where('medical_institution_id', $customer->medical_institution_id)
            ->where('status', 'confirmed')
            ->orderBy('id')
            ->get();

        $assignmentCounts = EmployeeContractPlan::query()
            ->whereIn('customer_contract_plan_id', $plans->pluck('id'))
            ->whereHas('employee', function ($q) {
                $q->where('status', 'employed');
            })
            ->selectRaw('customer_contract_plan_id, count(*) as assigned_count')
            ->groupBy('customer_contract_plan_id')
            ->pluck('assigned_count', 'customer_contract_plan_id');

        return view('customer.contract-plans.index', [
            'plans' => $plans,
            'assignmentCounts' => $assignmentCounts,
            'institution' => $customer->medicalInstitution,
        ]);
    }
}
