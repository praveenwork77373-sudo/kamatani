@php
    $isEdit = isset($plan);
    $action = $isEdit ? route('admin.customer-contract-plans.update', $plan) : route('admin.customer-contract-plans.store');
@endphp
<form method="POST" action="{{ $action }}" class="ui-card ui-form p-4 sm:p-6 space-y-4">
    @csrf
    @if($isEdit) @method('PUT') @endif
    <div>
        <label class="ui-label">医療機関</label>
        <select name="medical_institution_id" required class="ui-select">
            @foreach($institutions as $inst)
                <option value="{{ $inst->id }}" @selected(old('medical_institution_id', $plan->medical_institution_id ?? $selectedInstitutionId ?? '') == $inst->id)>{{ $inst->name }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="ui-label">契約プラン</label>
        <select name="contract_plan_id" required class="ui-select">
            @foreach($contractPlans as $cp)
                <option value="{{ $cp->id }}" @selected(old('contract_plan_id', $plan->contract_plan_id ?? '') == $cp->id)>
                    {{ $cp->code }} — {{ $cp->name }}（¥{{ number_format($cp->unit_price_ex_tax) }} / {{ $cp->rentable_points }}枚）
                </option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="ui-label">契約数</label>
        <input type="number" name="contract_count" min="0" value="{{ old('contract_count', $plan->contract_count ?? 0) }}" required class="ui-input">
    </div>
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
            <label class="ui-label">開始日</label>
            <input type="date" name="start_date" value="{{ old('start_date', optional($plan->start_date ?? null)->format('Y-m-d')) }}" class="ui-input">
        </div>
        <div>
            <label class="ui-label">終了日</label>
            <input type="date" name="end_date" value="{{ old('end_date', optional($plan->end_date ?? null)->format('Y-m-d')) }}" class="ui-input">
        </div>
    </div>
    <div>
        <label class="ui-label">状態</label>
        <select name="status" class="ui-select">
            <option value="draft" @selected(old('status', $plan->status ?? 'draft') === 'draft')>仮登録</option>
            <option value="confirmed" @selected(old('status', $plan->status ?? '') === 'confirmed')>本登録</option>
        </select>
    </div>
    <div>
        <label class="ui-label">備考</label>
        <textarea name="notes" rows="2" class="ui-textarea">{{ old('notes', $plan->notes ?? '') }}</textarea>
    </div>
    <div class="flex flex-col-reverse sm:flex-row gap-3">
        <a href="{{ route('admin.customer-contract-plans.index') }}" class="ui-btn ui-btn-secondary">戻る</a>
        <button type="submit" class="ui-btn ui-btn-primary">保存</button>
    </div>
</form>
