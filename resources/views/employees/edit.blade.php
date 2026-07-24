@extends('layouts.app')

@section('title', __('pages.edit_employee') . ' | ' . config('app.name'))
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
                    {{-- Employee Name --}}
                    <div>
                        <label class="block text-slate-700 text-[14px] font-semibold mb-2">
                            <span class="flex items-center gap-2">
                                <i data-lucide="user" class="w-4 h-4 text-slate-500"></i>
                                {{ __('pages.employee_name') }} <span class="text-red-500">*</span>
                            </span>
                        </label>
                        <div class="relative">
                            <i data-lucide="user" class="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400 pointer-events-none"></i>
                            <input type="text" name="name" value="{{ old('name', $employee->name) }}" required
                                   class="w-full pl-10 pr-4 py-2.5 rounded-lg border border-slate-300 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 text-[14px] bg-white shadow-sm transition-all placeholder:text-slate-400"
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
                        <div class="relative">
                            <i data-lucide="check-circle" class="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400 pointer-events-none z-10"></i>
                            <select name="status" required
                                    class="w-full pl-10 pr-10 py-2.5 rounded-lg border border-slate-300 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 text-[14px] bg-white shadow-sm transition-all appearance-none cursor-pointer">
                                <option value="employed" @selected(old('status', $employee->status)==='employed')>{{ __('pages.employed') }}</option>
                                <option value="on_leave" @selected(old('status', $employee->status)==='on_leave')>{{ __('pages.on_leave') }}</option>
                                <option value="retired" @selected(old('status', $employee->status)==='retired')>{{ __('pages.retired') }}</option>
                            </select>
                            <i data-lucide="chevron-down" class="absolute right-4 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400 pointer-events-none"></i>
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
                        <div class="relative">
                            <i data-lucide="users" class="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400 pointer-events-none z-10"></i>
                            <select name="gender"
                                    class="w-full pl-10 pr-10 py-2.5 rounded-lg border border-slate-300 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 text-[14px] bg-white shadow-sm transition-all appearance-none cursor-pointer">
                                <option value="">{{ __('pages.not_set') }}</option>
                                <option value="male" @selected(old('gender', $employee->gender)==='male')>{{ __('pages.male') }}</option>
                                <option value="female" @selected(old('gender', $employee->gender)==='female')>{{ __('pages.female') }}</option>
                                <option value="other" @selected(old('gender', $employee->gender)==='other')>{{ __('pages.other') }}</option>
                            </select>
                            <i data-lucide="chevron-down" class="absolute right-4 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400 pointer-events-none"></i>
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
                            <div class="relative">
                                <i data-lucide="briefcase" class="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400 pointer-events-none z-10"></i>
                                <select name="primary_department_id" required
                                        class="w-full pl-10 pr-10 py-2.5 rounded-lg border border-slate-300 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 text-[14px] bg-white shadow-sm transition-all appearance-none cursor-pointer">
                                    <option value="">{{ __('pages.select_please') }}</option>
                                    @foreach($departments as $dept)
                                        <option value="{{ $dept->id }}" @selected(old('primary_department_id', $employee->primary_department_id)===$dept->id)>{{ $dept->name }}</option>
                                    @endforeach
                                </select>
                                <i data-lucide="chevron-down" class="absolute right-4 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400 pointer-events-none"></i>
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
                        <div class="relative">
                            <i data-lucide="shirt" class="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400 pointer-events-none z-10"></i>
                            <select name="top_size"
                                    class="w-full pl-10 pr-10 py-2.5 rounded-lg border border-slate-300 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 text-[14px] bg-white shadow-sm transition-all appearance-none cursor-pointer">
                                <option value="">{{ __('pages.not_set') }}</option>
                                @foreach(['XS','S','M','L','XL','XXL'] as $size)
                                    <option value="{{ $size }}" @selected(old('top_size', $employee->top_size)===$size)>{{ $size }}</option>
                                @endforeach
                            </select>
                            <i data-lucide="chevron-down" class="absolute right-4 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400 pointer-events-none"></i>
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
                        <div class="relative">
                            <i data-lucide="package" class="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400 pointer-events-none z-10"></i>
                            <select name="bottom_size"
                                    class="w-full pl-10 pr-10 py-2.5 rounded-lg border border-slate-300 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 text-[14px] bg-white shadow-sm transition-all appearance-none cursor-pointer">
                                <option value="">{{ __('pages.not_set') }}</option>
                                @foreach(['XS','S','M','L','XL','XXL'] as $size)
                                    <option value="{{ $size }}" @selected(old('bottom_size', $employee->bottom_size)===$size)>{{ $size }}</option>
                                @endforeach
                            </select>
                            <i data-lucide="chevron-down" class="absolute right-4 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400 pointer-events-none"></i>
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
                        <div class="relative">
                            <i data-lucide="footprints" class="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400 pointer-events-none z-10"></i>
                            <select name="shoe_size"
                                    class="w-full pl-10 pr-10 py-2.5 rounded-lg border border-slate-300 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 text-[14px] bg-white shadow-sm transition-all appearance-none cursor-pointer">
                                <option value="">{{ __('pages.not_set') }}</option>
                                @foreach(['22.0','22.5','23.0','23.5','24.0','24.5','25.0','25.5','26.0','26.5','27.0','27.5','28.0'] as $size)
                                    <option value="{{ $size }}" @selected(old('shoe_size', $employee->shoe_size)===$size)>{{ $size }}</option>
                                @endforeach
                            </select>
                            <i data-lucide="chevron-down" class="absolute right-4 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400 pointer-events-none"></i>
                        </div>
                        @error('shoe_size')
                            <p class="text-[13px] text-red-600 mt-2 flex items-center gap-1.5">
                                <i data-lucide="alert-circle" class="w-3.5 h-3.5"></i>
                                {{ $message }}
                            </p>
                        @enderror
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
                    <p class="text-[13px] text-slate-500 mb-4">{{ __('pages.multiple_positions_description') }}</p>
                </div>

                <div id="positions-area" class="space-y-4">
                    @php
                        $positions = $employee->positions->count() > 0 ? $employee->positions : collect([new \App\Models\EmployeePosition()]);
                    @endphp
                    @foreach($positions as $idx => $pos)
                        <div class="position-row bg-slate-50 border border-slate-200 rounded-lg p-4">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 items-end">
                                <div>
                                    <label class="block text-slate-700 text-[13px] font-semibold mb-2">
                                        <span class="flex items-center gap-2">
                                            <i data-lucide="building" class="w-4 h-4 text-slate-500"></i>
                                            {{ __('pages.department') }}
                                        </span>
                                    </label>
                                    <div class="relative">
                                        <i data-lucide="building" class="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400 pointer-events-none z-10"></i>
                                        <select name="positions[{{ $idx }}][department_id]"
                                                class="w-full pl-10 pr-10 py-2.5 rounded-lg border border-slate-300 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 text-[14px] bg-white shadow-sm transition-all appearance-none cursor-pointer">
                                            <option value="">{{ __('pages.select_please') }}</option>
                                            @foreach($departments as $dept)
                                                <option value="{{ $dept->id }}" @selected(old("positions.$idx.department_id", $pos->department_id)===$dept->id)>{{ $dept->name }}</option>
                                            @endforeach
                                        </select>
                                        <i data-lucide="chevron-down" class="absolute right-4 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400 pointer-events-none"></i>
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
                                        <div class="relative flex-1">
                                            <i data-lucide="user-check" class="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400 pointer-events-none z-10"></i>
                                            <select name="positions[{{ $idx }}][job_type_id]"
                                                    class="w-full pl-10 pr-10 py-2.5 rounded-lg border border-slate-300 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 text-[14px] bg-white shadow-sm transition-all appearance-none cursor-pointer">
                                                <option value="">{{ __('pages.select_please') }}</option>
                                                @foreach($jobTypes as $jobType)
                                                    <option value="{{ $jobType->id }}" @selected(old("positions.$idx.job_type_id", $pos->job_type_id)===$jobType->id)>{{ $jobType->name }}</option>
                                                @endforeach
                                            </select>
                                            <i data-lucide="chevron-down" class="absolute right-4 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400 pointer-events-none"></i>
                                        </div>
                                        <button type="button" class="remove-position-btn inline-flex items-center justify-center h-10 w-10 rounded-lg border border-red-300 text-red-600 hover:bg-red-50 hover:border-red-400 transition-all {{ $loop->first ? 'hidden' : '' }}">
                                            <i data-lucide="trash-2" class="w-4 h-4"></i>
                                        </button>
                                    </div>
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
