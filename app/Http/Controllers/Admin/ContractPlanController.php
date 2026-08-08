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
        $data = $this->validatedPlan($request);
        $data['code'] = MasterCodeGenerator::ensure($data['code'] ?? null, 'contract_plan');
        $data['is_active'] = $request->boolean('is_active', true);
        $productIds = $data['uniform_product_ids'] ?? [];
        unset($data['uniform_product_ids']);

        $plan = new ContractPlan($data);
        $plan->syncRentablePointsTotal();
        $plan->save();
        $plan->uniformProducts()->sync($this->productSyncPayload($productIds));

        return redirect()->route('admin.contract-plans.index')->with('status', __('pages.flash_100ce5f6c9'));
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
        $data = $this->validatedPlan($request, $contractPlan);
        unset($data['code']);
        $data['is_active'] = $request->boolean('is_active');
        $productIds = $data['uniform_product_ids'] ?? [];
        unset($data['uniform_product_ids']);

        $contractPlan->fill($data);
        $contractPlan->syncRentablePointsTotal();
        $contractPlan->save();
        $contractPlan->uniformProducts()->sync(
            $this->productSyncPayload($productIds, $contractPlan)
        );

        return redirect()->route('admin.contract-plans.index')->with('status', __('pages.flash_b24bf7b4f0'));
    }

    public function destroy(ContractPlan $contractPlan): RedirectResponse
    {
        $contractPlan->update(['is_active' => false]);

        return redirect()->route('admin.contract-plans.index')->with('status', __('pages.flash_970fead912'));
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedPlan(Request $request, ?ContractPlan $existing = null): array
    {
        $codeRule = ['nullable', 'string', 'max:50'];
        if ($existing) {
            $codeRule[] = Rule::unique('contract_plans', 'code')->ignore($existing->id);
        } else {
            $codeRule[] = 'unique:contract_plans,code';
        }

        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => $codeRule,
            'rentable_points' => ['nullable', 'integer', 'min:0'],
            'rentable_points_top' => ['nullable', 'integer', 'min:0'],
            'rentable_points_bottom' => ['nullable', 'integer', 'min:0'],
            'rentable_points_other' => ['nullable', 'integer', 'min:0'],
            'rentable_points_other2' => ['nullable', 'integer', 'min:0'],
            'rentable_points_other3' => ['nullable', 'integer', 'min:0'],
            'unit_price_ex_tax' => ['required', 'numeric', 'min:0'],
            'partner_unit_price_ex_tax' => ['nullable', 'numeric', 'min:0'],
            'job_type_label' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
            'uniform_product_ids' => ['nullable', 'array'],
            'uniform_product_ids.*' => ['integer', 'exists:uniform_products,id'],
        ]);
    }

    /**
     * Preserve Excel slot_category on re-save; default new links from product.category.
     *
     * @param  list<int|string>  $productIds
     * @return array<int, array{slot_category: string}>
     */
    private function productSyncPayload(array $productIds, ?ContractPlan $existing = null): array
    {
        $existingSlots = [];
        if ($existing) {
            $existing->loadMissing('uniformProducts');
            foreach ($existing->uniformProducts as $product) {
                $slot = $product->pivot?->slot_category;
                if ($slot) {
                    $existingSlots[(int) $product->id] = (string) $slot;
                }
            }
        }

        $products = UniformProduct::query()
            ->whereIn('id', $productIds)
            ->get(['id', 'category'])
            ->keyBy('id');

        $sync = [];
        foreach ($productIds as $id) {
            $id = (int) $id;
            $slot = $existingSlots[$id]
                ?? app(\App\Services\EmployeePlanEligibilityService::class)
                    ->normalizeLimitCategory($products->get($id)?->category);
            $sync[$id] = ['slot_category' => $slot];
        }

        return $sync;
    }
}
