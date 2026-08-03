@php
    $isEdit = isset($plan);
    $action = $isEdit ? route('admin.customer-contract-plans.update', $plan) : route('admin.customer-contract-plans.store');
@endphp
<form method="POST" action="{{ $action }}" class="ui-card ui-form p-4 sm:p-6 space-y-4">
    @csrf
    @if($isEdit) @method('PUT') @endif

    <p class="text-[13px] text-slate-600 bg-slate-50 border border-slate-200 rounded-lg p-3">
        顧客契約プラン = 医療機関に契約プランを紐付ける画面です。先に「契約プラン」でプランとユニフォームを登録してから、ここで病院へ割り当ててください。
    </p>

    <div>
        <label class="ui-label">医療機関 <span class="text-red-500">*</span></label>
        <select name="medical_institution_id" required class="ui-select">
            <option value="">選択してください</option>
            @forelse($institutions as $inst)
                <option value="{{ $inst->id }}" @selected(old('medical_institution_id', $plan->medical_institution_id ?? $selectedInstitutionId ?? '') == $inst->id)>{{ $inst->name }}（{{ $inst->code }}）</option>
            @empty
                <option value="" disabled>医療機関がありません。先に医療機関を登録してください。</option>
            @endforelse
        </select>
        @error('medical_institution_id')<p class="ui-error">{{ $message }}</p>@enderror
    </div>

    <div>
        <label class="ui-label">契約プラン <span class="text-red-500">*</span></label>
        <select name="contract_plan_id" required class="ui-select" @disabled($contractPlans->isEmpty())>
            <option value="">選択してください</option>
            @forelse($contractPlans as $cp)
                <option value="{{ $cp->id }}" @selected(old('contract_plan_id', $plan->contract_plan_id ?? '') == $cp->id)>
                    {{ $cp->code }} — {{ $cp->name }}（¥{{ number_format($cp->unit_price_ex_tax) }} / {{ $cp->rentable_points }}枚）
                </option>
            @empty
                <option value="" disabled>契約プランがありません</option>
            @endforelse
        </select>
        @if($contractPlans->isEmpty())
            <p class="ui-hint mt-2">
                先に
                <a href="{{ route('admin.contract-plans.create') }}" class="text-primary-600 underline font-medium">契約プランを登録</a>
                し、適用ユニフォームを紐付けてください。
            </p>
        @endif
        @error('contract_plan_id')<p class="ui-error">{{ $message }}</p>@enderror
    </div>

    <div>
        <label class="ui-label">契約数（口数） <span class="text-red-500">*</span></label>
        <input type="number" name="contract_count" min="0" value="{{ old('contract_count', $plan->contract_count ?? 1) }}" required class="ui-input">
        <p class="ui-hint">参考値（契約枠）。公式請求は割当職員数 × プラン単価です。</p>
        @error('contract_count')<p class="ui-error">{{ $message }}</p>@enderror
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
            <label class="ui-label">開始日</label>
            <input type="date" name="start_date" value="{{ old('start_date', optional($plan->start_date ?? null)->format('Y-m-d')) }}" class="ui-input">
            @error('start_date')<p class="ui-error">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="ui-label">終了日</label>
            <input type="date" name="end_date" value="{{ old('end_date', optional($plan->end_date ?? null)->format('Y-m-d')) }}" class="ui-input">
            @error('end_date')<p class="ui-error">{{ $message }}</p>@enderror
        </div>
    </div>

    <div>
        <label class="ui-label">状態 <span class="text-red-500">*</span></label>
        <select name="status" required class="ui-select">
            <option value="draft" @selected(old('status', $plan->status ?? 'draft') === 'draft')>仮登録</option>
            <option value="confirmed" @selected(old('status', $plan->status ?? '') === 'confirmed')>本登録</option>
        </select>
        <p class="ui-hint">職員への割当・申請に使うには「本登録」にしてください。</p>
        @error('status')<p class="ui-error">{{ $message }}</p>@enderror
    </div>

    <div>
        <label class="ui-label">備考</label>
        <textarea name="notes" rows="2" class="ui-textarea">{{ old('notes', $plan->notes ?? '') }}</textarea>
        @error('notes')<p class="ui-error">{{ $message }}</p>@enderror
    </div>

    <div class="flex flex-col-reverse sm:flex-row gap-3">
        <a href="{{ route('admin.customer-contract-plans.index') }}" class="ui-btn ui-btn-secondary">戻る</a>
        <button type="submit" class="ui-btn ui-btn-primary" @disabled($contractPlans->isEmpty() || $institutions->isEmpty())>保存</button>
    </div>
</form>
