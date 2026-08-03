@extends('layouts.app')

@section('title', __('pages.edit_employee') . ' | ' . config('app.name'))
@section('header_category', __('menu.employees'))
@section('page_title', __('pages.edit_employee'))

@section('content')
    <div class="mb-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-slate-900 tracking-tight">{{ __('pages.edit_employee') }}</h1>
                <p class="text-[14px] text-slate-600 mt-1.5">{{ __('pages.edit_employee_description') }}</p>
            </div>
            <a href="{{ route('employees.show', $employee) }}" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-lg border border-slate-300 text-slate-700 hover:bg-slate-50 text-[14px] font-medium transition-all">
                <i data-lucide="arrow-left" class="w-4 h-4"></i>
                {{ __('common.back') }}
            </a>
        </div>
    </div>

    <form method="POST" action="{{ route('employees.update', $employee) }}" class="max-w-4xl mx-auto">
        @csrf
        @method('PUT')

        <div class="bg-white border border-slate-200 rounded-xl shadow-sm overflow-hidden">
            {{-- Form Header --}}
            <div class="px-6 py-4 border-b border-slate-200 bg-gradient-to-r from-primary-50 to-blue-50">
                <div class="flex items-center gap-3">
                    <div class="h-10 w-10 rounded-lg bg-primary-100 flex items-center justify-center">
                        <i data-lucide="user" class="w-5 h-5 text-primary-600"></i>
                    </div>
                    <div>
                        <h2 class="text-lg font-semibold text-slate-900">{{ __('pages.basic_information') }}</h2>
                        <p class="text-[13px] text-slate-500">{{ __('pages.edit_employee_information') }}</p>
                    </div>
                </div>
            </div>

            {{-- Form Body --}}
            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <x-master-code-field
                        type="staff_no"
                        name="staff_no"
                        :value="old('staff_no', $employee->staff_no)"
                        :exclude-id="$employee->id"
                        :institution-id="$employee->medical_institution_id"
                        :label="__('pages.staff_no')"
                    />

                    {{-- Employee Name --}}
                    <div>
                        <label class="block text-slate-700 text-[14px] font-semibold mb-2">
                            <span class="flex items-center gap-2">
                                <i data-lucide="user" class="w-4 h-4 text-slate-500"></i>
                                {{ __('pages.employee_name') }} <span class="text-red-500">*</span>
                            </span>
                        </label>
                        <div class="ui-field-icon">
                            <i data-lucide="user"></i>
                            <input type="text" name="name" value="{{ old('name', $employee->name) }}" required
                                   class="ui-input"
                                   placeholder="{{ __('pages.employee_name_placeholder') }}">
                        </div>
                        @error('name')
                            <p class="text-[13px] text-red-600 mt-2 flex items-center gap-1.5">
                                <i data-lucide="alert-circle" class="w-3.5 h-3.5"></i>
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                    {{-- Status --}}
                    <div>
                        <label class="block text-slate-700 text-[14px] font-semibold mb-2">
                            <span class="flex items-center gap-2">
                                <i data-lucide="check-circle" class="w-4 h-4 text-slate-500"></i>
                                {{ __('pages.status') }} <span class="text-red-500">*</span>
                            </span>
                        </label>
                        <div class="ui-field-icon">
                            <i data-lucide="check-circle"></i>
                            <select name="status" required class="ui-select">
                                <option value="employed" @selected(old('status', $employee->status)==='employed')>{{ __('pages.employed') }}</option>
                                <option value="on_leave" @selected(old('status', $employee->status)==='on_leave')>{{ __('pages.on_leave') }}</option>
                                <option value="retired" @selected(old('status', $employee->status)==='retired')>{{ __('pages.retired') }}</option>
                            </select>
                        </div>
                        @error('status')
                            <p class="text-[13px] text-red-600 mt-2 flex items-center gap-1.5">
                                <i data-lucide="alert-circle" class="w-3.5 h-3.5"></i>
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                    {{-- Gender --}}
                    <div>
                        <label class="block text-slate-700 text-[14px] font-semibold mb-2">
                            <span class="flex items-center gap-2">
                                <i data-lucide="users" class="w-4 h-4 text-slate-500"></i>
                                {{ __('pages.gender') }}
                            </span>
                        </label>
                        <div class="ui-field-icon">
                            <i data-lucide="users"></i>
                            <select name="gender" class="ui-select">
                                <option value="">{{ __('pages.not_set') }}</option>
                                <option value="male" @selected(old('gender', $employee->gender)==='male')>{{ __('pages.male') }}</option>
                                <option value="female" @selected(old('gender', $employee->gender)==='female')>{{ __('pages.female') }}</option>
                                <option value="other" @selected(old('gender', $employee->gender)==='other')>{{ __('pages.other') }}</option>
                            </select>
                        </div>
                        @error('gender')
                            <p class="text-[13px] text-red-600 mt-2 flex items-center gap-1.5">
                                <i data-lucide="alert-circle" class="w-3.5 h-3.5"></i>
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                    {{-- Primary Department (for institution_admin only) --}}
                    @if($customer && $customer->role === 'institution_admin')
                        <div>
                            <label class="block text-slate-700 text-[14px] font-semibold mb-2">
                                <span class="flex items-center gap-2">
                                    <i data-lucide="briefcase" class="w-4 h-4 text-slate-500"></i>
                                    {{ __('pages.department') }} <span class="text-red-500">*</span>
                                </span>
                            </label>
                            <div class="ui-field-icon">
                                <i data-lucide="briefcase"></i>
                                <select name="primary_department_id" required class="ui-select">
                                    <option value="">{{ __('pages.select_please') }}</option>
                                    @foreach($departments as $dept)
                                        <option value="{{ $dept->id }}" @selected(old('primary_department_id', $employee->primary_department_id)===$dept->id)>{{ $dept->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            @error('primary_department_id')
                                <p class="text-[13px] text-red-600 mt-2 flex items-center gap-1.5">
                                    <i data-lucide="alert-circle" class="w-3.5 h-3.5"></i>
                                    {{ $message }}
                                </p>
                            @enderror
                        </div>
                    @endif

                    {{-- Top Size --}}
                    <div>
                        <label class="block text-slate-700 text-[14px] font-semibold mb-2">
                            <span class="flex items-center gap-2">
                                <i data-lucide="shirt" class="w-4 h-4 text-slate-500"></i>
                                {{ __('pages.top_size') }}
                            </span>
                        </label>
                        <div class="ui-field-icon">
                            <i data-lucide="shirt"></i>
                            <select name="top_size" class="ui-select">
                                <option value="">{{ __('pages.not_set') }}</option>
                                @foreach(['XS','S','M','L','XL','XXL'] as $size)
                                    <option value="{{ $size }}" @selected(old('top_size', $employee->top_size)===$size)>{{ $size }}</option>
                                @endforeach
                            </select>
                        </div>
                        @error('top_size')
                            <p class="text-[13px] text-red-600 mt-2 flex items-center gap-1.5">
                                <i data-lucide="alert-circle" class="w-3.5 h-3.5"></i>
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                    {{-- Bottom Size --}}
                    <div>
                        <label class="block text-slate-700 text-[14px] font-semibold mb-2">
                            <span class="flex items-center gap-2">
                                <i data-lucide="package" class="w-4 h-4 text-slate-500"></i>
                                {{ __('pages.bottom_size') }}
                            </span>
                        </label>
                        <div class="ui-field-icon">
                            <i data-lucide="package"></i>
                            <select name="bottom_size" class="ui-select">
                                <option value="">{{ __('pages.not_set') }}</option>
                                @foreach(['XS','S','M','L','XL','XXL'] as $size)
                                    <option value="{{ $size }}" @selected(old('bottom_size', $employee->bottom_size)===$size)>{{ $size }}</option>
                                @endforeach
                            </select>
                        </div>
                        @error('bottom_size')
                            <p class="text-[13px] text-red-600 mt-2 flex items-center gap-1.5">
                                <i data-lucide="alert-circle" class="w-3.5 h-3.5"></i>
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                    {{-- Shoe Size --}}
                    <div>
                        <label class="block text-slate-700 text-[14px] font-semibold mb-2">
                            <span class="flex items-center gap-2">
                                <i data-lucide="footprints" class="w-4 h-4 text-slate-500"></i>
                                {{ __('pages.shoe_size') }}
                            </span>
                        </label>
                        <div class="ui-field-icon">
                            <i data-lucide="footprints"></i>
                            <select name="shoe_size" class="ui-select">
                                <option value="">{{ __('pages.not_set') }}</option>
                                @foreach(['22.0','22.5','23.0','23.5','24.0','24.5','25.0','25.5','26.0','26.5','27.0','27.5','28.0'] as $size)
                                    <option value="{{ $size }}" @selected(old('shoe_size', $employee->shoe_size)===$size)>{{ $size }}</option>
                                @endforeach
                            </select>
                        </div>
                        @error('shoe_size')
                            <p class="text-[13px] text-red-600 mt-2 flex items-center gap-1.5">
                                <i data-lucide="alert-circle" class="w-3.5 h-3.5"></i>
                                {{ $message }}
                            </p>
                        @enderror
                    </div>
                    <div>
                        <label class="block text-slate-700 text-[14px] font-semibold mb-2">股下 (cm)</label>
                        <div class="ui-field-icon">
                            <i data-lucide="ruler"></i>
                            <select name="inseam_cm" class="ui-select">
                                <option value="">未設定</option>
                                @for($cm = 60; $cm <= 90; $cm++)
                                    <option value="{{ $cm }}" @selected(old('inseam_cm', $employee->inseam_cm)==$cm)>{{ $cm }}</option>
                                @endfor
                            </select>
                        </div>
                    </div>
                    <div>
                        <label class="block text-slate-700 text-[14px] font-semibold mb-2">体型</label>
                        <div class="ui-field-icon">
                            <i data-lucide="user"></i>
                            <input type="text" name="body_type" value="{{ old('body_type', $employee->body_type) }}" class="ui-input" placeholder="例: 7号, 11号">
                        </div>
                    </div>
                    <div>
                        <label class="block text-slate-700 text-[14px] font-semibold mb-2">入職日</label>
                        <div class="ui-field-icon">
                            <i data-lucide="calendar"></i>
                            <input type="date" name="join_date" value="{{ old('join_date', optional($employee->join_date)->format('Y-m-d')) }}" class="ui-input">
                        </div>
                    </div>
                    <div>
                        <label class="block text-slate-700 text-[14px] font-semibold mb-2">退職日</label>
                        <div class="ui-field-icon">
                            <i data-lucide="calendar"></i>
                            <input type="date" name="retire_date" value="{{ old('retire_date', optional($employee->retire_date)->format('Y-m-d')) }}" class="ui-input">
                        </div>
                    </div>
                </div>

                <div class="mt-6 mb-4">
                    <h3 class="text-[15px] font-semibold text-slate-700 mb-2">共通契約プラン（所属指定なし）</h3>
                    <p class="text-[13px] text-slate-500 mb-3">兼務に紐づけないプラン。所属ごとのプランは下の兼務行で設定します。</p>
                    <div class="space-y-2 border border-slate-200 rounded-lg p-3 max-h-48 overflow-y-auto">
                        @php
                            $today = now()->toDateString();
                            $selectedPlanIds = old(
                                'customer_contract_plan_ids',
                                $employee->contractPlanAssignments
                                    ->filter(fn ($a) => $a->employee_position_id === null && $a->isActiveOn($today))
                                    ->pluck('customer_contract_plan_id')
                                    ->all()
                            );
                        @endphp
                        @forelse(($availablePlans ?? []) as $ccp)
                            <label class="flex items-center gap-2 text-sm">
                                <input type="checkbox" name="customer_contract_plan_ids[]" value="{{ $ccp->id }}"
                                    {{ in_array($ccp->id, $selectedPlanIds) ? 'checked' : '' }}>
                                <span class="font-mono text-xs">{{ $ccp->contractPlan->code ?? '' }}</span>
                                <span>{{ $ccp->contractPlan->name ?? '' }}</span>
                            </label>
                        @empty
                            <p class="text-slate-500 text-sm">本登録の契約プランがありません</p>
                        @endforelse
                    </div>
                </div>

                {{-- Divider --}}
                <div class="my-6 border-t border-slate-200"></div>

                {{-- Multiple Positions Section --}}
                <div class="mb-4">
                    <div class="flex items-center gap-2 mb-4">
                        <i data-lucide="briefcase" class="w-4 h-4 text-slate-500"></i>
                        <h3 class="text-[15px] font-semibold text-slate-700">{{ __('pages.multiple_positions') }}</h3>
                    </div>
                    <p class="text-[13px] text-slate-500 mb-4">兼務ごとに異なる契約プランを割当できます。</p>
                </div>

                <div id="positions-area" class="space-y-4">
                    @php
                        $positions = $employee->positions->count() > 0 ? $employee->positions : collect([new \App\Models\EmployeePosition()]);
                        $oldPositions = old('positions');
                    @endphp
                    @foreach($positions as $idx => $pos)
                        @php
                            $posPlanIds = old(
                                "positions.$idx.customer_contract_plan_ids",
                                $pos->id
                                    ? $employee->contractPlanAssignments
                                        ->filter(fn ($a) => (int) $a->employee_position_id === (int) $pos->id && $a->isActiveOn($today))
                                        ->pluck('customer_contract_plan_id')
                                        ->all()
                                    : []
                            );
                        @endphp
                        <div class="position-row bg-slate-50 border border-slate-200 rounded-lg p-4 space-y-3">
                            @if($pos->id)
                                <input type="hidden" name="positions[{{ $idx }}][id]" value="{{ $pos->id }}">
                            @endif
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 items-end">
                                <div>
                                    <label class="block text-slate-700 text-[13px] font-semibold mb-2">
                                        <span class="flex items-center gap-2">
                                            <i data-lucide="building" class="w-4 h-4 text-slate-500"></i>
                                            {{ __('pages.department') }}
                                        </span>
                                    </label>
                                    <div class="ui-field-icon">
                                        <i data-lucide="building"></i>
                                        <select name="positions[{{ $idx }}][department_id]" class="ui-select">
                                            <option value="">{{ __('pages.select_please') }}</option>
                                            @foreach($departments as $dept)
                                                <option value="{{ $dept->id }}" @selected(old("positions.$idx.department_id", $pos->department_id)===$dept->id)>{{ $dept->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-slate-700 text-[13px] font-semibold mb-2">
                                        <span class="flex items-center gap-2">
                                            <i data-lucide="user-check" class="w-4 h-4 text-slate-500"></i>
                                            {{ __('pages.job_type') }}
                                        </span>
                                    </label>
                                    <div class="flex items-center gap-2">
                                        <div class="ui-field-icon flex-1">
                                            <i data-lucide="user-check"></i>
                                            <select name="positions[{{ $idx }}][job_type_id]" class="ui-select">
                                                <option value="">{{ __('pages.select_please') }}</option>
                                                @foreach($jobTypes as $jobType)
                                                    <option value="{{ $jobType->id }}" @selected(old("positions.$idx.job_type_id", $pos->job_type_id)===$jobType->id)>{{ $jobType->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <button type="button" class="remove-position-btn inline-flex items-center justify-center h-10 w-10 rounded-lg border border-red-300 text-red-600 hover:bg-red-50 hover:border-red-400 transition-all {{ $loop->first ? 'hidden' : '' }}">
                                            <i data-lucide="trash-2" class="w-4 h-4"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div class="position-plans border-t border-slate-200 pt-3">
                                <p class="text-[12px] font-semibold text-slate-600 mb-2">この所属の契約プラン</p>
                                <div class="space-y-1.5 max-h-36 overflow-y-auto">
                                    @forelse(($availablePlans ?? []) as $ccp)
                                        <label class="flex items-center gap-2 text-sm">
                                            <input type="checkbox"
                                                   name="positions[{{ $idx }}][customer_contract_plan_ids][]"
                                                   value="{{ $ccp->id }}"
                                                   {{ in_array($ccp->id, (array) $posPlanIds) ? 'checked' : '' }}>
                                            <span class="font-mono text-xs">{{ $ccp->contractPlan->code ?? '' }}</span>
                                            <span>{{ $ccp->contractPlan->name ?? '' }}</span>
                                        </label>
                                    @empty
                                        <p class="text-slate-500 text-xs">本登録の契約プランがありません</p>
                                    @endforelse
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <button type="button" id="add-position-btn"
                        class="mt-4 inline-flex items-center gap-2 px-4 py-2.5 rounded-lg border border-dashed border-slate-300 text-slate-700 hover:bg-slate-50 text-[14px] font-medium transition-all">
                    <i data-lucide="plus" class="w-4 h-4"></i>
                    {{ __('pages.add_position') }}
                </button>
                <p class="mt-2 text-[12px] text-slate-500 flex items-center gap-1.5">
                    <i data-lucide="info" class="w-3.5 h-3.5"></i>
                    {{ __('pages.position_note') }}
                </p>
            </div>

            {{-- Form Footer --}}
            <div class="px-6 py-4 bg-slate-50 border-t border-slate-200 flex items-center justify-between">
                <p class="text-[12px] text-slate-500 flex items-center gap-1.5">
                    <i data-lucide="info" class="w-3.5 h-3.5"></i>
                    {{ __('pages.required_fields_note') }}
                </p>
                <div class="flex items-center gap-3">
                    <a href="{{ route('employees.show', $employee) }}" class="px-5 py-2.5 rounded-lg border border-slate-300 text-slate-700 hover:bg-white text-[14px] font-medium transition-all flex items-center gap-2">
                        <i data-lucide="x" class="w-4 h-4"></i>
                        {{ __('common.cancel') }}
                    </a>
                    <button type="submit" class="px-6 py-2.5 rounded-lg bg-primary-600 hover:bg-primary-700 text-white text-[14px] font-semibold shadow-md transition-all flex items-center gap-2">
                        <i data-lucide="check" class="w-4 h-4"></i>
                        {{ __('pages.update') }}
                    </button>
                </div>
            </div>
        </div>
    </form>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const addBtn = document.getElementById('add-position-btn');
            const area = document.getElementById('positions-area');
            if (!addBtn || !area) return;

            let index = area.querySelectorAll('.position-row').length;

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
                
                // Reinitialize Lucide icons
                if (typeof lucide !== 'undefined') {
                    lucide.createIcons();
                }
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

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
        setTimeout(() => { if (typeof lucide !== 'undefined') lucide.createIcons(); }, 100);
        setTimeout(() => { if (typeof lucide !== 'undefined') lucide.createIcons(); }, 500);
    }
});
</script>
@endpush
@endsection
