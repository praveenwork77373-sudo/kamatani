@extends('layouts.app')

@section('title', '職員追加 | ユニフォーム管理システム')
@section('page_title', '職員追加')

@section('content')
    <div class="mb-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-slate-900 tracking-tight">職員追加</h1>
                <p class="text-[14px] text-slate-600 mt-1.5">新しい職員を登録します。</p>
            </div>
            <a href="{{ route('employees.index') }}" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-lg border border-slate-300 text-slate-700 hover:bg-slate-50 text-[14px] font-medium transition-all">
                <i class="ph ph-arrow-left text-base"></i>
                一覧に戻る
            </a>
        </div>
    </div>

    <form method="POST" action="{{ route('employees.store') }}">
        @csrf

        <div class="bg-white border border-slate-200 rounded-xl shadow-sm overflow-hidden">
            {{-- 基本情報セクション --}}
            <div class="p-6 border-b border-slate-200">
                <h2 class="text-lg font-semibold text-slate-900 mb-4 flex items-center gap-2">
                    <i class="ph ph-info text-primary-600"></i>
                    基本情報
                </h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    {{-- Institution Selection (for admin users) --}}
                    @if(isset($adminUser) && $adminUser)
                        <div class="md:col-span-2">
                            <label class="block text-slate-700 text-[14px] font-semibold mb-2 flex items-center gap-2">
                                <i data-lucide="building-2" class="w-4 h-4 text-slate-500"></i>
                                {{ __('pages.medical_institution') }} <span class="text-red-500">*</span>
                            </label>
                            <select name="medical_institution_id" id="institution-select" required
                                    class="w-full px-4 py-2.5 rounded-lg border border-slate-300 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 text-[14px] bg-white shadow-sm transition-all">
                                <option value="">{{ __('pages.select_please') }}</option>
                                @foreach($institutions as $institution)
                                    <option value="{{ $institution->id }}" 
                                            @selected(old('medical_institution_id', $selectedInstitutionId) == $institution->id)>
                                        {{ $institution->name }} ({{ $institution->code }})
                                    </option>
                                @endforeach
                            </select>
                            @error('medical_institution_id')
                                <p class="text-[13px] text-red-600 mt-1.5 flex items-center gap-1">
                                    <i class="ph ph-warning text-xs"></i>
                                    {{ $message }}
                                </p>
                            @enderror
                        </div>
                    @elseif(isset($customer) && $customer)
                        {{-- Display institution for customer users (read-only) --}}
                        <div class="md:col-span-2">
                            <label class="block text-slate-700 text-[14px] font-semibold mb-2 flex items-center gap-2">
                                <i data-lucide="building-2" class="w-4 h-4 text-slate-500"></i>
                                {{ __('pages.medical_institution') }}
                            </label>
                            <div class="px-4 py-2.5 rounded-lg border border-slate-200 bg-slate-50 text-[14px] text-slate-700">
                                {{ $customer->medicalInstitution?->name ?? 'N/A' }}
                            </div>
                            <input type="hidden" name="medical_institution_id" value="{{ $customer->medical_institution_id }}">
                        </div>
                    @endif

                    <div>
                        <label class="block text-slate-700 text-[14px] font-semibold mb-2 flex items-center gap-2">
                            <i class="ph ph-identification-card text-slate-500"></i>
                            職員番号 <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="staff_no" value="{{ old('staff_no') }}" required
                               class="w-full px-4 py-2.5 rounded-lg border border-slate-300 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 text-[14px] bg-white shadow-sm transition-all"
                               placeholder="例: 10001">
                        @error('staff_no')
                            <p class="text-[13px] text-red-600 mt-1.5 flex items-center gap-1">
                                <i class="ph ph-warning text-xs"></i>
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-slate-700 text-[14px] font-semibold mb-2 flex items-center gap-2">
                            <i class="ph ph-user text-slate-500"></i>
                            氏名 <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="name" value="{{ old('name') }}" required
                               class="w-full px-4 py-2.5 rounded-lg border border-slate-300 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 text-[14px] bg-white shadow-sm transition-all"
                               placeholder="例: 山田 太郎">
                        @error('name')
                            <p class="text-[13px] text-red-600 mt-1.5 flex items-center gap-1">
                                <i class="ph ph-warning text-xs"></i>
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-slate-700 text-[14px] font-semibold mb-2 flex items-center gap-2">
                            <i class="ph ph-gender-intersex text-slate-500"></i>
                            {{ __('pages.gender') }}
                        </label>
                        <select name="gender"
                                class="w-full px-4 py-2.5 rounded-lg border border-slate-300 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 text-[14px] bg-white shadow-sm transition-all">
                            <option value="">{{ __('pages.select_please') }}</option>
                            <option value="male" @selected(old('gender')==='male')>{{ __('pages.male') }}</option>
                            <option value="female" @selected(old('gender')==='female')>{{ __('pages.female') }}</option>
                            <option value="other" @selected(old('gender')==='other')>{{ __('pages.other') }}</option>
                        </select>
                        @error('gender')
                            <p class="text-[13px] text-red-600 mt-1.5 flex items-center gap-1">
                                <i class="ph ph-warning text-xs"></i>
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-slate-700 text-[14px] font-semibold mb-2 flex items-center gap-2">
                            <i class="ph ph-buildings text-slate-500"></i>
                            部署 <span class="text-red-500">*</span>
                        </label>
                        @if(isset($customer) && $customer->role === 'department_admin')
                            {{-- 部署管理者は自部署に固定 --}}
                            @php
                                $dept = $departments->first();
                            @endphp
                            <div class="px-4 py-2.5 rounded-lg border border-slate-200 bg-slate-50 text-[14px] text-slate-700">
                                {{ $dept?->name ?? '部署未設定' }}
                            </div>
                            <input type="hidden" name="primary_department_id" value="{{ $dept?->id }}">
                        @else
                            <select name="primary_department_id" id="primary-department-select" required
                                    class="w-full px-4 py-2.5 rounded-lg border border-slate-300 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 text-[14px] bg-white shadow-sm transition-all">
                                <option value="">{{ __('pages.select_please') }}</option>
                                @foreach($departments as $dept)
                                    <option value="{{ $dept->id }}" @selected(old('primary_department_id')==$dept->id)>
                                        {{ $dept->name }}
                                    </option>
                                @endforeach
                            </select>
                        @endif
                        @error('primary_department_id')
                            <p class="text-[13px] text-red-600 mt-1.5 flex items-center gap-1">
                                <i class="ph ph-warning text-xs"></i>
                                {{ $message }}
                            </p>
                        @enderror
                    </div>
                </div>
            </div>

            {{-- 職種・兼務セクション --}}
            <div class="p-6 border-b border-slate-200">
                <h2 class="text-lg font-semibold text-slate-900 mb-4">職種・兼務設定</h2>
                <p class="text-[13px] text-slate-600 mb-3">
                    職員が所属する部署と職種を設定します。複数の部署・職種を追加することで、兼務（複数ポジション）に対応できます。
                </p>
                <div id="positions-area" class="space-y-3">
                    <div class="position-row bg-slate-50 border border-slate-200 rounded-lg p-3 space-y-3">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3 items-end">
                            <div>
                                <label class="block text-slate-700 text-[13px] font-semibold mb-1">
                                    <span class="inline-flex items-center gap-1.5">
                                        <i class="ph ph-buildings text-sm text-slate-500"></i>
                                        <span>部署</span>
                                    </span>
                                </label>
                                <select name="positions[0][department_id]" class="position-department-select w-full px-3 py-2.5 rounded-lg border border-slate-300 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 text-[14px] bg-white shadow-sm transition-all">
                                    <option value="">{{ __('pages.select_please') }}</option>
                                    @foreach($departments as $dept)
                                        <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-slate-700 text-[13px] font-semibold mb-1">
                                    <span class="inline-flex items-center gap-1.5">
                                        <i class="ph ph-briefcase text-sm text-slate-500"></i>
                                        <span>職種</span>
                                    </span>
                                </label>
                                <div class="flex items-center gap-2">
                                    <select name="positions[0][job_type_id]" class="position-job-type-select flex-1 w-full px-3 py-2.5 rounded-lg border border-slate-300 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 text-[14px] bg-white shadow-sm transition-all">
                                        <option value="">{{ __('pages.select_please') }}</option>
                                        @foreach($jobTypes as $jobType)
                                            <option value="{{ $jobType->id }}">{{ $jobType->name }}</option>
                                        @endforeach
                                    </select>
                                    <button type="button" class="remove-position-btn hidden inline-flex items-center justify-center h-10 w-10 rounded-lg border border-red-300 text-red-600 hover:bg-red-50">
                                        <i class="ph ph-trash text-sm"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="position-plans border-t border-slate-200 pt-3">
                            <p class="text-[12px] font-semibold text-slate-600 mb-2">この所属の契約プラン</p>
                            <div class="space-y-1.5 max-h-36 overflow-y-auto">
                                @forelse(($availablePlans ?? []) as $ccp)
                                    <label class="flex items-center gap-2 text-sm">
                                        <input type="checkbox" name="positions[0][customer_contract_plan_ids][]" value="{{ $ccp->id }}" class="position-plan-checkbox">
                                        <span class="font-mono text-xs text-slate-500">{{ $ccp->contractPlan->code ?? '' }}</span>
                                        <span>{{ $ccp->contractPlan->name ?? '' }}</span>
                                    </label>
                                @empty
                                    <p class="text-slate-500 text-xs">本登録の契約プランがありません</p>
                                @endforelse
                            </div>
                        </div>
                    </div>
                </div>
                <button type="button" id="add-position-btn"
                        class="mt-3 inline-flex items-center gap-2 px-4 py-2.5 rounded-lg border border-dashed border-slate-300 text-slate-700 hover:bg-slate-50 text-[13px] font-medium transition-all">
                    <i class="ph ph-plus text-sm"></i>
                    兼務ポジションを追加
                </button>
                <p class="mt-2 text-[11px] text-slate-500">
                    ※ 最初の行が主たる所属として扱われます。兼務ごとに異なる契約プランを割当できます。
                </p>
            </div>

            {{-- サイズ情報セクション --}}
            <div class="p-6 border-b border-slate-200">
                <h2 class="text-lg font-semibold text-slate-900 mb-4">サイズ情報</h2>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div>
                        <label class="block text-slate-700 text-[14px] font-semibold mb-2">
                            <span class="inline-flex items-center gap-2">
                                <i class="ph ph-t-shirt text-base text-slate-500"></i>
                                <span>上衣サイズ</span>
                            </span>
                        </label>
                        <select name="top_size"
                                class="w-full px-3 py-2.5 rounded-lg border border-slate-300 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 text-[14px] bg-white shadow-sm transition-all">
                            <option value="">選択してください</option>
                            @foreach(['XS','S','M','L','XL','XXL'] as $size)
                                <option value="{{ $size }}" @selected(old('top_size')===$size)>{{ $size }}</option>
                            @endforeach
                        </select>
                        @error('top_size')
                            <p class="text-[13px] text-red-600 mt-1.5 flex items-center gap-1">
                                <i class="ph ph-warning text-xs"></i>
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-slate-700 text-[14px] font-semibold mb-2">
                            <span class="inline-flex items-center gap-2">
                                <i class="ph ph-trousers text-base text-slate-500"></i>
                                <span>ズボンサイズ</span>
                            </span>
                        </label>
                        <select name="bottom_size"
                                class="w-full px-3 py-2.5 rounded-lg border border-slate-300 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 text-[14px] bg-white shadow-sm transition-all">
                            <option value="">選択してください</option>
                            @foreach(['XS','S','M','L','XL','XXL'] as $size)
                                <option value="{{ $size }}" @selected(old('bottom_size')===$size)>{{ $size }}</option>
                            @endforeach
                        </select>
                        @error('bottom_size')
                            <p class="text-[13px] text-red-600 mt-1.5 flex items-center gap-1">
                                <i class="ph ph-warning text-xs"></i>
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-slate-700 text-[14px] font-semibold mb-2">
                            <span class="inline-flex items-center gap-2">
                                <i class="ph ph-sneaker text-base text-slate-500"></i>
                                <span>靴サイズ</span>
                            </span>
                        </label>
                        <select name="shoe_size"
                                class="w-full px-3 py-2.5 rounded-lg border border-slate-300 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 text-[14px] bg-white shadow-sm transition-all">
                            <option value="">選択してください</option>
                            @foreach(['22.0','22.5','23.0','23.5','24.0','24.5','25.0','25.5','26.0','26.5','27.0','27.5','28.0'] as $size)
                                <option value="{{ $size }}" @selected(old('shoe_size')===$size)>{{ $size }}</option>
                            @endforeach
                        </select>
                        @error('shoe_size')
                            <p class="text-[13px] text-red-600 mt-1.5 flex items-center gap-1">
                                <i class="ph ph-warning text-xs"></i>
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-slate-700 text-[14px] font-semibold mb-2">股下 (cm)</label>
                        <select name="inseam_cm"
                                class="w-full px-3 py-2.5 rounded-lg border border-slate-300 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 text-[14px] bg-white shadow-sm transition-all">
                            <option value="">未設定</option>
                            @for($cm = 60; $cm <= 90; $cm++)
                                <option value="{{ $cm }}" @selected(old('inseam_cm')==$cm)>{{ $cm }}</option>
                            @endfor
                        </select>
                        @error('inseam_cm')
                            <p class="text-[13px] text-red-600 mt-1.5">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-slate-700 text-[14px] font-semibold mb-2">体型</label>
                        <input type="text" name="body_type" value="{{ old('body_type') }}"
                               class="w-full px-3 py-2.5 rounded-lg border border-slate-300 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 text-[14px] bg-white shadow-sm transition-all"
                               placeholder="例: 7号, 11号">
                        @error('body_type')
                            <p class="text-[13px] text-red-600 mt-1.5">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-slate-700 text-[14px] font-semibold mb-2">入職日</label>
                        <input type="date" name="join_date" value="{{ old('join_date') }}"
                               class="w-full px-3 py-2.5 rounded-lg border border-slate-300 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 text-[14px] bg-white shadow-sm transition-all">
                        @error('join_date')
                            <p class="text-[13px] text-red-600 mt-1.5">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-slate-700 text-[14px] font-semibold mb-2">退職日</label>
                        <input type="date" name="retire_date" value="{{ old('retire_date') }}"
                               class="w-full px-3 py-2.5 rounded-lg border border-slate-300 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 text-[14px] bg-white shadow-sm transition-all">
                        @error('retire_date')
                            <p class="text-[13px] text-red-600 mt-1.5">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            {{-- 契約プラン割当（所属指定なし・任意） --}}
            <div class="p-6 border-b border-slate-200">
                <h2 class="text-lg font-semibold text-slate-900 mb-2">共通契約プラン（所属指定なし）</h2>
                <p class="text-[13px] text-slate-500 mb-3">兼務に紐づけないプランはこちら。所属ごとのプランは上の兼務行で設定できます。登録時は任意です。</p>
                <div class="space-y-2 border border-slate-200 rounded-lg p-3 max-h-48 overflow-y-auto bg-slate-50">
                    @php
                        $selectedPlanIds = old('customer_contract_plan_ids', []);
                    @endphp
                    @forelse(($availablePlans ?? []) as $ccp)
                        <label class="flex items-center gap-2 text-sm">
                            <input type="checkbox" name="customer_contract_plan_ids[]" value="{{ $ccp->id }}"
                                {{ in_array($ccp->id, (array) $selectedPlanIds) ? 'checked' : '' }}>
                            <span class="font-mono text-xs text-slate-500">{{ $ccp->contractPlan->code ?? '' }}</span>
                            <span>{{ $ccp->contractPlan->name ?? '' }}</span>
                            <span class="text-slate-400 text-xs">（口数 {{ $ccp->contract_count }}）</span>
                        </label>
                    @empty
                        <p class="text-slate-500 text-sm">本登録の契約プランがありません。カマタニ側で顧客契約プランを本登録してください。</p>
                    @endforelse
                </div>
                @error('customer_contract_plan_ids')
                    <p class="text-[13px] text-red-600 mt-1.5">{{ $message }}</p>
                @enderror
            </div>

            {{-- 状態セクション --}}
            <div class="p-6 border-b border-slate-200">
                <h2 class="text-lg font-semibold text-slate-900 mb-4">{{ __('pages.status') }}</h2>
                <div class="max-w-md">
                    <label class="block text-slate-700 text-[14px] font-semibold mb-2">
                        状態 <span class="text-red-500">*</span>
                    </label>
                    <select name="status" required
                            class="w-full px-4 py-2.5 rounded-lg border border-slate-300 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 text-[14px] bg-white shadow-sm transition-all">
                        <option value="employed" @selected(old('status')==='employed')>{{ __('pages.employed') }}</option>
                        <option value="on_leave" @selected(old('status')==='on_leave')>{{ __('pages.on_leave') }}</option>
                        <option value="retired" @selected(old('status')==='retired')>{{ __('pages.retired') }}</option>
                    </select>
                    @error('status')
                        <p class="text-[13px] text-red-600 mt-1.5 flex items-center gap-1">
                            <i class="ph ph-warning text-xs"></i>
                            {{ $message }}
                        </p>
                    @enderror
                </div>
            </div>

            {{-- アクションボタン --}}
            <div class="p-6 bg-slate-50 flex items-center justify-end gap-3">
                <a href="{{ route('employees.index') }}" class="px-5 py-2.5 rounded-lg border border-slate-300 text-slate-700 hover:bg-white text-[14px] font-medium transition-all">
                    キャンセル
                </a>
                <button type="submit" class="px-6 py-2.5 rounded-lg bg-primary-600 hover:bg-primary-700 text-white text-[14px] font-semibold shadow-md transition-all flex items-center gap-2">
                    <i class="ph ph-check text-base"></i>
                    登録する
                </button>
            </div>
        </div>
    </form>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const addBtn = document.getElementById('add-position-btn');
            const area = document.getElementById('positions-area');
            const institutionSelect = document.getElementById('institution-select');
            
            if (!addBtn || !area) return;

            let index = area.querySelectorAll('.position-row').length;

            // Function to load departments and job types by institution
            function loadInstitutionData(institutionId) {
                if (!institutionId) {
                    // Clear all selects
                    document.querySelectorAll('.position-department-select, .position-job-type-select, #primary-department-select').forEach(sel => {
                        sel.innerHTML = '<option value="">{{ __('pages.select_please') }}</option>';
                    });
                    return;
                }

                fetch(`{{ route('api.departments') }}?medical_institution_id=${institutionId}`)
                    .then(response => response.json())
                    .then(data => {
                        // Update primary department select
                        const primaryDeptSelect = document.getElementById('primary-department-select');
                        if (primaryDeptSelect) {
                            primaryDeptSelect.innerHTML = '<option value="">{{ __('pages.select_please') }}</option>';
                            data.forEach(dept => {
                                const option = document.createElement('option');
                                option.value = dept.id;
                                option.textContent = dept.name;
                                primaryDeptSelect.appendChild(option);
                            });
                        }

                        // Update all position department selects
                        document.querySelectorAll('.position-department-select').forEach(sel => {
                            const currentValue = sel.value;
                            sel.innerHTML = '<option value="">{{ __('pages.select_please') }}</option>';
                            data.forEach(dept => {
                                const option = document.createElement('option');
                                option.value = dept.id;
                                option.textContent = dept.name;
                                if (dept.id == currentValue) option.selected = true;
                                sel.appendChild(option);
                            });
                        });

                        // Update all position job type selects
                        document.querySelectorAll('.position-job-type-select').forEach(sel => {
                            const currentValue = sel.value;
                            sel.innerHTML = '<option value="">{{ __('pages.select_please') }}</option>';
                            data.job_types.forEach(jobType => {
                                const option = document.createElement('option');
                                option.value = jobType.id;
                                option.textContent = jobType.name;
                                if (jobType.id == currentValue) option.selected = true;
                                sel.appendChild(option);
                            });
                        });
                    })
                    .catch(error => {
                        console.error('Error loading institution data:', error);
                    });
            }

            // Listen to institution change (for admin users) — reload so plans load
            if (institutionSelect) {
                institutionSelect.addEventListener('change', function() {
                    const id = this.value;
                    if (id) {
                        window.location.href = '{{ route('employees.create') }}?medical_institution_id=' + id;
                        return;
                    }
                    loadInstitutionData(this.value);
                });

                if (institutionSelect.value) {
                    loadInstitutionData(institutionSelect.value);
                }
            }

            // Add position row
            addBtn.addEventListener('click', function () {
                const firstRow = area.querySelector('.position-row');
                if (!firstRow) return;

                const clone = firstRow.cloneNode(true);
                clone.querySelectorAll('select').forEach(function (sel) {
                    sel.value = '';
                    sel.name = sel.name.replace(/positions\[\d+]/, 'positions[' + index + ']');
                });
                clone.querySelectorAll('input[type="checkbox"]').forEach(function (cb) {
                    cb.checked = false;
                    cb.name = cb.name.replace(/positions\[\d+]/, 'positions[' + index + ']');
                });
                clone.querySelectorAll('input[type="hidden"]').forEach(function (inp) {
                    inp.remove();
                });
                const removeBtn = clone.querySelector('.remove-position-btn');
                if (removeBtn) {
                    removeBtn.classList.remove('hidden');
                }

                area.appendChild(clone);
                index++;
            });

            area.addEventListener('click', function (e) {
                const btn = e.target.closest('.remove-position-btn');
                if (!btn) return;
                const row = btn.closest('.position-row');
                if (row && area.querySelectorAll('.position-row').length > 1) {
                    row.remove();
                }
            });
        });
    </script>
@endsection
