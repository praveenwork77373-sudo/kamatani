@php
    $isEdit = isset($plan);
    $action = $isEdit ? route('admin.contract-plans.update', $plan) : route('admin.contract-plans.store');
    $selectedProducts = old('uniform_product_ids', $isEdit ? $plan->uniformProducts->pluck('id')->all() : []);
@endphp
<form method="POST" action="{{ $action }}" class="ui-card ui-form p-4 sm:p-6 space-y-4">
    @csrf
    @if($isEdit) @method('PUT') @endif
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <x-master-code-field
            type="contract_plan"
            name="code"
            :value="old('code', $plan->code ?? '')"
            :exclude-id="$isEdit ? $plan->id : null"
            :label="__('pages.plan_code')"
        />
        <div>
            <label class="ui-label">{{ __('pages.plan_name') }}</label>
            <input type="text" name="name" value="{{ old('name', $plan->name ?? '') }}" required class="ui-input">
        </div>
        <div>
            <label class="ui-label">{{ __('pages.ui_816d9257a0') }}</label>
            <input type="number" step="0.01" name="unit_price_ex_tax" min="0" value="{{ old('unit_price_ex_tax', $plan->unit_price_ex_tax ?? 0) }}" required class="ui-input">
            <p class="ui-hint mt-1">{{ __('pages.unit_price_customer_hint') }}</p>
        </div>
        <div>
            <label class="ui-label">{{ __('pages.partner_unit_price_ex_tax') }}</label>
            <input type="number" step="0.01" name="partner_unit_price_ex_tax" min="0" value="{{ old('partner_unit_price_ex_tax', $plan->partner_unit_price_ex_tax ?? '') }}" class="ui-input">
            <p class="ui-hint mt-1">{{ __('pages.partner_unit_price_hint') }}</p>
        </div>
        <div>
            <label class="ui-label">{{ __('pages.ui_10c9cfe887') }}</label>
            <input type="text" name="job_type_label" value="{{ old('job_type_label', $plan->job_type_label ?? '') }}" class="ui-input" placeholder="{{ __('pages.example_nurse_female') }}">
            <p class="ui-hint mt-1">{{ __('pages.position_display_hint') }}</p>
        </div>
        <div>
            <label class="ui-label">{{ __('pages.ui_180b08a5dd') }}</label>
            <input type="text" name="work_style" value="{{ old('work_style', $plan->work_style ?? '') }}" placeholder="{{ __('pages.example_employment_type') }}" class="ui-input">
        </div>
    </div>

    <div class="border border-slate-200 rounded-lg p-4 space-y-3 bg-slate-50/40">
        <div>
            <p class="text-sm font-semibold text-slate-800">{{ __('pages.category_point_caps') }}</p>
            <p class="ui-hint mt-1">{{ __('pages.category_point_caps_hint') }}</p>
        </div>
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3">
            <div>
                <label class="ui-label">{{ __('pages.cap_top') }}</label>
                <input type="number" name="rentable_points_top" min="0" value="{{ old('rentable_points_top', $plan->rentable_points_top ?? 0) }}" class="ui-input category-cap-input">
            </div>
            <div>
                <label class="ui-label">{{ __('pages.cap_bottom') }}</label>
                <input type="number" name="rentable_points_bottom" min="0" value="{{ old('rentable_points_bottom', $plan->rentable_points_bottom ?? 0) }}" class="ui-input category-cap-input">
            </div>
            <div>
                <label class="ui-label">{{ __('pages.cap_other') }}</label>
                <input type="number" name="rentable_points_other" min="0" value="{{ old('rentable_points_other', $plan->rentable_points_other ?? 0) }}" class="ui-input category-cap-input">
            </div>
            <div>
                <label class="ui-label">{{ __('pages.cap_other2') }}</label>
                <input type="number" name="rentable_points_other2" min="0" value="{{ old('rentable_points_other2', $plan->rentable_points_other2 ?? 0) }}" class="ui-input category-cap-input">
            </div>
            <div>
                <label class="ui-label">{{ __('pages.cap_other3') }}</label>
                <input type="number" name="rentable_points_other3" min="0" value="{{ old('rentable_points_other3', $plan->rentable_points_other3 ?? 0) }}" class="ui-input category-cap-input">
            </div>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="ui-label">{{ __('pages.rentable_points_total') }}</label>
                <input type="number" name="rentable_points" id="rentable-points-total" min="0" value="{{ old('rentable_points', $plan->rentable_points ?? 0) }}" class="ui-input" readonly>
                <p class="ui-hint mt-1">{{ __('pages.rentable_points_total_hint') }}</p>
            </div>
        </div>
    </div>

    <div>
        <label class="ui-label">{{ __('pages.supplier_notes') }}</label>
        <textarea name="notes" rows="2" class="ui-textarea">{{ old('notes', $plan->notes ?? '') }}</textarea>
    </div>
    <label class="inline-flex items-center gap-2 text-[14px] text-slate-700">
        <input type="checkbox" name="is_active" value="1" class="ui-check" {{ old('is_active', $plan->is_active ?? true) ? 'checked' : '' }}>
        {{ __('pages.active') }}
    </label>
    <div>
        <label class="ui-label">{{ __('pages.applicable_uniforms') }}</label>
        <p class="ui-hint mb-2">{{ __('pages.plan_products_hint') }}</p>
        <div class="max-h-64 overflow-y-auto border border-slate-200 rounded-lg p-3 space-y-1 bg-slate-50/50">
            @forelse($products as $product)
                <label class="flex items-start sm:items-center gap-2 text-[13px] sm:text-sm py-1.5">
                    <input type="checkbox" name="uniform_product_ids[]" value="{{ $product->id }}" class="ui-check mt-0.5"
                        {{ in_array($product->id, $selectedProducts) ? 'checked' : '' }}>
                    <span class="min-w-0">
                        <span class="font-mono text-xs text-slate-500">{{ $product->product_code }}</span>
                        <span class="block sm:inline sm:ml-1">{{ $product->name }} / {{ $product->size }} <span class="text-slate-400">({{ $product->categoryLabel() }})</span></span>
                    </span>
                </label>
            @empty
                <p class="text-slate-500 text-sm">
                    {{ __('pages.register_products_first') }}
                    <a href="{{ route('admin.uniform-products.create') }}" class="text-primary-600 underline">{{ __('menu.uniform_products') }}</a>
                    {{ __('pages.register_products_after') }}
                </p>
            @endforelse
        </div>
    </div>
    <div class="flex flex-col-reverse sm:flex-row gap-3 pt-2">
        <a href="{{ route('admin.contract-plans.index') }}" class="ui-btn ui-btn-secondary">{{ __('common.back') }}</a>
        <button type="submit" class="ui-btn ui-btn-primary">{{ __('common.save') }}</button>
    </div>
</form>
<script>
(() => {
    const inputs = Array.from(document.querySelectorAll('.category-cap-input'));
    const total = document.getElementById('rentable-points-total');
    if (!inputs.length || !total) return;
    const sync = () => {
        const sum = inputs.reduce((acc, el) => acc + (parseInt(el.value, 10) || 0), 0);
        if (sum > 0) total.value = String(sum);
    };
    inputs.forEach(el => el.addEventListener('input', sync));
    sync();
})();
</script>
