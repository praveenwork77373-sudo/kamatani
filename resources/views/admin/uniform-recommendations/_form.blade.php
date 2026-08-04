@php
    $isEdit = isset($recommendation);
    $action = $isEdit
        ? route('admin.uniform-recommendations.update', $recommendation)
        : route('admin.uniform-recommendations.store');
@endphp

<form method="POST" action="{{ $action }}" class="space-y-4 max-w-2xl">
    @csrf
    @if($isEdit) @method('PUT') @endif

    <div>
        <label class="block text-sm font-medium text-slate-700 mb-1">医療機関 <span class="text-red-500">*</span></label>
        <select name="medical_institution_id" required class="w-full rounded-lg border-slate-300">
            <option value="">選択してください</option>
            @foreach($institutions as $inst)
                <option value="{{ $inst->id }}" @selected(old('medical_institution_id', $recommendation->medical_institution_id ?? '') == $inst->id)>{{ $inst->name }}</option>
            @endforeach
        </select>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">部署（任意）</label>
            <select name="department_id" class="w-full rounded-lg border-slate-300">
                <option value="">指定なし</option>
                @foreach($departments as $dept)
                    <option value="{{ $dept->id }}" @selected(old('department_id', $recommendation->department_id ?? '') == $dept->id)>{{ $dept->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">職種（任意）</label>
            <select name="job_type_id" class="w-full rounded-lg border-slate-300">
                <option value="">指定なし</option>
                @foreach($jobTypes as $job)
                    <option value="{{ $job->id }}" @selected(old('job_type_id', $recommendation->job_type_id ?? '') == $job->id)>{{ $job->name }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <div>
        <label class="block text-sm font-medium text-slate-700 mb-1">ユニフォーム商品 <span class="text-red-500">*</span></label>
        <select name="uniform_product_id" required class="w-full rounded-lg border-slate-300">
            <option value="">選択してください</option>
            @foreach($products as $product)
                <option value="{{ $product->id }}" @selected(old('uniform_product_id', $recommendation->uniform_product_id ?? '') == $product->id)>
                    {{ $product->product_code }} — {{ $product->name }} ({{ $product->size }})
                </option>
            @endforeach
        </select>
    </div>

    <div class="flex gap-6">
        <label class="inline-flex items-center gap-2 text-sm">
            <input type="checkbox" name="is_recommended" value="1" @checked(old('is_recommended', $recommendation->is_recommended ?? true))>
            推奨
        </label>
        <label class="inline-flex items-center gap-2 text-sm">
            <input type="checkbox" name="is_required" value="1" @checked(old('is_required', $recommendation->is_required ?? false))>
            指定（必須寄り）
        </label>
    </div>

    <div class="flex gap-3">
        <button class="ui-btn ui-btn-primary">保存</button>
        <a href="{{ route('admin.uniform-recommendations.index') }}" class="ui-btn ui-btn-secondary">戻る</a>
    </div>
</form>
