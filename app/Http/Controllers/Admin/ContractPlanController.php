<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContractPlan;
use App\Models\UniformProduct;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use App\Support\MasterCodeGenerator;
use App\Support\Pagination;
use Illuminate\Validation\Rule;

class ContractPlanController extends Controller
{
    public function index(Request $request): View
    {
        $plans = ContractPlan::withCount('uniformProducts')
            ->orderBy('code')
            ->paginate(Pagination::perPage($request))->withQueryString();

        return view('admin.contract-plans.index', compact('plans'));
    }

    public function create(): View
    {
        $products = UniformProduct::orderBy('product_code')->get();

        return view('admin.contract-plans.create', compact('products'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:50', 'unique:contract_plans,code'],
            'rentable_points' => ['required', 'integer', 'min:0'],
            'unit_price_ex_tax' => ['required', 'numeric', 'min:0'],
            'job_type_label' => ['nullable', 'string', 'max:255'],
            'work_style' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
            'uniform_product_ids' => ['nullable', 'array'],
            'uniform_product_ids.*' => ['integer', 'exists:uniform_products,id'],
        ]);

        $data['code'] = MasterCodeGenerator::ensure($data['code'] ?? null, 'contract_plan');
        $data['is_active'] = $request->boolean('is_active', true);
        $productIds = $data['uniform_product_ids'] ?? [];
        unset($data['uniform_product_ids']);

        $plan = ContractPlan::create($data);
        $plan->uniformProducts()->sync($productIds);

        return redirect()->route('admin.contract-plans.index')->with('status', '契約プランを登録しました。');
    }

    public function show(ContractPlan $contractPlan): View
    {
        $contractPlan->load('uniformProducts');

        return view('admin.contract-plans.show', ['plan' => $contractPlan]);
    }

    public function edit(ContractPlan $contractPlan): View
    {
        $contractPlan->load('uniformProducts');
        $products = UniformProduct::orderBy('product_code')->get();

        return view('admin.contract-plans.edit', [
            'plan' => $contractPlan,
            'products' => $products,
        ]);
    }

    public function update(Request $request, ContractPlan $contractPlan): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:50', Rule::unique('contract_plans', 'code')->ignore($contractPlan->id)],
            'rentable_points' => ['required', 'integer', 'min:0'],
            'unit_price_ex_tax' => ['required', 'numeric', 'min:0'],
            'job_type_label' => ['nullable', 'string', 'max:255'],
            'work_style' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
            'uniform_product_ids' => ['nullable', 'array'],
            'uniform_product_ids.*' => ['integer', 'exists:uniform_products,id'],
        ]);

        $data['code'] = MasterCodeGenerator::ensure($data['code'] ?? null, 'contract_plan');
        $data['is_active'] = $request->boolean('is_active');
        $productIds = $data['uniform_product_ids'] ?? [];
        unset($data['uniform_product_ids']);

        $contractPlan->update($data);
        $contractPlan->uniformProducts()->sync($productIds);

        return redirect()->route('admin.contract-plans.index')->with('status', '契約プランを更新しました。');
    }

    public function destroy(ContractPlan $contractPlan): RedirectResponse
    {
        $contractPlan->update(['is_active' => false]);

        return redirect()->route('admin.contract-plans.index')->with('status', '契約プランを無効化しました。');
    }
}
