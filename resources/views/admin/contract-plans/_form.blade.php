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
            label="プランコード"
        />
        <div>
            <label class="ui-label">プラン名</label>
            <input type="text" name="name" value="{{ old('name', $plan->name ?? '') }}" required class="ui-input">
        </div>
        <div>
            <label class="ui-label">貸与枚数（ポイント）</label>
            <input type="number" name="rentable_points" min="0" value="{{ old('rentable_points', $plan->rentable_points ?? 0) }}" required class="ui-input">
        </div>
        <div>
            <label class="ui-label">月額単価（税抜）</label>
            <input type="number" step="0.01" name="unit_price_ex_tax" min="0" value="{{ old('unit_price_ex_tax', $plan->unit_price_ex_tax ?? 0) }}" required class="ui-input">
        </div>
        <div>
            <label class="ui-label">職種ラベル</label>
            <input type="text" name="job_type_label" value="{{ old('job_type_label', $plan->job_type_label ?? '') }}" class="ui-input">
        </div>
        <div>
            <label class="ui-label">勤務体制</label>
            <input type="text" name="work_style" value="{{ old('work_style', $plan->work_style ?? '') }}" placeholder="常勤 / 非常勤" class="ui-input">
        </div>
    </div>
    <div>
        <label class="ui-label">備考</label>
        <textarea name="notes" rows="2" class="ui-textarea">{{ old('notes', $plan->notes ?? '') }}</textarea>
    </div>
    <label class="inline-flex items-center gap-2 text-[14px] text-slate-700">
        <input type="checkbox" name="is_active" value="1" class="ui-check" {{ old('is_active', $plan->is_active ?? true) ? 'checked' : '' }}>
        有効
    </label>
    <div>
        <label class="ui-label">適用可能ユニフォーム</label>
        <p class="ui-hint mb-2">ここでチェックした商品が、このプランを割り当てられた職員の申請対象になります。（商品画面でプランは選びません）</p>
        <div class="max-h-64 overflow-y-auto border border-slate-200 rounded-lg p-3 space-y-1 bg-slate-50/50">
            @forelse($products as $product)
                <label class="flex items-start sm:items-center gap-2 text-[13px] sm:text-sm py-1.5">
                    <input type="checkbox" name="uniform_product_ids[]" value="{{ $product->id }}" class="ui-check mt-0.5"
                        {{ in_array($product->id, $selectedProducts) ? 'checked' : '' }}>
                    <span class="min-w-0">
                        <span class="font-mono text-xs text-slate-500">{{ $product->product_code }}</span>
                        <span class="block sm:inline sm:ml-1">{{ $product->name }} / {{ $product->size }}</span>
                    </span>
                </label>
            @empty
                <p class="text-slate-500 text-sm">
                    商品マスタがありません。先に
                    <a href="{{ route('admin.uniform-products.create') }}" class="text-primary-600 underline">ユニフォーム商品</a>
                    を登録してください。
                </p>
            @endforelse
        </div>
    </div>
    <div class="flex flex-col-reverse sm:flex-row gap-3 pt-2">
        <a href="{{ route('admin.contract-plans.index') }}" class="ui-btn ui-btn-secondary">戻る</a>
        <button type="submit" class="ui-btn ui-btn-primary">保存</button>
    </div>
</form>
