<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\ContractPlanConfirmedMail;
use App\Models\ContractPlan;
use App\Models\CustomerContractPlan;
use App\Models\MedicalInstitution;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;
use App\Support\Pagination;

class CustomerContractPlanController extends Controller
{
    public function index(Request $request): View
    {
        $query = CustomerContractPlan::with(['medicalInstitution', 'contractPlan'])
            ->orderByDesc('id');

        if ($institutionId = $request->input('medical_institution_id')) {
            $query->where('medical_institution_id', $institutionId);
        }

        $plans = $query->paginate(Pagination::perPage($request))->withQueryString();

        return view('admin.customer-contract-plans.index', [
            'plans' => $plans,
            'institutions' => MedicalInstitution::orderBy('name')->get(),
            'selectedInstitutionId' => $institutionId,
        ]);
    }

    public function create(Request $request): View
    {
        return view('admin.customer-contract-plans.create', [
            'institutions' => MedicalInstitution::orderBy('name')->get(),
            'contractPlans' => ContractPlan::where('is_active', true)->orderBy('code')->get(),
            'selectedInstitutionId' => $request->input('medical_institution_id'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'medical_institution_id' => ['required', 'exists:medical_institutions,id'],
            'contract_plan_id' => ['required', 'exists:contract_plans,id'],
            'contract_count' => ['required', 'integer', 'min:0'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'status' => ['required', 'in:draft,confirmed'],
            'notes' => ['nullable', 'string'],
        ]);

        if ($data['status'] === 'confirmed') {
            $data['confirmed_at'] = now();
            $data['confirmed_by_admin_user_id'] = $request->user()->adminUser?->id;
        }

        $plan = CustomerContractPlan::create($data);

        if ($plan->status === 'confirmed') {
            $this->sendConfirmedMail($plan->load(['contractPlan', 'medicalInstitution']));
        }

        return redirect()->route('admin.customer-contract-plans.index')
            ->with('status', __('pages.flash_3a148be24a'));
    }

    public function edit(CustomerContractPlan $customerContractPlan): View
    {
        return view('admin.customer-contract-plans.edit', [
            'plan' => $customerContractPlan,
            'institutions' => MedicalInstitution::orderBy('name')->get(),
            'contractPlans' => ContractPlan::orderBy('code')->get(),
        ]);
    }

    public function update(Request $request, CustomerContractPlan $customerContractPlan): RedirectResponse
    {
        $data = $request->validate([
            'medical_institution_id' => ['required', 'exists:medical_institutions,id'],
            'contract_plan_id' => ['required', 'exists:contract_plans,id'],
            'contract_count' => ['required', 'integer', 'min:0'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'status' => ['required', 'in:draft,confirmed'],
            'notes' => ['nullable', 'string'],
        ]);

        $wasConfirmed = $customerContractPlan->status === 'confirmed';
        if ($data['status'] === 'confirmed' && !$wasConfirmed) {
            $data['confirmed_at'] = now();
            $data['confirmed_by_admin_user_id'] = $request->user()->adminUser?->id;
        }
        if ($data['status'] === 'draft') {
            $data['confirmed_at'] = null;
            $data['confirmed_by_admin_user_id'] = null;
        }

        $customerContractPlan->update($data);

        if (! $wasConfirmed && $customerContractPlan->fresh()->status === 'confirmed') {
            $this->sendConfirmedMail($customerContractPlan->fresh(['contractPlan', 'medicalInstitution']));
        }

        return redirect()->route('admin.customer-contract-plans.index')
            ->with('status', __('pages.flash_09a17ec925'));
    }

    public function confirm(Request $request, CustomerContractPlan $customerContractPlan): RedirectResponse
    {
        $customerContractPlan->update([
            'status' => 'confirmed',
            'confirmed_at' => now(),
            'confirmed_by_admin_user_id' => $request->user()->adminUser?->id,
        ]);

        $this->sendConfirmedMail($customerContractPlan->fresh(['contractPlan', 'medicalInstitution']));

        return back()->with('status', __('pages.flash_6a1fab4274'));
    }

    private function sendConfirmedMail(CustomerContractPlan $customerContractPlan): void
    {
        $institution = $customerContractPlan->medicalInstitution;
        if (! $institution?->notification_email) {
            return;
        }

        try {
            Mail::to($institution->notification_email)->send(
                new ContractPlanConfirmedMail($institution, $customerContractPlan)
            );
        } catch (\Throwable $e) {
            Log::warning('Contract plan confirmed mail failed', [
                'ccp_id' => $customerContractPlan->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function destroy(CustomerContractPlan $customerContractPlan): RedirectResponse
    {
        $today = now()->toDateString();
        $activeAssignments = \App\Models\EmployeeContractPlan::query()
            ->where('customer_contract_plan_id', $customerContractPlan->id)
            ->where(function ($q) use ($today) {
                $q->whereNull('assigned_until')
                    ->orWhere('assigned_until', '>=', $today);
            })
            ->count();

        if ($activeAssignments > 0) {
            return redirect()
                ->route('admin.customer-contract-plans.index')
                ->with('error', __('pages.ccp_delete_blocked_active_assignments', ['count' => $activeAssignments]));
        }

        $customerContractPlan->delete();

        return redirect()->route('admin.customer-contract-plans.index')
            ->with('status', __('pages.flash_95a4097d9e'));
    }
}
