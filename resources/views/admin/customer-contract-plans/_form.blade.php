@php
    $isEdit = isset($plan);
    $action = $isEdit ? route('admin.customer-contract-plans.update', $plan) : route('admin.customer-contract-plans.store');
@endphp
<form method="POST" action="{{ $action }}" class="ui-card ui-form p-4 sm:p-6 space-y-4">
    @csrf
    @if($isEdit) @method('PUT') @endif

    <p class="text-[13px] text-slate-600 bg-slate-50 border border-slate-200 rounded-lg p-3">
        {{ __('pages.ccp_form_intro') }}
    </p>

    <div>
        <label class="ui-label">{{ __('pages.medical_institution') }} <span class="text-red-500">*</span></label>
        <select name="medical_institution_id" required class="ui-select">
            <option value="">{{ __('pages.select_please') }}</option>
            @forelse($institutions as $inst)
                <option value="{{ $inst->id }}" @selected(old('medical_institution_id', $plan->medical_institution_id ?? $selectedInstitutionId ?? '') == $inst->id)>{{ $inst->name }}（{{ $inst->code }}）</option>
            @empty
                <option value="" disabled>{{ __('pages.no_institutions_first') }}</option>
            @endforelse
        </select>
        @error('medical_institution_id')<p class="ui-error">{{ $message }}</p>@enderror
    </div>

    <div>
        <label class="ui-label">{{ __('pages.contract_plans_short') }} <span class="text-red-500">*</span></label>
        <select name="contract_plan_id" required class="ui-select" @disabled($contractPlans->isEmpty())>
            <option value="">{{ __('pages.select_please') }}</option>
            @forelse($contractPlans as $cp)
                <option value="{{ $cp->id }}" @selected(old('contract_plan_id', $plan->contract_plan_id ?? '') == $cp->id)>
                    {{ $cp->code }} — {{ $cp->name }}（¥{{ number_format($cp->unit_price_ex_tax) }} / {{ $cp->rentable_points }}枚）
                </option>
            @empty
                <option value="" disabled>{{ __('pages.no_contract_plans') }}</option>
            @endforelse
        </select>
        @if($contractPlans->isEmpty())
            <p class="ui-hint mt-2">
                {{ __('pages.ccp_link_uniforms_before') }}
                <a href="{{ route('admin.contract-plans.create') }}" class="text-primary-600 underline font-medium">{{ __('pages.register_contract_plan') }}</a>
                {{ __('pages.ccp_link_uniforms_after') }}
            </p>
        @endif
        @error('contract_plan_id')<p class="ui-error">{{ $message }}</p>@enderror
    </div>

    <div>
        <label class="ui-label">{{ __('pages.ui_00a446c519') }} <span class="text-red-500">*</span></label>
        <input type="number" name="contract_count" min="0" value="{{ old('contract_count', $plan->contract_count ?? 1) }}" required class="ui-input">
        <p class="ui-hint">{{ __('pages.contract_count_hint') }}</p>
        @error('contract_count')<p class="ui-error">{{ $message }}</p>@enderror
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
            <label class="ui-label">{{ __('pages.start_date_label') }} <span class="text-red-500">*</span></label>
            <input type="date" name="start_date" value="{{ old('start_date', optional($plan->start_date ?? null)->format('Y-m-d')) }}" required class="ui-input">
            @error('start_date')<p class="ui-error">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="ui-label">{{ __('pages.end_date') }} <span class="text-red-500">*</span></label>
            <input type="date" name="end_date" value="{{ old('end_date', optional($plan->end_date ?? null)->format('Y-m-d')) }}" required class="ui-input">
            @error('end_date')<p class="ui-error">{{ $message }}</p>@enderror
        </div>
    </div>
    <p class="ui-hint -mt-2">{{ __('pages.ccp_dates_always_required') }}</p>

    <div>
        <label class="ui-label">{{ __('pages.status') }} <span class="text-red-500">*</span></label>
        <select name="status" required class="ui-select">
            <option value="draft" @selected(old('status', $plan->status ?? 'draft') === 'draft')>{{ __('pages.status_draft') }}</option>
            <option value="confirmed" @selected(old('status', $plan->status ?? '') === 'confirmed')>{{ __('pages.status_confirmed') }}</option>
        </select>
        <p class="ui-hint">{{ __('pages.confirm_to_use_plan') }}</p>
        @error('status')<p class="ui-error">{{ $message }}</p>@enderror
    </div>

    <div>
        <label class="ui-label">{{ __('pages.supplier_notes') }}</label>
        <textarea name="notes" rows="2" class="ui-textarea">{{ old('notes', $plan->notes ?? '') }}</textarea>
        @error('notes')<p class="ui-error">{{ $message }}</p>@enderror
    </div>

    <div class="flex flex-col-reverse sm:flex-row gap-3">
        <a href="{{ route('admin.customer-contract-plans.index') }}" class="ui-btn ui-btn-secondary">{{ __('common.back') }}</a>
        <button type="submit" class="ui-btn ui-btn-primary" @disabled($contractPlans->isEmpty() || $institutions->isEmpty())>{{ __('common.save') }}</button>
    </div>
</form>
