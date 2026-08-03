@extends('layouts.app')

@section('title', __('pages.new_application') . ' | ' . __('auth.system_title'))
@section('page_title', __('pages.new_application'))

@section('content')
    @if(session('error'))
        <div class="mb-6 p-4 bg-red-50 border-l-4 border-red-500 rounded-lg shadow-sm">
            <div class="flex items-center gap-2">
                <i data-lucide="alert-circle" class="w-5 h-5 text-red-600"></i>
                <span class="text-[14px] text-red-800 font-medium">{{ session('error') }}</span>
            </div>
        </div>
    @endif

    @if(session('status'))
        <div class="mb-6 p-4 bg-green-50 border-l-4 border-green-500 rounded-lg shadow-sm">
            <div class="flex items-center gap-2">
                <i data-lucide="check-circle" class="w-5 h-5 text-green-600"></i>
                <span class="text-[14px] text-green-800 font-medium">{{ session('status') }}</span>
            </div>
        </div>
    @endif

    <form method="POST" action="{{ route('applications.store') }}" id="application-form">
        @csrf
        <input type="hidden" name="application_mode" id="application-mode" value="{{ old('application_mode', 'individual') }}">
        <input type="hidden" name="employee_id" id="selected-employee-id" value="{{ old('employee_id') }}">

        {{-- Header --}}
        <div class="mb-6 flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-slate-900 tracking-tight">{{ __('pages.new_application') }}</h1>
                <p class="text-[14px] text-slate-600 mt-1.5">{{ __('pages.application_description') }}</p>
            </div>
            <div class="flex items-center gap-3">
                <a href="{{ route('applications.index') }}" class="px-5 py-2.5 rounded-lg border border-slate-300 text-slate-700 hover:bg-slate-50 text-[14px] font-medium transition-all">
                    {{ __('pages.cancel') }}
                </a>
                <button type="submit" class="px-6 py-2.5 rounded-lg bg-primary-600 hover:bg-primary-700 text-white text-[14px] font-semibold shadow-md transition-all flex items-center gap-2">
                    <i data-lucide="send" class="w-4 h-4"></i>
                    {{ __('pages.submit_application') }}
                </button>
            </div>
        </div>

        {{-- Vertical layout: application info, then product selection --}}
        <div class="space-y-6">
            {{-- 1) Application Information --}}
            <div class="bg-white border border-slate-200 rounded-xl shadow-sm overflow-hidden">
                <div class="p-6 border-b border-slate-200 bg-gradient-to-r from-primary-50 to-blue-50">
                    <h2 class="text-lg font-semibold text-slate-900 flex items-center gap-2">
                        <i data-lucide="file-text" class="w-5 h-5 text-primary-600"></i>
                        {{ __('pages.application_information') }}
                    </h2>
                </div>

                <div class="p-6 space-y-6 max-w-4xl">
                    {{-- Application Type --}}
                    <div>
                        <label class="block text-slate-700 text-[14px] font-semibold mb-2 flex items-center gap-2">
                            <i data-lucide="file-text" class="w-4 h-4 text-slate-500"></i>
                            {{ __('pages.application_type') }} <span class="text-red-500">*</span>
                        </label>
                        <div class="relative">
                            <select name="type" id="application-type-select" required
                                    class="w-full px-4 py-2.5 rounded-lg border border-slate-300 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 text-[14px] bg-white shadow-sm transition-all cursor-pointer">
                                <option value="">{{ __('pages.select_please') }}</option>
                                <option value="new_loan" @selected(old('type')==='new_loan')>{{ __('pages.new_loan') }}</option>
                                <option value="resize" @selected(old('type')==='resize')>{{ __('pages.size_exchange') }}</option>
                                <option value="exchange" @selected(old('type')==='exchange')>{{ __('pages.exchange') }}</option>
                                <option value="return" @selected(old('type')==='return')>{{ __('pages.return') }}</option>
                                <option value="transfer" @selected(old('type')==='transfer')>{{ __('pages.transfer') }}</option>
                            </select>
                        </div>
                        @error('type')
                            <p class="text-[13px] text-red-600 mt-1.5">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Application Category --}}
                    <div>
                        <label class="block text-slate-700 text-[14px] font-semibold mb-3 flex items-center gap-2">
                            <i data-lucide="list-bullets" class="w-4 h-4 text-slate-500"></i>
                            {{ __('pages.application_category') }}
                        </label>
                        <div class="flex gap-4">
                            <label class="flex items-center gap-2 p-3 rounded-lg border-2 cursor-pointer transition-all application-category-option {{ old('application_mode', 'individual') === 'individual' ? 'border-primary-500 bg-primary-50' : 'border-slate-300 bg-white' }}" data-category="individual">
                                <input type="radio" name="application_mode_radio" value="individual" {{ old('application_mode', 'individual') === 'individual' ? 'checked' : '' }} class="text-primary-600">
                                <i data-lucide="user" class="w-4 h-4 text-primary-600"></i>
                                <span class="text-[14px] font-medium text-slate-900">{{ __('pages.individual_label') }}</span>
                            </label>
                            <label class="flex items-center gap-2 p-3 rounded-lg border-2 cursor-pointer transition-all application-category-option {{ old('application_mode') === 'departmental' ? 'border-primary-500 bg-primary-50' : 'border-slate-300 bg-white' }}" data-category="departmental">
                                <input type="radio" name="application_mode_radio" value="departmental" {{ old('application_mode') === 'departmental' ? 'checked' : '' }} class="text-primary-600">
                                <i data-lucide="users" class="w-4 h-4 text-primary-600"></i>
                                <span class="text-[14px] font-medium text-slate-900">{{ __('pages.department_batch') }}</span>
                            </label>
                        </div>
                    </div>

                    {{-- Department Selection (for both modes) --}}
                    <div id="department-selection-field">
                        <label class="block text-slate-700 text-[14px] font-semibold mb-2 flex items-center gap-2">
                            <i data-lucide="building-2" class="w-4 h-4 text-slate-500"></i>
                            {{ __('pages.select_department') }} <span class="text-red-500">*</span>
                        </label>
                        <div class="relative">
                            <select required name="department_id" id="department-select"
                                    class="w-full px-4 py-2.5 rounded-lg border border-slate-300 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 text-[14px] bg-white shadow-sm cursor-pointer">
                                <option value="">{{ __('pages.select_please') }}</option>
                                @foreach($departments as $dept)
                                    <option value="{{ $dept->id }}" @selected(old('department_id')==$dept->id)>
                                        {{ $dept->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        
                        {{-- Department Employees List (for departmental mode) --}}
                        <div id="department-employees-list" class="mt-4 p-4 bg-gradient-to-r from-slate-50 to-blue-50 rounded-lg border border-slate-200 hidden">
                            <div class="flex items-center gap-2 mb-3">
                                <i data-lucide="users" class="w-5 h-5 text-primary-600"></i>
                                <h3 class="text-[14px] font-semibold text-slate-900">{{ __('pages.employee_list_label') }}</h3>
                            </div>
                            <div id="department-employees-content" class="space-y-2 max-h-60 overflow-y-auto">
                                <p class="text-[13px] text-slate-500 text-center py-4">{{ __('pages.select_department') }} {{ __('pages.to_view_employees') }}</p>
                            </div>
                        </div>
                        
                        @error('department_id')
                            <p class="text-[13px] text-red-600 mt-1.5">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Employee Search (for individual mode) --}}
                    <div id="employee-search-field" class="hidden">
                        <label class="block text-slate-700 text-[14px] font-semibold mb-2 flex items-center gap-2">
                            <i data-lucide="user-search" class="w-4 h-4 text-slate-500"></i>
                            {{ __('pages.employee_search_placeholder') }} <span class="text-red-500">*</span>
                        </label>
                        <div class="relative">
                            {{-- Searchable Select Container --}}
                            <div id="employee-select-container" class="relative">
                                <div id="employee-select-trigger" class="w-full px-4 py-2.5 pl-12 pr-10 rounded-lg border border-slate-300 focus-within:border-primary-500 focus-within:ring-2 focus-within:ring-primary-500/20 text-[14px] bg-white shadow-sm cursor-pointer flex items-center justify-between min-h-[42px] hover:border-primary-400 transition-colors">
                                    <span id="employee-select-text" class="text-slate-500 flex-1 truncate pr-2">{{ __('pages.select_please') }}</span>
                                    <i data-lucide="chevron-down" id="employee-select-chevron" class="w-4 h-4 text-slate-400 flex-shrink-0 transition-transform ml-2"></i>
                                </div>
{{-- Dropdown --}}
                                <div id="employee-select-dropdown" class="hidden absolute z-50 w-full mt-1 bg-white border border-slate-200 rounded-lg shadow-lg max-h-60 overflow-hidden">
                                    {{-- Search Input --}}
                                    <div class="p-2 border-b border-slate-200 sticky top-0 bg-white">
                                        <div class="relative">
                                            <input type="text" id="employee-select-search" 
                                                   placeholder="{{ __('pages.search') }}..."
                                                   class="w-full px-3 py-2 pl-11 pr-3 text-[13px] border border-slate-300 rounded-lg focus:border-primary-500 focus:ring-1 focus:ring-primary-500/20">
</div>
                                    </div>
                                    {{-- Options List --}}
                                    <div id="employee-select-options" class="overflow-y-auto max-h-52 py-1">
                                        <div class="px-3 py-2 text-[13px] text-slate-500 text-center">{{ __('pages.select_department_first') }}</div>
                                    </div>
                                </div>
                            </div>
                            <input type="hidden" id="selected-employee-id" name="employee_id">
                        </div>
                        
                        {{-- Selected Employee Card --}}
                        <div id="selected-employee-card" class="mt-4 p-4 bg-gradient-to-r from-blue-50 to-indigo-50 rounded-lg border border-blue-200 hidden">
                            <div class="flex items-start gap-4">
                                <div class="w-16 h-16 rounded-full bg-primary-100 flex items-center justify-center flex-shrink-0">
                                    <i data-lucide="user" class="w-8 h-8 text-primary-600"></i>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <h3 class="font-semibold text-slate-900 text-[15px] mb-1" id="employee-name"></h3>
                                    <p class="text-[13px] text-slate-600 mb-1">
                                        <span class="font-medium">{{ __('pages.employee_id_label') }}:</span> 
                                        <span id="employee-staff-no"></span>
                                    </p>
                                    <p class="text-[13px] text-slate-600 mb-2">
                                        <span class="font-medium">{{ __('pages.job_type_label') }}:</span> 
                                        <span id="employee-job-type"></span>
                                    </p>
                                    <div class="flex flex-wrap gap-3 text-[12px]">
                                        <span class="text-slate-600">
                                            <span class="font-medium">{{ __('pages.jacket') }}:</span> 
                                            <span id="employee-top-size" class="text-slate-900"></span>
                                        </span>
                                        <span class="text-slate-600">
                                            <span class="font-medium">{{ __('pages.pants') }}:</span> 
                                            <span id="employee-bottom-size" class="text-slate-900"></span>
                                        </span>
                                        <span class="text-slate-600">
                                            <span class="font-medium">{{ __('pages.shoes_label') }}:</span> 
                                            <span id="employee-shoe-size" class="text-slate-900"></span>
                                        </span>
                                        <span class="text-slate-600">
                                            <span class="font-medium">股下:</span>
                                            <span id="employee-inseam" class="text-slate-900"></span>
                                        </span>
                                        <span class="text-slate-600">
                                            <span class="font-medium">体型:</span>
                                            <span id="employee-body-type" class="text-slate-900"></span>
                                        </span>
                                    </div>
                                    <p class="text-[12px] text-slate-600 mt-2">
                                        <span class="font-medium">契約プラン:</span>
                                        <span id="employee-plans" class="text-slate-900"></span>
                                    </p>
                                </div>
                                <button type="button" id="clear-employee-btn" class="text-slate-400 hover:text-red-600 transition-colors">
                                    <i data-lucide="x" class="w-5 h-5"></i>
                                </button>
                            </div>
                        </div>
                        @error('employee_id')
                            <p class="text-[13px] text-red-600 mt-1.5">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- 移管先職員 --}}
                    <div id="transfer-target-field" class="hidden">
                        <label class="block text-slate-700 text-[14px] font-semibold mb-2 flex items-center gap-2">
                            <i data-lucide="user-round-plus" class="w-4 h-4 text-slate-500"></i>
                            移管先職員 <span class="text-red-500">*</span>
                        </label>
                        <select name="transfer_to_employee_id" id="transfer-to-employee-select"
                                class="w-full px-4 py-2.5 rounded-lg border border-slate-300 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 text-[14px] bg-white shadow-sm">
                            <option value="">選択してください</option>
                            @foreach($employees as $emp)
                                <option value="{{ $emp->id }}" @selected(old('transfer_to_employee_id')==$emp->id)>
                                    {{ $emp->staff_no }} — {{ $emp->name }}
                                </option>
                            @endforeach
                        </select>
                        <p class="text-[12px] text-slate-500 mt-1.5">貸与品を受け取る職員を指定します。</p>
                        @error('transfer_to_employee_id')
                            <p class="text-[13px] text-red-600 mt-1.5">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- 現在の貸与（返却・交換・移管用） --}}
                    <div id="current-loans-panel" class="hidden">
                        <label class="block text-slate-700 text-[14px] font-semibold mb-2 flex items-center gap-2">
                            <i data-lucide="package-minus" class="w-4 h-4 text-amber-600"></i>
                            <span id="current-loans-title">返却・交換する貸与品</span>
                        </label>
                        <div id="current-loans-list" class="space-y-2 max-h-56 overflow-y-auto border border-slate-200 rounded-lg p-3 bg-slate-50">
                            <p class="text-[13px] text-slate-500">職員を選択すると貸与中の品が表示されます。</p>
                        </div>
                        <div id="return-items-hidden"></div>
                    </div>

                    {{-- Loan Start Date --}}
                    <div>
                        <label class="block text-slate-700 text-[14px] font-semibold mb-2 flex items-center gap-2">
                            <i data-lucide="calendar" class="w-4 h-4 text-slate-500"></i>
                            {{ __('pages.loan_start_date') }} <span class="text-red-500">*</span>
                        </label>
                        <input type="date" name="start_date" id="start-date-input" value="{{ old('start_date', date('Y-m-d')) }}" required
                               class="w-full px-4 py-2.5 rounded-lg border border-slate-300 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 text-[14px] bg-white shadow-sm">
                        @error('start_date')
                            <p class="text-[13px] text-red-600 mt-1.5">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Loan End Date --}}
                    <div>
                        <label class="block text-slate-700 text-[14px] font-semibold mb-2 flex items-center gap-2">
                            <i data-lucide="calendar-days" class="w-4 h-4 text-slate-500"></i>
                            {{ __('pages.loan_end_date') }} <span class="text-red-500">*</span>
                        </label>
                        <input type="date" name="end_date" id="end-date-input" value="{{ old('end_date') }}" required
                               class="w-full px-4 py-2.5 rounded-lg border border-slate-300 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 text-[14px] bg-white shadow-sm">
                        @error('end_date')
                            <p class="text-[13px] text-red-600 mt-1.5">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Delivery Address --}}
                    <div>
                        <label class="block text-slate-700 text-[14px] font-semibold mb-2.5 flex items-center gap-2">
                            <i data-lucide="map-pin" class="w-4 h-4 text-primary-600"></i>
                            {{ __('pages.delivery_location') }} <span class="text-red-500">*</span>
                        </label>
                        <select name="delivery_location_id" required
                                class="w-full px-4 py-3 rounded-xl border-2 border-slate-300 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 text-[14px] bg-white shadow-sm font-medium cursor-pointer">
                            <option value="">{{ __('pages.select_please') }}</option>
                            @foreach($deliveryLocations as $loc)
                                <option value="{{ $loc->id }}" @selected(old('delivery_location_id')==$loc->id)>
                                    {{ $loc->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('delivery_location_id')
                            <p class="text-[13px] text-red-600 mt-1.5">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Contract Limit Progress Bar (plan rentable points) --}}
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-[14px] font-semibold text-slate-700 flex items-center gap-2">
                                <i data-lucide="shield-check" class="w-4 h-4 text-primary-600"></i>
                                プラン貸与上限
                            </span>
                            <span class="text-[14px] text-slate-600">
                                <span id="selected-items-count">0</span>{{ __('pages.items') }} / 
                                <span id="contract-limit">—</span>{{ __('pages.items') }}
                                <span class="text-[12px] text-slate-500">（職員選択でプラン上限に更新）</span>
                            </span>
                        </div>
                        <div class="w-full bg-slate-200 rounded-full h-3 overflow-hidden shadow-inner">
                            <div id="contract-limit-progress" class="h-full bg-gradient-to-r from-primary-500 via-blue-600 to-indigo-600 transition-all duration-300 rounded-full" style="width: 0%"></div>
                        </div>
                        <p class="text-[12px] text-slate-500 mt-1.5" id="contract-limit-text"></p>
                    </div>

                    {{-- Application memos (SPEC / PDF) --}}
                    <div class="space-y-3">
                        <div>
                            <label class="block text-slate-700 text-[14px] font-semibold mb-2">メモ1</label>
                            <input type="text" name="memo1" value="{{ old('memo1') }}" maxlength="500"
                                   class="w-full px-4 py-2.5 rounded-lg border border-slate-300 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 text-[14px]"
                                   placeholder="帳票に印字されるメモ1">
                            @error('memo1')
                                <p class="text-[13px] text-red-600 mt-1.5">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label class="block text-slate-700 text-[14px] font-semibold mb-2">メモ2</label>
                            <input type="text" name="memo2" value="{{ old('memo2') }}" maxlength="500"
                                   class="w-full px-4 py-2.5 rounded-lg border border-slate-300 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 text-[14px]"
                                   placeholder="帳票に印字されるメモ2">
                            @error('memo2')
                                <p class="text-[13px] text-red-600 mt-1.5">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    {{-- Special Approval Button (Optional) --}}
                    <div id="special-approval-button-container" class="hidden">
                        <button type="button" id="open-special-approval-modal" 
                                class="w-full flex items-center justify-center gap-2 px-4 py-2.5 rounded-lg border-2 border-amber-300 bg-amber-50 text-amber-700 hover:bg-amber-100 transition-all text-[14px] font-medium">
                            <i data-lucide="alert-triangle" class="w-4 h-4"></i>
                            {{ __('pages.special_approval_reason') }} ({{ __('pages.optional') }})
                        </button>
                        <input type="hidden" id="special_approval_reason" name="special_approval_reason" value="{{ old('special_approval_reason') }}">
                        @error('special_approval_reason')
                            <p class="text-[13px] text-red-600 mt-1.5">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            {{-- Special Approval Modal --}}
            <div id="special-approval-modal" class="fixed inset-0 bg-black/50 z-[9998] hidden items-center justify-center">
                <div class="bg-white rounded-xl shadow-2xl max-w-lg w-full mx-4 transform transition-all">
                    <div class="px-6 py-5 border-b border-slate-200 flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="h-10 w-10 rounded-lg bg-amber-100 flex items-center justify-center">
                                <i data-lucide="alert-triangle" class="w-5 h-5 text-amber-600"></i>
                            </div>
                            <h3 class="text-lg font-semibold text-slate-900">{{ __('pages.special_approval_reason') }}</h3>
                        </div>
                        <button type="button" id="close-special-approval-modal" class="text-slate-400 hover:text-slate-600 transition-colors">
                            <i data-lucide="x" class="w-5 h-5"></i>
                        </button>
                    </div>
                    <div class="px-6 py-5">
                        <p class="text-[14px] text-slate-600 mb-4">{{ __('pages.special_approval_description') }}</p>
                        <textarea 
                            id="special_approval_reason_textarea" 
                            rows="4" 
                            class="w-full px-4 py-2.5 border border-slate-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 text-[14px] text-slate-900 placeholder-slate-400"
                            placeholder="{{ __('pages.special_approval_placeholder') }}"
                        >{{ old('special_approval_reason') }}</textarea>
                    </div>
                    <div class="px-6 py-4 border-t border-slate-200 flex items-center justify-end gap-3">
                        <button type="button" id="cancel-special-approval" class="px-4 py-2 rounded-lg border border-slate-300 text-slate-700 hover:bg-slate-50 text-[14px] font-medium transition-all">
                            {{ __('pages.cancel') }}
                        </button>
                        <button type="button" id="save-special-approval" class="px-4 py-2 rounded-lg bg-primary-600 text-white hover:bg-primary-700 text-[14px] font-medium transition-all flex items-center gap-2">
                            <i data-lucide="check" class="w-4 h-4"></i>
                            {{ __('pages.save') }}
                        </button>
                    </div>
                </div>
            </div>

            {{-- 2) Product Selection (below application info) --}}
            <div class="bg-white border border-slate-200 rounded-xl shadow-sm overflow-hidden">
                <div class="p-6 border-b border-slate-200 bg-gradient-to-r from-primary-50 to-blue-50">
                    <h2 class="text-lg font-semibold text-slate-900 flex items-center gap-2">
                        <i data-lucide="shopping-cart" class="w-5 h-5 text-primary-600"></i>
                        {{ __('pages.product_selection') }}
                    </h2>
                    <p class="text-[13px] text-slate-600 mt-1">申請情報の入力後、対象ユニフォームを選択してください。</p>
                </div>

                <div class="p-6">
                    {{-- Category Tabs --}}
                    <div class="flex flex-wrap gap-2 mb-6 border-b-2 border-slate-200">
                        <button type="button" class="category-tab active px-5 py-3 text-[14px] font-semibold text-primary-600 bg-primary-50 border-b-3 border-primary-600 hover:text-primary-700 hover:bg-primary-100 transition-all flex items-center gap-2 rounded-t-lg" data-category="top">
                            <i data-lucide="shirt" class="w-5 h-5"></i>
                            {{ __('pages.tops') }}
                        </button>
                        <button type="button" class="category-tab px-5 py-3 text-[14px] font-semibold text-slate-600 border-b-3 border-transparent hover:text-primary-600 hover:border-primary-500 hover:bg-slate-50 transition-all flex items-center gap-2 rounded-t-lg" data-category="bottom">
                            <i data-lucide="pants" class="w-5 h-5"></i>
                            {{ __('pages.pants') }}
                        </button>
                        <button type="button" class="category-tab px-5 py-3 text-[14px] font-semibold text-slate-600 border-b-3 border-transparent hover:text-primary-600 hover:border-primary-500 hover:bg-slate-50 transition-all flex items-center gap-2 rounded-t-lg" data-category="shoe">
                            <i data-lucide="footprints" class="w-5 h-5"></i>
                            {{ __('pages.shoes_label') }}
                        </button>
                       
                    </div>

                    {{-- Products Grid --}}
                    <div id="products-container" class="space-y-4 py-2 overflow-y-auto">
                        {{-- Products will be loaded here via JavaScript --}}
                        <div class="text-center py-12 text-slate-500">
                            <i data-lucide="shopping-bag" class="w-12 h-12 mx-auto mb-3 text-slate-300"></i>
                            <p class="text-[14px]">{{ __('pages.no_products_available') }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Hidden form fields for selected items --}}
        <div id="selected-items-container" class="hidden"></div>
    </form>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    // Initialize Lucide icons
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }

    const applicationModeInput = document.getElementById('application-mode');
    const employeeSelectContainer = document.getElementById('employee-select-container');
    const employeeSelectTrigger = document.getElementById('employee-select-trigger');
    const employeeSelectText = document.getElementById('employee-select-text');
    const employeeSelectDropdown = document.getElementById('employee-select-dropdown');
    const employeeSelectSearch = document.getElementById('employee-select-search');
    const employeeSelectOptions = document.getElementById('employee-select-options');
    const employeeSelectChevron = document.getElementById('employee-select-chevron');
    const employeeSearchResults = document.getElementById('employee-search-results');
    const selectedEmployeeCard = document.getElementById('selected-employee-card');
    const selectedEmployeeId = document.getElementById('selected-employee-id');
    const clearEmployeeBtn = document.getElementById('clear-employee-btn');
    
    // Store employees data for searchable select
    let employeesData = [];
    let isEmployeeDropdownOpen = false;
    let filteredEmployeesData = [];
    const departmentSelect = document.getElementById('department-select');
    const departmentSelectionField = document.getElementById('department-selection-field');
    const departmentEmployeesList = document.getElementById('department-employees-list');
    const departmentEmployeesContent = document.getElementById('department-employees-content');
    const employeeSearchField = document.getElementById('employee-search-field');
    const categoryTabs = document.querySelectorAll('.category-tab');
    const productsContainer = document.getElementById('products-container');
    const selectedItemsContainer = document.getElementById('selected-items-container');
    let contractLimit = parseInt(document.getElementById('contract-limit').textContent) || 5;
    const contractLimitEl = document.getElementById('contract-limit');
    const contractLimitProgress = document.getElementById('contract-limit-progress');
    const selectedItemsCount = document.getElementById('selected-items-count');
    const contractLimitText = document.getElementById('contract-limit-text');

    let allUniforms = @json($uniformsJson);
    let filteredUniforms = [];
    let selectedItems = new Map();
    let returnItems = new Map(); // product_id => { quantity, max, name, size }
    const returnItemsHidden = document.getElementById('return-items-hidden');
    const currentLoansPanel = document.getElementById('current-loans-panel');
    const currentLoansList = document.getElementById('current-loans-list');
    const transferTargetField = document.getElementById('transfer-target-field');
    const applicationTypeSelect = document.getElementById('application-type-select');
    const transferToSelect = document.getElementById('transfer-to-employee-select');
    let currentCategory = 'top';
    let searchTimeout;

    // ========== Searchable Select Functions ==========
    
    // Reset employee select
    function resetEmployeeSelect() {
        if (employeeSelectText) employeeSelectText.textContent = '{{ __('pages.select_please') }}';
        if (employeeSelectText) employeeSelectText.classList.add('text-slate-500');
        if (employeeSelectText) employeeSelectText.classList.remove('text-slate-900');
        if (selectedEmployeeId) selectedEmployeeId.value = '';
        if (employeeSelectSearch) employeeSelectSearch.value = '';
        closeEmployeeDropdown();
        employeesData = [];
        filteredEmployeesData = [];
    }
    
    // Open employee dropdown
    function openEmployeeDropdown() {
        if (!employeeSelectDropdown || !employeeSelectTrigger || !employeeSelectChevron) return;
        isEmployeeDropdownOpen = true;
        employeeSelectDropdown.classList.remove('hidden');
        employeeSelectTrigger.classList.add('border-primary-500', 'ring-2', 'ring-primary-500/20');
        employeeSelectChevron.classList.add('rotate-180');
        if (employeeSelectSearch) {
            setTimeout(() => employeeSelectSearch.focus(), 100);
        }
    }
    
    // Close employee dropdown
    function closeEmployeeDropdown() {
        if (!employeeSelectDropdown || !employeeSelectTrigger || !employeeSelectChevron) return;
        isEmployeeDropdownOpen = false;
        employeeSelectDropdown.classList.add('hidden');
        employeeSelectTrigger.classList.remove('border-primary-500', 'ring-2', 'ring-primary-500/20');
        employeeSelectChevron.classList.remove('rotate-180');
    }
    
    // Toggle employee dropdown
    function toggleEmployeeDropdown() {
        if (isEmployeeDropdownOpen) {
            closeEmployeeDropdown();
        } else {
            const departmentId = departmentSelect?.value;
            if (!departmentId) {
                window.showToast('{{ __('pages.select_department_first') }}', 'warning');
                return;
            }
            if (employeesData.length === 0) {
                loadEmployeesForSelect(departmentId);
            }
            openEmployeeDropdown();
        }
    }
    
    // Filter employees by search query
    function filterEmployeesBySearch(query) {
        if (!employeeSelectOptions) return;
        const searchLower = query.toLowerCase().trim();
        
        if (searchLower === '') {
            filteredEmployeesData = employeesData;
        } else {
            filteredEmployeesData = employeesData.filter(emp => 
                emp.name.toLowerCase().includes(searchLower) || 
                (emp.staff_no && emp.staff_no.toString().toLowerCase().includes(searchLower))
            );
        }
        
        renderEmployeeOptions();
    }
    
    // Render employee options in dropdown
    function renderEmployeeOptions() {
        if (!employeeSelectOptions) return;
        
        if (filteredEmployeesData.length === 0) {
            employeeSelectOptions.innerHTML = `
                <div class="px-3 py-2 text-[13px] text-slate-500 text-center">{{ __('pages.no_employees_found') }}</div>
            `;
            return;
        }
        
        let html = '';
        filteredEmployeesData.forEach(emp => {
            const planLabel = emp.plan_names || (emp.plans || []).map(p => p.name).join(' / ') || '';
            html += `
                <button type="button" class="w-full text-left px-4 py-3 hover:bg-slate-50 transition-colors employee-select-option flex items-center gap-3" 
                        data-employee-id="${emp.id}"
                        data-employee-name="${emp.name}"
                        data-employee-staff-no="${emp.staff_no || ''}"
                        data-employee-job-type="${emp.job_type_name || ''}"
                        data-employee-top-size="${emp.top_size || '-'}"
                        data-employee-bottom-size="${emp.bottom_size || '-'}"
                        data-employee-shoe-size="${emp.shoe_size || '-'}"
                        data-employee-inseam="${emp.inseam_cm || '-'}"
                        data-employee-body-type="${emp.body_type || '-'}"
                        data-employee-plans="${planLabel || '未割当'}"
                        data-employee-rentable-limit="${emp.rentable_limit ?? ''}"
                        data-employee-dept="${emp.department_id}"
                        data-employee-job="${emp.job_type_id}"
                        data-employee-gender="${emp.gender || ''}">
                    <div class="w-10 h-10 rounded-full bg-primary-100 flex items-center justify-center flex-shrink-0">
                        <i data-lucide="user" class="w-5 h-5 text-primary-600"></i>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="font-medium text-slate-900 text-[14px]">${emp.name}</p>
                        <p class="text-[12px] text-slate-600">${emp.staff_no || ''}${planLabel ? ' · ' + planLabel : ''}</p>
                    </div>
                </button>
            `;
        });
        employeeSelectOptions.innerHTML = html;
        
        // Re-initialize icons
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
        
        // Add click handlers
        document.querySelectorAll('.employee-select-option').forEach(btn => {
            btn.addEventListener('click', function() {
                selectEmployee(this.dataset);
                closeEmployeeDropdown();
            });
        });
    }
    
    // Load employees for select dropdown
    async function loadEmployeesForSelect(departmentId, searchQuery = '') {
        if (!employeeSelectOptions) return;
        
        try {
            employeeSelectOptions.innerHTML = `
                <div class="px-3 py-2 text-[13px] text-slate-500 text-center">{{ __('pages.loading') }}...</div>
            `;
            
            let url = `{{ route('api.employees.search') }}`;
            const params = new URLSearchParams();
            if (searchQuery) {
                params.append('q', searchQuery);
            }
            if (departmentId) {
                params.append('department_id', departmentId);
            }
            if (params.toString()) {
                url += '?' + params.toString();
            }
            
            const response = await fetch(url);
            const employees = await response.json();
            
            employeesData = employees;
            filteredEmployeesData = employees;
            renderEmployeeOptions();
        } catch (error) {
            console.error('Error loading employees:', error);
            employeeSelectOptions.innerHTML = `
                <div class="px-3 py-2 text-[13px] text-red-500 text-center">{{ __('pages.error_loading_employees') }}</div>
            `;
        }
    }
    
    // Event listeners for searchable select
    if (employeeSelectTrigger) {
        employeeSelectTrigger.addEventListener('click', function(e) {
            e.stopPropagation();
            toggleEmployeeDropdown();
        });
    }
    
    if (employeeSelectSearch) {
        employeeSelectSearch.addEventListener('input', function() {
            filterEmployeesBySearch(this.value);
        });
        
        employeeSelectSearch.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeEmployeeDropdown();
            }
        });
    }
    
    // Close dropdown when clicking outside
    document.addEventListener('click', function(e) {
        if (employeeSelectContainer && !employeeSelectContainer.contains(e.target)) {
            closeEmployeeDropdown();
        }
    });

    // Application category toggle
    document.querySelectorAll('.application-category-option').forEach(option => {
        option.addEventListener('click', function() {
            const category = this.dataset.category;
            applicationModeInput.value = category;
            
            document.querySelectorAll('.application-category-option').forEach(opt => {
                opt.classList.remove('border-primary-500', 'bg-primary-50');
                opt.classList.add('border-slate-300', 'bg-white');
            });
            
            this.classList.remove('border-slate-300', 'bg-white');
            this.classList.add('border-primary-500', 'bg-primary-50');
            
            if (category === 'individual') {
                departmentSelectionField.classList.remove('hidden');
                departmentEmployeesList.classList.add('hidden');
                // Department is always required
                departmentSelect.required = true;
                departmentRequiredIndicator.classList.remove('hidden');
                // Clear employee selection when switching modes
                selectedEmployeeCard.classList.add('hidden');
                selectedEmployeeId.value = '';
                resetEmployeeSelect();
                // Show employee search field only if department is already selected
                if (departmentSelect.value) {
                    employeeSearchField.classList.remove('hidden');
                    loadEmployeesForSelect(departmentSelect.value);
                } else {
                    employeeSearchField.classList.add('hidden');
                }
            } else {
                employeeSearchField.classList.add('hidden');
                departmentSelectionField.classList.remove('hidden');
                departmentSelect.required = true;
                departmentRequiredIndicator.classList.remove('hidden');
                selectedEmployeeCard.classList.add('hidden');
                selectedEmployeeId.value = '';
                resetEmployeeSelect();
                // Load employees when switching to departmental mode if department is already selected
                if (departmentSelect.value) {
                    loadDepartmentEmployees(departmentSelect.value);
                }
            }
        });
    });

    // Department selection handler
    departmentSelect?.addEventListener('change', async function() {
        const departmentId = this.value;
        const mode = applicationModeInput.value;
        
            if (mode === 'individual') {
            // For individual mode, show employee search field and filter by department
            if (departmentId) {
                employeeSearchField.classList.remove('hidden');
                // Load employees for the selected department
                await loadEmployeesForSelect(departmentId);
                // If employee is already selected, filter by employee's department, job type, and gender
                if (selectedEmployeeId.value && selectedEmployeeInfo.gender) {
                    const jobId = selectedEmployeeInfo.job_type_id;
                    const gender = selectedEmployeeInfo.gender;
                    await filterUniformsByEmployee(departmentId, jobId, gender);
                } else {
                    // Filter uniforms by department when department is selected
                    await filterUniformsByDepartment(departmentId);
                }
            } else {
                // Hide employee search if no department selected
                employeeSearchField.classList.add('hidden');
                resetEmployeeSelect();
                
                // If employee is selected but no department, filter by gender only
                if (selectedEmployeeId.value && selectedEmployeeInfo.gender) {
                    filteredUniforms = allUniforms.filter(u => 
                        u.gender === 'unisex' || u.gender === selectedEmployeeInfo.gender || !u.gender
                    );
                } else {
                    // Show all uniforms when no department selected
                    filteredUniforms = allUniforms;
                }
                loadProducts();
            }
            // Clear selected employee if department changes
            selectedEmployeeCard.classList.add('hidden');
            selectedEmployeeId.value = '';
            resetEmployeeSelect();
            selectedEmployeeSizes = {};
            selectedEmployeeInfo = {
                department_id: null,
                job_type_id: null,
                gender: null,
                medical_institution_id: null
            };
            // Reload products to clear size filtering
            loadProducts();
        } else {
            // For departmental mode, show all employees in department
            if (departmentId) {
                await loadDepartmentEmployees(departmentId);
                
                // If employee is already selected, filter by employee's department, job type, and gender
                if (selectedEmployeeId.value && selectedEmployeeInfo.gender) {
                    const jobId = selectedEmployeeInfo.job_type_id;
                    const gender = selectedEmployeeInfo.gender;
                    await filterUniformsByEmployee(departmentId, jobId, gender);
                } else {
                    // Filter uniforms by department
                    await filterUniformsByDepartment(departmentId);
                }
            } else {
                departmentEmployeesList.classList.add('hidden');
                
                // If employee is selected but no department, filter by gender only
                if (selectedEmployeeId.value && selectedEmployeeInfo.gender) {
                    filteredUniforms = allUniforms.filter(u => 
                        u.gender === 'unisex' || u.gender === selectedEmployeeInfo.gender || !u.gender
                    );
                } else {
                    // Show all uniforms when no department selected
                    filteredUniforms = allUniforms;
                }
                loadProducts();
            }
        }
    });

    // Load department employees
    async function loadDepartmentEmployees(departmentId) {
        try {
            const response = await fetch(`{{ route('api.employees_by_department') }}?department_id=${departmentId}`);
            const employees = await response.json();
            
            // Store employees data for filtering
            departmentEmployeesData = employees;
            
            // Reset aggregated data
            departmentEmployeesAggregated = {
                sizes: {
                    top: new Set(),
                    bottom: new Set(),
                    shoe: new Set()
                },
                genders: new Set(),
                job_types: new Set()
            };
            
            // Aggregate all employees' sizes, genders, and job types
            employees.forEach(emp => {
                // Collect sizes
                if (emp.top_size) departmentEmployeesAggregated.sizes.top.add(String(emp.top_size).trim());
                if (emp.bottom_size) departmentEmployeesAggregated.sizes.bottom.add(String(emp.bottom_size).trim());
                if (emp.shoe_size) departmentEmployeesAggregated.sizes.shoe.add(String(emp.shoe_size).trim());
                
                // Collect genders
                if (emp.gender) departmentEmployeesAggregated.genders.add(String(emp.gender).trim());
                
                // Collect job types
                if (emp.positions && emp.positions[0] && emp.positions[0].job_type_id) {
                    departmentEmployeesAggregated.job_types.add(emp.positions[0].job_type_id);
                }
            });
            
            if (employees.length === 0) {
                departmentEmployeesContent.innerHTML = `
                    <div class="text-center py-4">
                        <i data-lucide="users-x" class="w-8 h-8 mx-auto mb-2 text-slate-400"></i>
                        <p class="text-[13px] text-slate-500">{{ __('pages.no_employees_found') }}</p>
                    </div>
                `;
                departmentEmployeesList.classList.remove('hidden');
                if (typeof lucide !== 'undefined') lucide.createIcons();
                
                // Filter products based on empty department
                await filterUniformsByDepartment(departmentId);
                return;
            }

            let html = '';
            employees.forEach(emp => {
                const jobType = emp.positions && emp.positions[0] && emp.positions[0].job_type ? emp.positions[0].job_type.name : '-';
                html += `
                    <div class="flex items-center gap-3 p-3 bg-white rounded-lg border border-slate-200 hover:shadow-sm transition-shadow">
                        <div class="w-10 h-10 rounded-full bg-primary-100 flex items-center justify-center flex-shrink-0">
                            <i data-lucide="user" class="w-5 h-5 text-primary-600"></i>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="font-medium text-slate-900 text-[13px]">${emp.name}</p>
                            <div class="flex items-center gap-3 mt-1">
                                <p class="text-[12px] text-slate-600">
                                    <span class="font-medium">{{ __('pages.employee_id_label') }}:</span> ${emp.staff_no}
                                </p>
                                <p class="text-[12px] text-slate-600">
                                    <span class="font-medium">{{ __('pages.job_type_label') }}:</span> ${jobType}
                                </p>
                            </div>
                        </div>
                    </div>
                `;
            });
            
            html += `
                <div class="mt-3 pt-3 border-t border-slate-200">
                    <p class="text-[12px] text-slate-600 text-center">
                        <i data-lucide="info" class="w-4 h-4 inline-block mr-1"></i>
                        {{ __('pages.total_employees') }}: <span class="font-semibold text-slate-900">${employees.length}</span> {{ __('pages.per_person') }}
                    </p>
                </div>
            `;
            
            departmentEmployeesContent.innerHTML = html;
            departmentEmployeesList.classList.remove('hidden');
            
            if (typeof lucide !== 'undefined') lucide.createIcons();
            
            // Filter products based on all employees' conditions
            await filterUniformsByDepartmentForAllEmployees(departmentId);
        } catch (error) {
            console.error('Department employees fetch error:', error);
            departmentEmployeesContent.innerHTML = `
                <div class="text-center py-4">
                    <i data-lucide="alert-circle" class="w-8 h-8 mx-auto mb-2 text-red-400"></i>
                    <p class="text-[13px] text-red-600">{{ __('pages.error_loading_employees') }}</p>
                </div>
            `;
            departmentEmployeesList.classList.remove('hidden');
            if (typeof lucide !== 'undefined') lucide.createIcons();
        }
    }
    
    // Filter uniforms by department considering ALL employees' sizes, genders, and job types
    async function filterUniformsByDepartmentForAllEmployees(departmentId) {
        if (!departmentId || departmentEmployeesData.length === 0) {
            filteredUniforms = allUniforms;
            loadProducts();
            return;
        }
        
        try {
            // Get uniforms for this department
            const response = await fetch(`{{ route('api.uniforms_by_dept_job') }}?department_id=${departmentId}`);
            
            if (!response.ok) {
                console.error('API response not OK:', response.status, response.statusText);
                filteredUniforms = allUniforms;
                loadProducts();
                return;
            }
            
            const uniforms = await response.json();
            
            if (!Array.isArray(uniforms) || uniforms.length === 0) {
                filteredUniforms = allUniforms;
                loadProducts();
                return;
            }
            
            // Filter uniforms based on ALL employees' conditions:
            // - Product gender must match at least one employee's gender (or be unisex)
            // - Product must be available for at least one employee's job type
            let filtered = uniforms.filter(u => {
                // Check gender: product must be unisex OR match at least one employee's gender
                const genderMatches = !u.gender || 
                                     u.gender === 'unisex' || 
                                     departmentEmployeesAggregated.genders.has(u.gender) ||
                                     departmentEmployeesAggregated.genders.size === 0;
                
                // Check job type: if product has job type restriction, it should match at least one employee's job type
                // (This depends on your API response structure - adjust as needed)
                const jobTypeMatches = true; // Assuming API already filters by department
            
                return genderMatches && jobTypeMatches;
            });
            
            // Group uniforms by base product_code
            const grouped = {};
            filtered.forEach(u => {
                let baseKey = u.product_code || u.id;
                
                // Extract base product code by removing size suffix
                if (u.product_code && u.size) {
                    const sizeSuffixes = ['XS', 'S', 'M', 'L', 'XL', 'XXL'];
                    const sizePattern = new RegExp(`[-_](${sizeSuffixes.join('|')})$`, 'i');
                    baseKey = u.product_code.replace(sizePattern, '').trim();
                }
                
                if (!grouped[baseKey]) {
                    grouped[baseKey] = {
                        ...u,
                        sizes: [],
                        sizes_with_ids: [],
                        available_sizes: [],
                        product_code: baseKey,
                        base_product_code: baseKey
                    };
                }
                
                // Add size information
                const existingSize = grouped[baseKey].sizes_with_ids.find(s => s.size === u.size);
                if (!existingSize) {
                    grouped[baseKey].sizes_with_ids.push({
                        size: u.size,
                        product_id: u.id,
                        stock_quantity: u.stock_quantity || 0,
                        available: (u.stock_quantity || 0) > 0
                    });
                    grouped[baseKey].sizes.push(u.size);
                } else {
                    existingSize.stock_quantity += (u.stock_quantity || 0);
                    existingSize.available = existingSize.stock_quantity > 0;
                }
                
                if ((u.stock_quantity || 0) > 0 && !grouped[baseKey].available_sizes.includes(u.size)) {
                    grouped[baseKey].available_sizes.push(u.size);
                }
            });
            
            // Convert grouped object to array and sort sizes
            filteredUniforms = Object.values(grouped).map(product => {
                // Sort sizes: XS, S, M, L, XL, XXL for top/bottom, numeric for shoes
                if (product.category === 'top' || product.category === 'bottom') {
                    product.sizes.sort((a, b) => {
                        const order = ['XS', 'S', 'M', 'L', 'XL', 'XXL'];
                        const aIndex = order.indexOf(String(a).toUpperCase());
                        const bIndex = order.indexOf(String(b).toUpperCase());
                        return (aIndex === -1 ? 99 : aIndex) - (bIndex === -1 ? 99 : bIndex);
                    });
                    product.sizes_with_ids.sort((a, b) => {
                        const order = ['XS', 'S', 'M', 'L', 'XL', 'XXL'];
                        const aIndex = order.indexOf(String(a.size).toUpperCase());
                        const bIndex = order.indexOf(String(b.size).toUpperCase());
                        return (aIndex === -1 ? 99 : aIndex) - (bIndex === -1 ? 99 : bIndex);
                    });
                } else if (product.category === 'shoe') {
                    product.sizes.sort((a, b) => parseFloat(a) - parseFloat(b));
                    product.sizes_with_ids.sort((a, b) => parseFloat(a.size) - parseFloat(b.size));
                }
                
                return product;
            });
            
            loadProducts();
        } catch (error) {
            console.error('Uniform fetch error:', error);
            filteredUniforms = allUniforms;
            loadProducts();
        }
    }

    // Filter employees by department (for individual mode)
    async function filterEmployeesByDepartment(departmentId) {
        if (!departmentId) {
            employeeSearchResults.classList.add('hidden');
            return;
        }
        await loadEmployeesForSelect(departmentId);
    }

    // Store selected employee sizes
    let selectedEmployeeSizes = {
        top_size: null,
        bottom_size: null,
        shoe_size: null
    };
    
    let selectedEmployeeInfo = {
        department_id: null,
        job_type_id: null,
        gender: null,
        medical_institution_id: null
    };
    
    // Store all department employees data for departmental mode
    let departmentEmployeesData = [];
    let departmentEmployeesAggregated = {
        sizes: {
            top: new Set(),
            bottom: new Set(),
            shoe: new Set()
        },
        genders: new Set(),
        job_types: new Set()
    };

    // Select employee
    function selectEmployee(data) {
        selectedEmployeeId.value = data.employeeId;
        
        // Update select text display
        if (employeeSelectText) {
            employeeSelectText.textContent = `${data.employeeName} (${data.employeeStaffNo || ''})`;
            employeeSelectText.classList.remove('text-slate-500');
            employeeSelectText.classList.add('text-slate-900');
        }
        
        document.getElementById('employee-name').textContent = data.employeeName;
        document.getElementById('employee-staff-no').textContent = data.employeeStaffNo;
        document.getElementById('employee-job-type').textContent = data.employeeJobType || '-';
        document.getElementById('employee-top-size').textContent = data.employeeTopSize || '-';
        document.getElementById('employee-bottom-size').textContent = data.employeeBottomSize || '-';
        document.getElementById('employee-shoe-size').textContent = data.employeeShoeSize || '-';
        const inseamEl = document.getElementById('employee-inseam');
        const bodyTypeEl = document.getElementById('employee-body-type');
        const plansEl = document.getElementById('employee-plans');
        if (inseamEl) inseamEl.textContent = data.employeeInseam || '-';
        if (bodyTypeEl) bodyTypeEl.textContent = data.employeeBodyType || '-';
        if (plansEl) plansEl.textContent = data.employeePlans || '未割当';
        selectedEmployeeCard.classList.remove('hidden');

        // Update plan-based rentable limit for progress bar
        if (data.employeeRentableLimit !== undefined && data.employeeRentableLimit !== '') {
            const parsed = parseInt(data.employeeRentableLimit, 10);
            if (!Number.isNaN(parsed) && parsed > 0) {
                contractLimit = parsed;
                if (contractLimitEl) contractLimitEl.textContent = String(contractLimit);
            }
        }
        
        // Store employee sizes for filtering (handle "-" as null)
        selectedEmployeeSizes = {
            top_size: (data.employeeTopSize && data.employeeTopSize !== '-') ? data.employeeTopSize.trim() : null,
            bottom_size: (data.employeeBottomSize && data.employeeBottomSize !== '-') ? data.employeeBottomSize.trim() : null,
            shoe_size: (data.employeeShoeSize && data.employeeShoeSize !== '-') ? data.employeeShoeSize.trim() : null
        };
        
        // Store employee info (department, gender, org, job type)
        selectedEmployeeInfo = {
            department_id: data.employeeDept ? parseInt(data.employeeDept) : null,
            job_type_id: data.employeeJob ? parseInt(data.employeeJob) : null,
            gender: (data.employeeGender && data.employeeGender !== '') ? data.employeeGender.trim() : null,
            medical_institution_id: null // Will be set from department if needed
        };
        
        // When employee is selected, filter products by employee's department, job type, and gender
        const deptId = departmentSelect ? departmentSelect.value : null;
        const jobId = selectedEmployeeInfo.job_type_id;
        const gender = selectedEmployeeInfo.gender;
        
        if (deptId) {
            // Filter by department, job type, and gender
            filterUniformsByEmployee(deptId, jobId, gender);
        } else {
            // If no department selected, still filter by gender
            if (gender) {
                filteredUniforms = allUniforms.filter(u => 
                    u.gender === 'unisex' || u.gender === gender || !u.gender
                );
            } else {
                filteredUniforms = allUniforms;
            }
            loadProducts();
        }

        if (typeof syncTypeDependentUi === 'function') {
            syncTypeDependentUi();
        }
    }

    // Clear employee
    clearEmployeeBtn?.addEventListener('click', function() {
        selectedEmployeeId.value = '';
        selectedEmployeeCard.classList.add('hidden');
        resetEmployeeSelect();
        selectedEmployeeSizes = {
            top_size: null,
            bottom_size: null,
            shoe_size: null
        };
        selectedEmployeeInfo = {
            department_id: null,
            job_type_id: null,
            gender: null,
            medical_institution_id: null
        };
        filteredUniforms = [];
        loadProducts();
    });

    // Filter uniforms by employee
    async function filterUniformsByEmployee(deptId, jobId, gender) {
        const employeeId = document.getElementById('selected-employee-id')?.value
            || document.querySelector('[name="employee_id"]')?.value;

        // Prefer plan-linked products for the selected employee
        if (employeeId) {
            try {
                let apiUrl = `{{ route('api.uniforms_by_dept_job') }}?employee_id=${employeeId}`;
                if (gender) {
                    apiUrl += `&gender=${gender}`;
                }
                const response = await fetch(apiUrl);
                const uniforms = await response.json();

                if (!Array.isArray(uniforms) || uniforms.length === 0) {
                    filteredUniforms = [];
                    const plansEl = document.getElementById('employee-plans');
                    const planText = plansEl ? plansEl.textContent : '';
                    let emptyMsg = '{{ __('pages.no_products_available') }}';
                    if (planText && planText.includes('開始前')) {
                        emptyMsg = '契約プランは割当済みですが、顧客契約プランの開始日が未来のため、本日は申請対象商品がありません。開始日を確認してください。';
                    } else if (!planText || planText === '未割当' || planText === '-') {
                        emptyMsg = '契約プラン未割当のため申請できる商品がありません。職員にプランを割り当ててください。';
                    }
                    productsContainer.innerHTML = `
                        <div class="text-center py-12 text-slate-500">
                            <i data-lucide="shopping-bag" class="w-12 h-12 mx-auto mb-3 text-slate-300"></i>
                            <p class="text-[14px]">${emptyMsg}</p>
                            <p class="text-[12px] text-slate-400 mt-2">※ 商品カテゴリ（上衣/下衣/靴）タブも切り替えて確認してください。</p>
                        </div>
                    `;
                    if (typeof lucide !== 'undefined') lucide.createIcons();
                    return;
                }

                const grouped = {};
                uniforms.forEach(u => {
                    let baseKey = u.product_code || u.id;
                    if (u.product_code && u.size) {
                        const lastDashPos = u.product_code.lastIndexOf('-');
                        if (lastDashPos !== -1) {
                            const suffix = u.product_code.substring(lastDashPos + 1);
                            if (suffix === u.size || ['S', 'M', 'L', 'XL', 'XXL'].includes(suffix) || !isNaN(suffix)) {
                                baseKey = u.product_code.substring(0, lastDashPos);
                            }
                        }
                    }
                    if (!grouped[baseKey]) {
                        grouped[baseKey] = [];
                    }
                    grouped[baseKey].push(u);
                });

                filteredUniforms = Object.keys(grouped).map(key => {
                    const group = grouped[key];
                    const first = group[0];
                    return {
                        id: first.id,
                        name: first.name,
                        product_code: key,
                        category: first.category,
                        gender: first.gender,
                        sizes_with_ids: group.map(g => ({
                            size: g.size,
                            product_id: g.id,
                            stock_quantity: g.stock_quantity ?? 0,
                            available: (g.stock_quantity ?? 0) > 0,
                        })),
                        available_sizes: group.map(g => g.size),
                        stock_quantity: group.reduce((s, g) => s + (g.stock_quantity || 0), 0),
                        image_path: first.image_path || '',
                    };
                });
                loadProducts();
                return;
            } catch (error) {
                console.error('Plan uniform fetch error:', error);
            }
        }

        // If no department, show all uniforms filtered by gender
        if (!deptId) {
            filteredUniforms = allUniforms.filter(u => 
                !gender || u.gender === 'unisex' || u.gender === gender || !u.gender
            );
            loadProducts();
            return;
        }

        try {
            // Build API URL - if jobId is missing, API will return department-based recommendations
            let apiUrl = `{{ route('api.uniforms_by_dept_job') }}?department_id=${deptId}`;
            if (jobId) {
                apiUrl += `&job_type_id=${jobId}`;
            }
            if (gender) {
                apiUrl += `&gender=${gender}`;
            }
            
            const response = await fetch(apiUrl);
            const uniforms = await response.json();
            
            // Group uniforms by base product_code (extract base code by removing size suffix)
            const grouped = {};
            uniforms.forEach(u => {
                // Extract base product code by removing size suffix
                let baseKey = u.product_code || u.id;
                if (u.product_code && u.size) {
                    const lastDashPos = u.product_code.lastIndexOf('-');
                    if (lastDashPos !== -1) {
                        const suffix = u.product_code.substring(lastDashPos + 1);
                        // Check if suffix matches the size (S, M, L, XL, XXL, or numeric)
                        if (suffix === u.size || ['S', 'M', 'L', 'XL', 'XXL'].includes(suffix) || !isNaN(suffix)) {
                            baseKey = u.product_code.substring(0, lastDashPos);
                        }
                    }
                }
                
                if (!grouped[baseKey]) {
                    grouped[baseKey] = {
                        id: u.id,
                        name: u.name,
                        product_code: baseKey, // Use base product code
                        category: u.category,
                        gender: u.gender,
                        sizes: [],
                        sizes_with_ids: [],
                        available_sizes: [],
                        stock_quantity: 0,
                        image_path: u.image_path || ''
                    };
                }
                if (u.size) {
                    // Add size with product ID
                    const existingSize = grouped[baseKey].sizes_with_ids.find(s => s.size === u.size);
                    if (!existingSize) {
                        grouped[baseKey].sizes_with_ids.push({
                            size: u.size,
                            product_id: u.id,
                            stock_quantity: u.stock_quantity || 0,
                            available: (u.stock_quantity || 0) > 0
                        });
                        grouped[baseKey].sizes.push(u.size);
                    } else {
                        // Update stock if this product has more stock
                        existingSize.stock_quantity += (u.stock_quantity || 0);
                        existingSize.available = existingSize.stock_quantity > 0;
                    }
                    // Add to available_sizes if in stock
                    if ((u.stock_quantity || 0) > 0 && !grouped[baseKey].available_sizes.includes(u.size)) {
                        grouped[baseKey].available_sizes.push(u.size);
                    }
                }
                grouped[baseKey].stock_quantity += (u.stock_quantity || 0);
            });
            
            // Convert grouped object to array and sort sizes
            filteredUniforms = Object.values(grouped).map(u => ({
                ...u,
                sizes: u.sizes.sort((a, b) => {
                    // Sort letter sizes (S, M, L, XL, XXL)
                    const sizeOrder = { 'S': 1, 'M': 2, 'L': 3, 'XL': 4, 'XXL': 5 };
                    if (sizeOrder[a] && sizeOrder[b]) {
                        return sizeOrder[a] - sizeOrder[b];
                    }
                    // Sort numeric sizes (for shoes)
                    const numA = parseFloat(a);
                    const numB = parseFloat(b);
                    if (!isNaN(numA) && !isNaN(numB)) {
                        return numA - numB;
                    }
                    return a.localeCompare(b);
                }),
                sizes_with_ids: u.sizes_with_ids.sort((a, b) => {
                    const sizeOrder = { 'S': 1, 'M': 2, 'L': 3, 'XL': 4, 'XXL': 5 };
                    if (sizeOrder[a.size] && sizeOrder[b.size]) {
                        return sizeOrder[a.size] - sizeOrder[b.size];
                    }
                    if (u.category === 'shoe') {
                        return parseFloat(a.size) - parseFloat(b.size);
                    }
                    return a.size.localeCompare(b.size);
                }),
                available_sizes: u.available_sizes.sort((a, b) => {
                    const sizeOrder = { 'S': 1, 'M': 2, 'L': 3, 'XL': 4, 'XXL': 5 };
                    if (sizeOrder[a] && sizeOrder[b]) {
                        return sizeOrder[a] - sizeOrder[b];
                    }
                    const numA = parseFloat(a);
                    const numB = parseFloat(b);
                    if (!isNaN(numA) && !isNaN(numB)) {
                        return numA - numB;
                    }
                    return a.localeCompare(b);
                })
            }));
            
            // Additional gender filter if needed
            if (gender) {
                filteredUniforms = filteredUniforms.filter(u => 
                    u.gender === 'unisex' || u.gender === gender || !u.gender
                );
            }
            
            loadProducts();
        } catch (error) {
            console.error('Uniform fetch error:', error);
            filteredUniforms = allUniforms;
            loadProducts();
        }
    }
    
    // Filter uniforms by department (for departmental mode and individual mode)
    async function filterUniformsByDepartment(departmentId) {
        if (!departmentId) {
            filteredUniforms = allUniforms;
            loadProducts();
            return;
        }
        
        try {
            // Get uniforms for this department
            const response = await fetch(`{{ route('api.uniforms_by_dept_job') }}?department_id=${departmentId}`);
            
            if (!response.ok) {
                console.error('API response not OK:', response.status, response.statusText);
                filteredUniforms = allUniforms;
                loadProducts();
                return;
            }
            
            const uniforms = await response.json();
            
            // Check if uniforms is an array
            if (!Array.isArray(uniforms)) {
                console.error('API did not return an array:', uniforms);
                filteredUniforms = allUniforms;
                loadProducts();
                return;
            }
            
            // If no uniforms returned, show all uniforms
            if (uniforms.length === 0) {
                console.warn('No uniforms returned from API, showing all uniforms');
                filteredUniforms = allUniforms;
                loadProducts();
                return;
            }
            
            // Group uniforms by base product_code (extract base code by removing size suffix)
            // Products are stored as BASE_CODE-SIZE (e.g., "TOP-F-1-M" where "TOP-F-1" is base, "M" is size)
            const grouped = {};
            uniforms.forEach(u => {
                // Extract base product code by removing size suffix
                let baseKey = u.product_code || u.id;
                if (u.product_code && u.size) {
                    const lastDashPos = u.product_code.lastIndexOf('-');
                    if (lastDashPos !== -1) {
                        const suffix = u.product_code.substring(lastDashPos + 1);
                        // Check if suffix matches the size (S, M, L, XL, XXL, or numeric)
                        if (suffix === u.size || ['S', 'M', 'L', 'XL', 'XXL'].includes(suffix) || !isNaN(suffix)) {
                            baseKey = u.product_code.substring(0, lastDashPos);
                        }
                    }
                }
                
                if (!grouped[baseKey]) {
                    grouped[baseKey] = {
                        id: u.id,
                        name: u.name,
                        product_code: baseKey, // Use base product code
                        category: u.category,
                        gender: u.gender,
                        sizes: [],
                        sizes_with_ids: [],
                        stock_quantity: 0,
                        image_path: u.image_path || ''
                    };
                }
                if (u.size) {
                    // Add size with product ID
                    const existingSize = grouped[baseKey].sizes_with_ids.find(s => s.size === u.size);
                    if (!existingSize) {
                        grouped[baseKey].sizes_with_ids.push({
                            size: u.size,
                            product_id: u.id,
                            stock_quantity: u.stock_quantity || 0,
                            available: (u.stock_quantity || 0) > 0
                        });
                        grouped[baseKey].sizes.push(u.size);
                    } else {
                        // Update stock if this product has more stock
                        existingSize.stock_quantity += (u.stock_quantity || 0);
                        existingSize.available = existingSize.stock_quantity > 0;
                    }
                }
                grouped[baseKey].stock_quantity += (u.stock_quantity || 0);
            });
            
            filteredUniforms = Object.values(grouped).map(u => ({
                ...u,
                sizes: u.sizes.sort((a, b) => {
                    const sizeOrder = { 'S': 1, 'M': 2, 'L': 3, 'XL': 4, 'XXL': 5 };
                    if (sizeOrder[a] && sizeOrder[b]) return sizeOrder[a] - sizeOrder[b];
                    return a.localeCompare(b);
                }),
                sizes_with_ids: u.sizes_with_ids.sort((a, b) => {
                    const sizeOrder = { 'S': 1, 'M': 2, 'L': 3, 'XL': 4, 'XXL': 5 };
                    if (sizeOrder[a.size] && sizeOrder[b.size]) return sizeOrder[a.size] - sizeOrder[b.size];
                    if (u.category === 'shoe') return parseFloat(a.size) - parseFloat(b.size);
                    return a.size.localeCompare(b.size);
                })
            }));
            
            loadProducts();
        } catch (error) {
            console.error('Department filter error:', error);
            filteredUniforms = allUniforms;
            loadProducts();
        }
    }

    // Category tabs
    categoryTabs.forEach(tab => {
        tab.addEventListener('click', function() {
            // Remove active state from all tabs
            categoryTabs.forEach(t => {
                t.classList.remove('active', 'border-primary-600', 'text-primary-600', 'bg-primary-50');
                t.classList.add('text-slate-600', 'border-transparent');
            });
            
            // Add active state to clicked tab
            this.classList.add('active', 'border-primary-600', 'text-primary-600', 'bg-primary-50');
            this.classList.remove('text-slate-600', 'border-transparent');
            
            currentCategory = this.dataset.category;
            loadProducts();
        });
    });

    // Initialize active category tab styling on page load
    function initializeActiveTab() {
        const activeTab = document.querySelector('.category-tab.active');
        if (activeTab) {
            activeTab.classList.add('border-primary-600', 'text-primary-600', 'bg-primary-50');
            activeTab.classList.remove('text-slate-600', 'border-transparent');
        } else {
            // If no active tab found, set first tab as active
            const firstTab = categoryTabs[0];
            if (firstTab) {
                firstTab.classList.add('active', 'border-primary-600', 'text-primary-600', 'bg-primary-50');
                firstTab.classList.remove('text-slate-600', 'border-transparent');
                currentCategory = firstTab.dataset.category;
            }
        }
    }
    
    // Initialize on page load
    initializeActiveTab();

    // Load products
    function loadProducts() {
        const categoryMap = {
            'top': 'top',
            'bottom': 'bottom',
            'shoe': 'shoe',
            'other': 'other'
        };
        
        // Get products to display (filtered or all)
        let productsToShow = filteredUniforms.length > 0 ? filteredUniforms : allUniforms;
        
        // Apply gender filter if employee is selected (individual mode) or departmental mode
        const isDepartmentalMode = applicationModeInput.value === 'departmental';
        
        if (isDepartmentalMode && departmentEmployeesAggregated.genders.size > 0) {
            // Departmental mode: filter by all employees' genders
            productsToShow = productsToShow.filter(u => {
                // Product must be unisex OR match at least one employee's gender OR have no gender specified
                return !u.gender || 
                       u.gender === 'unisex' || 
                       departmentEmployeesAggregated.genders.has(u.gender) ||
                       departmentEmployeesAggregated.genders.size === 0;
            });
        } else if (selectedEmployeeId.value && selectedEmployeeInfo.gender) {
            // Individual mode: filter by selected employee's gender
            if (filteredUniforms.length === 0) {
                productsToShow = allUniforms.filter(u => 
                    u.gender === 'unisex' || u.gender === selectedEmployeeInfo.gender || !u.gender
                );
            } else {
                productsToShow = productsToShow.filter(u => 
                    u.gender === 'unisex' || u.gender === selectedEmployeeInfo.gender || !u.gender
                );
            }
        }
        
        // Filter by category
        productsToShow = productsToShow.filter(u => {
            if (currentCategory === 'other') {
                return !['top', 'bottom', 'shoe'].includes(u.category);
            }
            return u.category === categoryMap[currentCategory];
        });

        if (productsToShow.length === 0) {
            productsContainer.innerHTML = `
                <div class="text-center py-12 text-slate-500">
                    <i data-lucide="shopping-bag" class="w-12 h-12 mx-auto mb-3 text-slate-300"></i>
                    <p class="text-[14px]">{{ __('pages.no_products_available') }}</p>
                </div>
            `;
            if (typeof lucide !== 'undefined') lucide.createIcons();
            return;
        }

        // E-shop card grid layout
        let html = '<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4 px-4">';
        productsToShow.forEach(product => {
            // Use product_code as base key, size will be selected separately
            const productKey = product.product_code || product.id;
            // Check if any size of this product is selected
            const hasSelectedSize = Array.from(selectedItems.keys()).some(key => 
                key.startsWith(`${productKey}_`)
            );
            const isSelected = hasSelectedSize;
            
            // Filter sizes based on selected employee's registered sizes
            let availableSizes = product.sizes_with_ids || (product.sizes || []).map(size => ({
                size: size,
                product_id: null, // Will need to find it
                stock_quantity: 0,
                available: (product.available_sizes || []).includes(size)
            }));
            
            // Check if we're in departmental mode
            const isDepartmentalMode = applicationModeInput.value === 'departmental';
            
            // If employee is selected (individual mode) or departmental mode, mark sizes that match employees' registered sizes
            // We'll show all sizes but disable/highlight non-matching ones
            const sizesData = availableSizes.map(sizeData => {
                let matchesEmployeeSize = false;
                let employeeCountForSize = 0; // Count of employees who need this size
                
                if (isDepartmentalMode && departmentEmployeesData.length > 0) {
                    // Departmental mode: check if size matches ANY employee's size
                    const sizeStr = String(sizeData.size || sizeData).trim();
                    
                    departmentEmployeesData.forEach(emp => {
                        if (product.category === 'top' && emp.top_size) {
                            const employeeSize = String(emp.top_size).trim();
                            if (sizeStr === employeeSize || sizeStr.toLowerCase() === employeeSize.toLowerCase()) {
                                matchesEmployeeSize = true;
                                employeeCountForSize++;
                            }
                        } else if (product.category === 'bottom' && emp.bottom_size) {
                            const employeeSize = String(emp.bottom_size).trim();
                            if (sizeStr === employeeSize || sizeStr.toLowerCase() === employeeSize.toLowerCase()) {
                                matchesEmployeeSize = true;
                                employeeCountForSize++;
                            }
                        } else if (product.category === 'shoe' && emp.shoe_size) {
                            const employeeShoeSize = parseFloat(emp.shoe_size);
                            const sizeNum = parseFloat(sizeStr);
                            if (!isNaN(sizeNum) && !isNaN(employeeShoeSize)) {
                                if (Math.abs(sizeNum - employeeShoeSize) <= 0.5) {
                                    matchesEmployeeSize = true;
                                    employeeCountForSize++;
                                }
                            } else {
                                const empSizeStr = String(emp.shoe_size).trim();
                                if (sizeStr === empSizeStr || sizeStr.toLowerCase() === empSizeStr.toLowerCase()) {
                                    matchesEmployeeSize = true;
                                    employeeCountForSize++;
                                }
                            }
                        }
                    });
                    
                    // If no employees have sizes registered, allow all sizes
                    const hasAnySizes = departmentEmployeesData.some(emp => 
                        (product.category === 'top' && emp.top_size) ||
                        (product.category === 'bottom' && emp.bottom_size) ||
                        (product.category === 'shoe' && emp.shoe_size)
                    );
                    if (!hasAnySizes) {
                        matchesEmployeeSize = true;
                        employeeCountForSize = departmentEmployeesData.length;
                    }
                } else if (selectedEmployeeId.value) {
                    // Individual mode: check single employee's size
                    const sizeStr = String(sizeData.size || sizeData).trim();
                    
                    if (product.category === 'top' && selectedEmployeeSizes.top_size) {
                        const employeeSize = String(selectedEmployeeSizes.top_size).trim();
                        matchesEmployeeSize = sizeStr === employeeSize || sizeStr.toLowerCase() === employeeSize.toLowerCase();
                        if (matchesEmployeeSize) employeeCountForSize = 1;
                    } else if (product.category === 'bottom' && selectedEmployeeSizes.bottom_size) {
                        const employeeSize = String(selectedEmployeeSizes.bottom_size).trim();
                        matchesEmployeeSize = sizeStr === employeeSize || sizeStr.toLowerCase() === employeeSize.toLowerCase();
                        if (matchesEmployeeSize) employeeCountForSize = 1;
                    } else if (product.category === 'shoe' && selectedEmployeeSizes.shoe_size) {
                        const employeeShoeSize = parseFloat(selectedEmployeeSizes.shoe_size);
                        const sizeNum = parseFloat(sizeStr);
                        if (!isNaN(sizeNum) && !isNaN(employeeShoeSize)) {
                            matchesEmployeeSize = Math.abs(sizeNum - employeeShoeSize) <= 0.5;
                            if (matchesEmployeeSize) employeeCountForSize = 1;
                        } else {
                            matchesEmployeeSize = sizeStr === String(selectedEmployeeSizes.shoe_size).trim() || 
                                                  sizeStr.toLowerCase() === String(selectedEmployeeSizes.shoe_size).trim().toLowerCase();
                            if (matchesEmployeeSize) employeeCountForSize = 1;
                        }
                    } else if (!selectedEmployeeSizes.top_size && !selectedEmployeeSizes.bottom_size && !selectedEmployeeSizes.shoe_size) {
                        matchesEmployeeSize = true;
                        employeeCountForSize = 1;
                    }
                } else {
                    // No employee selected and not departmental mode, allow all sizes
                    matchesEmployeeSize = true;
                }
                
                return {
                    ...sizeData,
                    matchesEmployeeSize: matchesEmployeeSize,
                    disabled: (selectedEmployeeId.value || isDepartmentalMode) && !matchesEmployeeSize,
                    employeeCount: employeeCountForSize // Number of employees who need this size
                };
            });
            
            html += `
                <div class="group border border-slate-200 rounded-xl overflow-hidden hover:shadow-lg transition-all duration-300 ${isSelected ? 'ring-2 ring-primary-500 shadow-md' : 'hover:border-primary-300'}">
                    <div class="relative h-48 bg-gradient-to-br from-slate-50 to-slate-100 overflow-hidden">
                        ${product.image_path ? 
                            `<img src="${product.image_path}" alt="${product.name}" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">` :
                            `<div class="w-full h-full flex items-center justify-center">
                                <i data-lucide="shirt" class="w-16 h-16 text-slate-300"></i>
                            </div>`
                        }
                        ${isSelected ? `
                            <div class="absolute top-2 right-2 bg-primary-600 text-white rounded-full p-1.5 shadow-lg">
                                <i data-lucide="check" class="w-4 h-4"></i>
                            </div>
                        ` : ''}
                    </div>
                    
                    <div class="p-3 space-y-4">
                        <div>
                            <h3 class="font-semibold text-slate-900 text-[16px] mb-1.5 line-clamp-2 leading-tight">${product.name}</h3>
                            <p class="text-[12px] text-slate-500 font-mono">${product.product_code}</p>
                        </div>
                        
                        ${/* Size Selection - Multiple sizes with checkboxes for tops/bottoms, inputs for shoes */''}
                        <div>
                            <label class="block text-[13px] font-semibold text-slate-700 mb-2 flex items-center gap-1.5">
                                <i data-lucide="ruler" class="w-4 h-4 text-primary-600"></i>
                                {{ __('pages.size') }}
                            </label>
                            ${product.category === 'shoe' ? 
                                /* Shoe size - Multiple number inputs in a grid */
                                `<div class="space-y-2 max-h-32 overflow-y-auto">
                                    ${sizesData.map(sizeData => {
                                        const itemKey = `${productKey}_${sizeData.size}`;
                                        
                                        // Check all conditions for auto-check:
                                        // 1. Department is selected
                                        // 2. Employee is selected
                                        // 3. Employee's department matches selected department
                                        // 4. Product gender matches employee gender (or is unisex)
                                        // 5. Size matches employee's registered size
                                        // 6. Product is available (has stock)
                                        // 7. Item is not already selected
                                        // 8. Contract limit allows adding this item
                                        const departmentSelected = departmentSelect && departmentSelect.value;
                                        const departmentMatches = !selectedEmployeeInfo.department_id || 
                                                                  !departmentSelected || 
                                                                  parseInt(departmentSelected) === selectedEmployeeInfo.department_id;
                                        const genderMatches = !selectedEmployeeInfo.gender || 
                                                              !product.gender || 
                                                              product.gender === 'unisex' || 
                                                              product.gender === selectedEmployeeInfo.gender;
                                        const sizeMatches = sizeData.matchesEmployeeSize;
                                        const isAvailable = sizeData.available && sizeData.stock_quantity > 0;
                                        const notAlreadySelected = !selectedItems.has(itemKey);
                                        
                                        // Check contract limit before auto-checking
                                        let currentTotal = 0;
                                        selectedItems.forEach(item => {
                                            currentTotal += item.quantity;
                                        });
                                        const wouldExceedLimit = (currentTotal + 1) > contractLimit;
                                        
                                        const shouldAutoCheck = departmentSelected && 
                                                                 selectedEmployeeId.value && 
                                                                 departmentMatches && 
                                                                 genderMatches && 
                                                                 sizeMatches && 
                                                                 isAvailable && 
                                                                 notAlreadySelected && 
                                                                 !wouldExceedLimit;
                                        
                                        const selectedItem = selectedItems.get(itemKey) || { quantity: shouldAutoCheck ? 1 : 0, size: sizeData.size };
                                        
                                        // If auto-checking, add to selectedItems
                                        if (shouldAutoCheck && sizeData.product_id) {
                                            selectedItems.set(itemKey, {
                                                product_id: sizeData.product_id || product.id,
                                                product_name: product.name,
                                                product_code: product.product_code || product.id,
                                                product_image: product.image_path,
                                                size: sizeData.size,
                                                quantity: 1
                                            });
                                        }
                                        
                                        return `
                                            <div class="flex items-center gap-2 p-2 rounded-lg border transition-all ${selectedItem.quantity > 0 || shouldAutoCheck ? 'bg-primary-50 border-primary-400' : sizeData.disabled ? 'border-slate-200 bg-slate-50 opacity-50' : sizeData.matchesEmployeeSize ? 'border-slate-200 hover:border-primary-300' : 'border-slate-200 bg-slate-50 opacity-50'}">
                                                <label class="flex-1 text-[13px] font-medium ${sizeData.disabled ? 'text-slate-400' : sizeData.matchesEmployeeSize ? 'text-slate-700' : 'text-slate-400'} flex items-center justify-between">
                                                    <span>
                                                        ${sizeData.size}
                                                        ${sizeData.disabled ? ' ({{ __('pages.size_not_match') }})' : ''}
                                                        ${isDepartmentalMode && sizeData.employeeCount > 0 ? ` <span class="text-[11px] text-primary-600 font-semibold">(${sizeData.employeeCount}人)</span>` : ''}
                                                    </span>
                                                    ${sizeData.product_id ? `<span class="text-[11px] text-slate-500 ml-2">在庫: ${sizeData.stock_quantity || 0}件</span>` : ''}
                                                </label>
                                                <div class="flex items-center gap-1">
                                                    <button type="button" class="size-quantity-btn minus w-8 h-8 rounded-lg border border-slate-300 bg-white hover:bg-slate-50 flex items-center justify-center disabled:opacity-40 disabled:cursor-not-allowed transition-all" 
                                                            data-product-key="${productKey}" 
                                                            data-size="${sizeData.size}" 
                                                            data-product-id="${sizeData.product_id || product.id}"
                                                            data-product-name="${product.name}"
                                                            data-product-code="${product.product_code || product.id}"
                                                            data-product-image="${product.image_path}"
                                                            ${selectedItem.quantity <= 0 && !shouldAutoCheck || sizeData.disabled ? 'disabled' : ''}>
                                                        <i data-lucide="minus" class="w-3 h-3 pointer-events-none"></i>
                                                    </button>
                                                    <input type="number" min="0" step="0.5" value="${selectedItem.quantity || (shouldAutoCheck ? 1 : 0)}" 
                                                           class="size-quantity-input w-12 px-1 py-1 text-center text-[12px] border border-slate-300 rounded-lg font-medium bg-white text-slate-900 ${sizeData.disabled ? 'opacity-50' : ''}" 
                                                           data-product-key="${productKey}" 
                                                           data-size="${sizeData.size}"
                                                           ${sizeData.disabled ? 'readonly' : ''}
                                                           onchange="updateShoeQuantity('${productKey}', '${sizeData.size}', this.value, '${sizeData.product_id || product.id}', '${product.name}', '${product.product_code || product.id}', '${product.image_path}')">
                                                    <button type="button" class="size-quantity-btn plus w-8 h-8 rounded-lg border border-slate-300 bg-white hover:bg-slate-50 flex items-center justify-center transition-all ${sizeData.disabled ? 'opacity-50 cursor-not-allowed' : ''}" 
                                                            data-product-key="${productKey}" 
                                                            data-size="${sizeData.size}"
                                                            data-product-id="${sizeData.product_id || product.id}"
                                                            data-product-name="${product.name}"
                                                            data-product-code="${product.product_code || product.id}"
                                                            data-product-image="${product.image_path}"
                                                            ${sizeData.disabled ? 'disabled' : ''}>
                                                        <i data-lucide="plus" class="w-3 h-3 pointer-events-none"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        `;
                                    }).join('')}
                                </div>` :
                                /* Top/Bottom size - Checkboxes with quantity controls */
                                `<div class="space-y-2 max-h-32 overflow-y-auto">
                                    ${sizesData.map(sizeData => {
                                        const itemKey = `${productKey}_${sizeData.size}`;
                                        
                                        // Check all conditions for auto-check (same logic as for shoes)
                                        const departmentSelected = departmentSelect && departmentSelect.value;
                                        
                                        let shouldAutoCheck = false;
                                        let defaultQuantity = 0;
                                        
                                        if (isDepartmentalMode && departmentSelected) {
                                            // Departmental mode: auto-check based on employee count for this size
                                            const genderMatches = !product.gender || 
                                                                  product.gender === 'unisex' || 
                                                                  departmentEmployeesAggregated.genders.has(product.gender) ||
                                                                  departmentEmployeesAggregated.genders.size === 0;
                                            const sizeMatches = sizeData.matchesEmployeeSize;
                                            const isAvailable = sizeData.available && sizeData.stock_quantity > 0;
                                            const notAlreadySelected = !selectedItems.has(itemKey);
                                            
                                            defaultQuantity = sizeData.employeeCount || 0;
                                            
                                            let currentTotal = 0;
                                            selectedItems.forEach(item => {
                                                currentTotal += item.quantity;
                                            });
                                            const wouldExceedLimit = (currentTotal + defaultQuantity) > contractLimit;
                                            
                                            shouldAutoCheck = genderMatches && 
                                                             sizeMatches && 
                                                             isAvailable && 
                                                             notAlreadySelected && 
                                                             !wouldExceedLimit &&
                                                             defaultQuantity > 0;
                                        } else if (selectedEmployeeId.value && departmentSelected) {
                                            // Individual mode
                                            const departmentMatches = !selectedEmployeeInfo.department_id || 
                                                                      !departmentSelected || 
                                                                      parseInt(departmentSelected) === selectedEmployeeInfo.department_id;
                                            const genderMatches = !selectedEmployeeInfo.gender || 
                                                                  !product.gender || 
                                                                  product.gender === 'unisex' || 
                                                                  product.gender === selectedEmployeeInfo.gender;
                                            const sizeMatches = sizeData.matchesEmployeeSize;
                                            const isAvailable = sizeData.available && sizeData.stock_quantity > 0;
                                            const notAlreadySelected = !selectedItems.has(itemKey);
                                            
                                            defaultQuantity = 1;
                                            
                                            let currentTotal = 0;
                                            selectedItems.forEach(item => {
                                                currentTotal += item.quantity;
                                            });
                                            const wouldExceedLimit = (currentTotal + 1) > contractLimit;
                                            
                                            shouldAutoCheck = departmentMatches && 
                                                             genderMatches && 
                                                             sizeMatches && 
                                                             isAvailable && 
                                                             notAlreadySelected && 
                                                             !wouldExceedLimit;
                                        }
                                        
                                        const selectedItem = selectedItems.get(itemKey) || { quantity: shouldAutoCheck ? defaultQuantity : 0, size: sizeData.size };
                                        
                                        // If auto-checking, add to selectedItems
                                        if (shouldAutoCheck && sizeData.product_id) {
                                            selectedItems.set(itemKey, {
                                                product_id: sizeData.product_id || product.id,
                                                product_name: product.name,
                                                product_code: product.product_code || product.id,
                                                product_image: product.image_path,
                                                size: sizeData.size,
                                                quantity: defaultQuantity
                                            });
                                        }
                                        
                                        return `
                                            <div class="flex items-center gap-2 p-2 rounded-lg border transition-all ${selectedItem.quantity > 0 || shouldAutoCheck ? 'bg-primary-50 border-primary-400' : sizeData.disabled ? 'border-slate-200 bg-slate-50 opacity-50' : sizeData.matchesEmployeeSize ? 'border-slate-200 hover:border-primary-300' : 'border-slate-200 bg-slate-50 opacity-50'} ${!sizeData.available ? 'opacity-60' : ''}">
                                                <input type="checkbox" 
                                                       class="size-checkbox w-4 h-4 text-primary-600 border-slate-300 rounded focus:ring-primary-500" 
                                                       data-product-key="${productKey}" 
                                                       data-size="${sizeData.size}"
                                                       data-product-id="${sizeData.product_id || product.id}"
                                                       data-product-name="${product.name}"
                                                       data-product-code="${product.product_code || product.id}"
                                                       data-product-image="${product.image_path}"
                                                       ${selectedItem.quantity > 0 || shouldAutoCheck ? 'checked' : ''}
                                                       ${!sizeData.available || sizeData.disabled ? 'disabled' : ''}>
                                                <label class="flex-1 text-[13px] font-medium ${!sizeData.available || sizeData.disabled ? 'text-slate-400' : sizeData.matchesEmployeeSize ? 'text-slate-700' : 'text-slate-400'} ${sizeData.disabled ? '' : 'cursor-pointer'} flex items-center justify-between">
                                                    <span>
                                                        ${sizeData.size}
                                                        ${!sizeData.available ? ' ({{ __('pages.out_of_stock') }})' : sizeData.disabled ? ' ({{ __('pages.size_not_match') }})' : ''}
                                                        ${isDepartmentalMode && sizeData.employeeCount > 0 ? ` <span class="text-[11px] text-primary-600 font-semibold">(${sizeData.employeeCount}人)</span>` : ''}
                                                    </span>
                                                    ${sizeData.product_id ? `<span class="text-[11px] text-slate-500 ml-2">在庫: ${sizeData.stock_quantity || 0}件</span>` : ''}
                                                </label>
                                                <div class="flex items-center gap-1 ${selectedItem.quantity > 0 || shouldAutoCheck ? '' : 'hidden'} size-quantity-control">
                                                    <button type="button" class="size-quantity-btn minus w-8 h-8 rounded-lg border border-slate-300 bg-white hover:bg-slate-50 flex items-center justify-center disabled:opacity-40 disabled:cursor-not-allowed transition-all" 
                                                            data-product-key="${productKey}" 
                                                            data-size="${sizeData.size}"
                                                            data-product-id="${sizeData.product_id || product.id}"
                                                            data-product-name="${product.name}"
                                                            data-product-code="${product.product_code || product.id}"
                                                            data-product-image="${product.image_path}"
                                                            ${selectedItem.quantity <= 0 && !shouldAutoCheck ? 'disabled' : ''}>
                                                        <i data-lucide="minus" class="w-3 h-3 pointer-events-none"></i>
                                                    </button>
                                                    <input type="number" min="0" value="${selectedItem.quantity || (shouldAutoCheck ? defaultQuantity : 0)}" 
                                                           class="size-quantity-input w-12 px-1 py-1 text-center text-[12px] border border-slate-300 rounded-lg font-medium bg-white text-slate-900" 
                                                           data-product-key="${productKey}" 
                                                           data-size="${sizeData.size}"
                                                           readonly>
                                                    <button type="button" class="size-quantity-btn plus w-8 h-8 rounded-lg border border-slate-300 bg-white hover:bg-slate-50 flex items-center justify-center transition-all" 
                                                            data-product-key="${productKey}" 
                                                            data-size="${sizeData.size}"
                                                            data-product-id="${sizeData.product_id || product.id}"
                                                            data-product-name="${product.name}"
                                                            data-product-code="${product.product_code || product.id}"
                                                            data-product-image="${product.image_path}">
                                                        <i data-lucide="plus" class="w-3 h-3 pointer-events-none"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        `;
                                    }).join('')}
                                </div>`
                            }
                        </div>
                        
                        ${/* Stock Info */''}
                        <div class="flex items-center justify-between pt-3 border-t-2 border-slate-200">
                            <div class="flex items-center gap-2 text-[13px] text-slate-600 font-medium">
                                <i data-lucide="package" class="w-4 h-4 text-primary-600"></i>
                                <span>{{ __('pages.stock') }}:</span>
                            </div>
                            <span class="text-[14px] font-bold text-slate-900 bg-slate-100 px-3 py-1 rounded-lg">${product.stock_quantity}{{ __('pages.items') }}</span>
                        </div>
                    </div>
                </div>
            `;
        });
        html += '</div>';
        productsContainer.innerHTML = html;
        
        if (typeof lucide !== 'undefined') lucide.createIcons();
        
        // Update contract limit after auto-checking items
        updateContractLimit();
        
        // Handle size checkboxes (for tops/bottoms)
        document.querySelectorAll('.size-checkbox').forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                const productKey = this.dataset.productKey;
                const size = this.dataset.size;
                const productId = this.dataset.productId;
                const itemKey = `${productKey}_${size}`;
                const quantityControl = this.closest('.border').querySelector(`.size-quantity-control[data-product-key="${productKey}"][data-size="${size}"]`) || 
                                       this.parentElement.querySelector('.size-quantity-control');
                
                if (this.checked) {
                    // Add item with quantity 1
                    if (!selectedItems.has(itemKey)) {
                        selectedItems.set(itemKey, {
                            product_id: productId,
                            product_key: productKey,
                            size: size,
                            quantity: 1,
                            name: this.dataset.productName,
                            code: this.dataset.productCode,
                            image: this.dataset.productImage
                        });
                    } else {
                        const item = selectedItems.get(itemKey);
                        if (item.quantity === 0) {
                            item.quantity = 1;
                        }
                    }
                    // Show quantity control
                    if (quantityControl) {
                        quantityControl.classList.remove('hidden');
                    }
                } else {
                    // Remove item
                    selectedItems.delete(itemKey);
                    // Hide quantity control
                    if (quantityControl) {
                        quantityControl.classList.add('hidden');
                    }
                }
                
                updateQuantityDisplay(itemKey);
                updateContractLimit();
                updateFormFields();
            });
        });
        
        // Handle shoe size quantity input changes (editable inputs)
        window.updateShoeQuantity = function(productKey, size, quantity, productId, productName, productCode, productImage) {
            const itemKey = `${productKey}_${size}`;
            const qty = parseFloat(quantity) || 0;
            
            if (qty > 0) {
                // Add or update item
                if (!selectedItems.has(itemKey)) {
                    selectedItems.set(itemKey, {
                        product_id: productId,
                        product_key: productKey,
                        size: size,
                        quantity: qty,
                        name: productName,
                        code: productCode,
                        image: productImage
                    });
                } else {
                    selectedItems.get(itemKey).quantity = qty;
                }
                
                // Show quantity controls
                const sizeRow = document.querySelector(`input[data-product-key="${productKey}"][data-size="${size}"]`)?.closest('.border');
                if (sizeRow) {
                    const quantityControl = sizeRow.querySelector('.size-quantity-control');
                    if (quantityControl) {
                        quantityControl.classList.remove('hidden');
                    }
                }
            } else {
                // Remove item if quantity is 0
                selectedItems.delete(itemKey);
                
                // Hide quantity controls
                const sizeRow = document.querySelector(`input[data-product-key="${productKey}"][data-size="${size}"]`)?.closest('.border');
                if (sizeRow) {
                    const quantityControl = sizeRow.querySelector('.size-quantity-control');
                    if (quantityControl) {
                        quantityControl.classList.add('hidden');
                    }
                }
            }
            
            updateContractLimit();
            updateFormFields();
        };
        
        // Handle size quantity buttons (for both checkboxes and direct inputs)
        document.querySelectorAll('.size-quantity-btn').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                const productKey = this.dataset.productKey;
                const size = this.dataset.size;
                const productId = this.dataset.productId;
                const itemKey = `${productKey}_${size}`;
                const isPlus = this.classList.contains('plus');
                
                // Ensure item exists
                if (!selectedItems.has(itemKey)) {
                    selectedItems.set(itemKey, {
                        product_id: productId,
                        product_key: productKey,
                        size: size,
                        quantity: 0,
                        name: this.dataset.productName,
                        code: this.dataset.productCode,
                        image: this.dataset.productImage
                    });
                }
                
                updateQuantity(itemKey, isPlus);
            });
        });
    }

    // Update quantity
    function updateQuantity(itemKey, increment) {
        let item = selectedItems.get(itemKey);
        
        if (!item) {
            console.warn('Cannot find item for itemKey:', itemKey);
            return;
        }
        
        if (increment) {
            item.quantity = (item.quantity || 0) + 1;
        } else {
            item.quantity = Math.max(0, (item.quantity || 0) - 1);
        }
        
        if (item.quantity <= 0) {
            selectedItems.delete(itemKey);
            // Uncheck checkbox if it exists
            const [productKey, ...sizeParts] = itemKey.split('_');
            const size = sizeParts.join('_');
            const checkbox = document.querySelector(`.size-checkbox[data-product-key="${productKey}"][data-size="${size}"]`);
            if (checkbox) {
                checkbox.checked = false;
                const quantityControl = checkbox.parentElement.querySelector('.size-quantity-control');
                if (quantityControl) {
                    quantityControl.classList.add('hidden');
                }
            }
        } else {
            selectedItems.set(itemKey, item);
        }
        
        updateQuantityDisplay(itemKey);
        updateContractLimit();
        updateFormFields();
    }

    // Update quantity display
    function updateQuantityDisplay(itemKey) {
        const [productKey, ...sizeParts] = itemKey.split('_');
        const size = sizeParts.join('_'); // Handle sizes with dots like "25.5"
        const item = selectedItems.get(itemKey);
        const quantity = item ? item.quantity : 0;
        
        // Update size-specific quantity input
        const quantityInput = document.querySelector(`.size-quantity-input[data-product-key="${productKey}"][data-size="${size}"]`);
        if (quantityInput) {
            quantityInput.value = quantity;
        }
        
        // Update size-specific quantity buttons
        const minusBtn = document.querySelector(`.size-quantity-btn.minus[data-product-key="${productKey}"][data-size="${size}"]`);
        const plusBtn = document.querySelector(`.size-quantity-btn.plus[data-product-key="${productKey}"][data-size="${size}"]`);
        if (minusBtn) {
            minusBtn.disabled = quantity <= 0;
        }
        
        // Update checkbox state (for tops/bottoms)
        const checkbox = document.querySelector(`.size-checkbox[data-product-key="${productKey}"][data-size="${size}"]`);
        if (checkbox) {
            checkbox.checked = quantity > 0;
            const quantityControl = checkbox.parentElement.querySelector('.size-quantity-control');
            if (quantityControl) {
                if (quantity > 0) {
                    quantityControl.classList.remove('hidden');
                } else {
                    quantityControl.classList.add('hidden');
                }
            }
        }
        
        // Update product card selected state
        const productCard = document.querySelector(`[data-product-key="${productKey}"]`)?.closest('.border') || 
                           document.querySelector(`.size-checkbox[data-product-key="${productKey}"]`)?.closest('.border') ||
                           document.querySelector(`.size-quantity-btn[data-product-key="${productKey}"]`)?.closest('.border');
        if (productCard) {
            const hasAnySelected = Array.from(selectedItems.keys()).some(key => key.startsWith(`${productKey}_`));
            if (hasAnySelected) {
                productCard.classList.add('ring-2', 'ring-primary-500', 'shadow-md');
                productCard.classList.remove('hover:border-primary-300');
            } else {
                productCard.classList.remove('ring-2', 'ring-primary-500', 'shadow-md');
                productCard.classList.add('hover:border-primary-300');
            }
        }
    }

    // Update contract limit
    function updateContractLimit() {
        let total = 0;
        let exceedsLimit = false;
        let insufficientStock = false;
        
        selectedItems.forEach(item => {
            total += item.quantity;
            
            // Check stock availability for each selected item
            if (item.product_id) {
                // Find the product in allUniforms by checking sizes_with_ids
                for (const product of allUniforms) {
                    if (product.sizes_with_ids) {
                        const sizeInfo = product.sizes_with_ids.find(s => s.product_id === item.product_id);
                        if (sizeInfo && (sizeInfo.stock_quantity || 0) < item.quantity) {
                            insufficientStock = true;
                            break;
                        }
                    }
                }
            } else {
                // Try to find product by product_key and size
                const product = allUniforms.find(u => 
                    (u.product_code || u.id) === item.product_key
                );
                if (product && product.sizes_with_ids) {
                    const sizeInfo = product.sizes_with_ids.find(s => s.size === item.size);
                    if (sizeInfo && (sizeInfo.stock_quantity || 0) < item.quantity) {
                        insufficientStock = true;
                    }
                }
            }
        });
        
        // Check if exceeds contract limit
        if (total > contractLimit) {
            exceedsLimit = true;
        }
        
        selectedItemsCount.textContent = total;
        const percentage = Math.min(100, (total / contractLimit) * 100);
        contractLimitProgress.style.width = percentage + '%';
        
        // Show/hide special approval button based on conditions
        const specialApprovalButtonContainer = document.getElementById('special-approval-button-container');
        
        if (exceedsLimit || insufficientStock) {
            if (specialApprovalButtonContainer) {
                specialApprovalButtonContainer.classList.remove('hidden');
            }
            if (total > contractLimit) {
                contractLimitText.textContent = '{{ __('pages.contract_limit_reached') }}';
                contractLimitText.classList.remove('text-slate-500');
                contractLimitText.classList.add('text-amber-600', 'font-medium');
            } else if (insufficientStock) {
                contractLimitText.textContent = '{{ __('pages.special_approval_required') }}';
                contractLimitText.classList.remove('text-slate-500');
                contractLimitText.classList.add('text-amber-600', 'font-medium');
            }
            
            // Auto-open special approval modal when needed
            const specialApprovalModal = document.getElementById('special-approval-modal');
            const specialApprovalHiddenInput = document.getElementById('special_approval_reason');
            if (specialApprovalModal && specialApprovalHiddenInput && !specialApprovalHiddenInput.value) {
                // Only open if modal is not already open and no reason is set
                setTimeout(() => {
                    if (specialApprovalModal.classList.contains('hidden')) {
                        specialApprovalModal.classList.remove('hidden');
                        specialApprovalModal.classList.add('flex');
                        const specialApprovalTextarea = document.getElementById('special_approval_reason_textarea');
                        if (specialApprovalTextarea) {
                            setTimeout(() => specialApprovalTextarea.focus(), 100);
                        }
                    }
                }, 300);
            }
        } else {
            if (specialApprovalButtonContainer) {
                specialApprovalButtonContainer.classList.add('hidden');
            }
            const specialApprovalReason = document.getElementById('special_approval_reason');
            if (specialApprovalReason) {
                specialApprovalReason.value = '';
            }
            contractLimitText.textContent = '';
            contractLimitText.classList.remove('text-amber-600', 'font-medium');
            contractLimitText.classList.add('text-slate-500');
        }
    }

    // Update form fields
    function updateFormFields() {
        selectedItemsContainer.innerHTML = '';
        let index = 0;
        selectedItems.forEach((item, key) => {
            if (item.quantity > 0) {
                let productId = item.product_id;
                
                if (!productId) {
                    let product = filteredUniforms.find(u => 
                        (u.product_code || u.id) === item.product_key
                    );
                    
                    if (product && product.sizes_with_ids && Array.isArray(product.sizes_with_ids)) {
                        const sizeData = product.sizes_with_ids.find(s => s.size === item.size);
                        if (sizeData && sizeData.product_id) {
                            productId = sizeData.product_id;
                        }
                    }
                    
                    if (!productId) {
                        product = allUniforms.find(u => 
                            (u.product_code || u.id) === item.product_key && u.size === item.size
                        );
                        if (product) {
                            productId = product.id;
                        }
                    }
                }
                
                if (productId) {
                    selectedItemsContainer.innerHTML += `
                        <input type="hidden" name="items[${index}][uniform_product_id]" value="${productId}">
                        <input type="hidden" name="items[${index}][quantity]" value="${item.quantity}">
                    `;
                    index++;
                }
            }
        });

        const appType = applicationTypeSelect?.value || '';

        if (returnItemsHidden) {
            returnItemsHidden.innerHTML = '';
            let rIndex = 0;

            // Transfer: selected loans become items[] (transfer_change), not return_items
            if (appType === 'transfer') {
                const existingItemInputs = selectedItemsContainer.querySelectorAll('input[name^="items["]');
                if (existingItemInputs.length === 0) {
                    let tIndex = 0;
                    returnItems.forEach((item, productId) => {
                        if (item.quantity > 0) {
                            selectedItemsContainer.innerHTML += `
                                <input type="hidden" name="items[${tIndex}][uniform_product_id]" value="${productId}">
                                <input type="hidden" name="items[${tIndex}][quantity]" value="${item.quantity}">
                            `;
                            tIndex++;
                        }
                    });
                }
            } else {
                returnItems.forEach((item, productId) => {
                    if (item.quantity > 0) {
                        returnItemsHidden.innerHTML += `
                            <input type="hidden" name="return_items[${rIndex}][uniform_product_id]" value="${productId}">
                            <input type="hidden" name="return_items[${rIndex}][quantity]" value="${item.quantity}">
                        `;
                        rIndex++;
                    }
                });
            }
        }
    }

    function syncTypeDependentUi() {
        const type = applicationTypeSelect?.value || '';
        const needsLoans = ['return', 'exchange', 'resize', 'transfer'].includes(type);
        const needsTransfer = type === 'transfer';

        if (currentLoansPanel) {
            currentLoansPanel.classList.toggle('hidden', !needsLoans);
        }
        if (transferTargetField) {
            transferTargetField.classList.toggle('hidden', !needsTransfer);
            if (transferToSelect) {
                transferToSelect.required = needsTransfer;
            }
        }
        const title = document.getElementById('current-loans-title');
        if (title) {
            if (type === 'transfer') title.textContent = '移管する貸与品';
            else if (type === 'return') title.textContent = '返却する貸与品';
            else title.textContent = '旧品（返却する貸与品）';
        }

        // 移管時はカート＝移管対象。返却時はカート非表示寄りだが既存UIを維持し return_items を主に使う
        const employeeId = document.getElementById('selected-employee-id')?.value;
        if (needsLoans && employeeId) {
            loadEmployeeLoans(employeeId);
        }
    }

    async function loadEmployeeLoans(employeeId) {
        if (!currentLoansList || !employeeId) return;
        currentLoansList.innerHTML = '<p class="text-[13px] text-slate-500">読み込み中...</p>';
        try {
            const res = await fetch(`{{ url('/api/employees') }}/${employeeId}/loans`, {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            });
            if (!res.ok) throw new Error('load failed');
            const loans = await res.json();
            if (!Array.isArray(loans) || loans.length === 0) {
                currentLoansList.innerHTML = '<p class="text-[13px] text-slate-500">貸与中の品がありません。</p>';
                returnItems.clear();
                updateFormFields();
                return;
            }
            currentLoansList.innerHTML = loans.map(loan => {
                const checked = returnItems.has(String(loan.uniform_product_id)) ? 'checked' : '';
                const qty = returnItems.get(String(loan.uniform_product_id))?.quantity || 1;
                return `
                    <label class="flex items-center gap-3 p-2 rounded-lg bg-white border border-slate-200 hover:border-primary-300 cursor-pointer">
                        <input type="checkbox" class="return-loan-check text-primary-600" data-product-id="${loan.uniform_product_id}"
                               data-max="${loan.quantity}" data-name="${loan.product_name || ''}" data-size="${loan.size || ''}" ${checked}>
                        <div class="flex-1 min-w-0">
                            <div class="text-[13px] font-medium text-slate-900 truncate">${loan.product_name || ''} (${loan.size || '-'})</div>
                            <div class="text-[11px] text-slate-500">${loan.product_code || ''} · 貸与中 ${loan.quantity}</div>
                        </div>
                        <input type="number" min="1" max="${loan.quantity}" value="${Math.min(qty, loan.quantity)}"
                               class="return-loan-qty w-16 px-2 py-1 border border-slate-300 rounded text-[13px]"
                               data-product-id="${loan.uniform_product_id}">
                    </label>
                `;
            }).join('');

            currentLoansList.querySelectorAll('.return-loan-check').forEach(cb => {
                cb.addEventListener('change', syncReturnItemsFromUi);
            });
            currentLoansList.querySelectorAll('.return-loan-qty').forEach(inp => {
                inp.addEventListener('change', syncReturnItemsFromUi);
            });
            syncReturnItemsFromUi();
            if (typeof lucide !== 'undefined') lucide.createIcons();
        } catch (e) {
            console.error(e);
            currentLoansList.innerHTML = '<p class="text-[13px] text-red-600">貸与情報の取得に失敗しました。</p>';
        }
    }

    function syncReturnItemsFromUi() {
        returnItems.clear();
        if (!currentLoansList) return;
        currentLoansList.querySelectorAll('.return-loan-check:checked').forEach(cb => {
            const productId = String(cb.dataset.productId);
            const max = parseInt(cb.dataset.max || '1', 10);
            const qtyInput = currentLoansList.querySelector(`.return-loan-qty[data-product-id="${productId}"]`);
            let qty = parseInt(qtyInput?.value || '1', 10);
            if (isNaN(qty) || qty < 1) qty = 1;
            if (qty > max) qty = max;
            returnItems.set(productId, {
                quantity: qty,
                max,
                name: cb.dataset.name || '',
                size: cb.dataset.size || '',
            });
        });
        updateFormFields();
    }

    if (applicationTypeSelect) {
        applicationTypeSelect.addEventListener('change', syncTypeDependentUi);
        syncTypeDependentUi();
    }

    // Special Approval Modal Handlers
    const specialApprovalModal = document.getElementById('special-approval-modal');
    const openSpecialApprovalBtn = document.getElementById('open-special-approval-modal');
    const closeSpecialApprovalBtn = document.getElementById('close-special-approval-modal');
    const cancelSpecialApprovalBtn = document.getElementById('cancel-special-approval');
    const saveSpecialApprovalBtn = document.getElementById('save-special-approval');
    const specialApprovalTextarea = document.getElementById('special_approval_reason_textarea');
    const specialApprovalHiddenInput = document.getElementById('special_approval_reason');
    
    if (openSpecialApprovalBtn) {
        openSpecialApprovalBtn.addEventListener('click', function() {
            if (specialApprovalModal) {
                specialApprovalModal.classList.remove('hidden');
                specialApprovalModal.classList.add('flex');
                if (specialApprovalTextarea) {
                    specialApprovalTextarea.value = specialApprovalHiddenInput?.value || '';
                    setTimeout(() => specialApprovalTextarea.focus(), 100);
                }
            }
        });
    }
    
    function closeSpecialApprovalModal() {
        if (specialApprovalModal) {
            specialApprovalModal.classList.add('hidden');
            specialApprovalModal.classList.remove('flex');
        }
    }
    
    if (closeSpecialApprovalBtn) {
        closeSpecialApprovalBtn.addEventListener('click', closeSpecialApprovalModal);
    }
    
    if (cancelSpecialApprovalBtn) {
        cancelSpecialApprovalBtn.addEventListener('click', closeSpecialApprovalModal);
    }
    
    if (saveSpecialApprovalBtn) {
        saveSpecialApprovalBtn.addEventListener('click', function() {
            if (specialApprovalTextarea && specialApprovalHiddenInput) {
                specialApprovalHiddenInput.value = specialApprovalTextarea.value.trim();
                closeSpecialApprovalModal();
                if (window.showToast) {
                    window.showToast('{{ __('pages.special_approval_reason') }} {{ __('pages.saved') }}', 'success');
                }
                
                // After saving, try to submit the form if it was previously prevented
                const form = document.getElementById('application-form');
                if (form) {
                    // Create a new submit event and dispatch it
                    setTimeout(() => {
                        const submitEvent = new Event('submit', { bubbles: true, cancelable: true });
                        form.dispatchEvent(submitEvent);
                        // If event wasn't prevented, submit the form
                        if (!submitEvent.defaultPrevented) {
                            form.submit();
                        }
                    }, 300);
                }
            }
        });
    }
    
    // Close modal on backdrop click
    if (specialApprovalModal) {
        specialApprovalModal.addEventListener('click', function(e) {
            if (e.target === specialApprovalModal) {
                closeSpecialApprovalModal();
            }
        });
    }

    // Reset all form fields
    function resetApplicationForm() {
        // Reset application mode
        applicationModeInput.value = 'individual';
        document.querySelectorAll('.application-category-option').forEach(opt => {
            opt.classList.remove('border-primary-500', 'bg-primary-50');
            opt.classList.add('border-slate-300', 'bg-white');
        });
        document.querySelector('.application-category-option[data-category="individual"]')?.classList.add('border-primary-500', 'bg-primary-50');
        
        // Reset employee selection
        selectedEmployeeId.value = '';
        selectedEmployeeCard.classList.add('hidden');
        resetEmployeeSelect();
        selectedEmployeeSizes = {
            top_size: null,
            bottom_size: null,
            shoe_size: null
        };
        
        // Reset department selection
        if (departmentSelect) {
            departmentSelect.value = '';
            departmentSelect.required = true;
        }
        departmentEmployeesList?.classList.add('hidden');
        employeeSearchField?.classList.add('hidden');
        
        // Reset dates
        const startDateInput = document.getElementById('start-date-input');
        const endDateInput = document.getElementById('end-date-input');
        if (startDateInput) startDateInput.value = '';
        if (endDateInput) endDateInput.value = '';
        
        // Reset application type
        const typeInputs = document.querySelectorAll('input[name="type"]');
        typeInputs.forEach(input => {
            if (input.value === 'new_loan') {
                input.checked = true;
            } else {
                input.checked = false;
            }
        });
        
        // Reset delivery location
        const deliveryLocationSelect = document.getElementById('delivery-location-select');
        if (deliveryLocationSelect) deliveryLocationSelect.value = '';
        
        // Reset selected items
        selectedItems.clear();
        selectedItemsContainer.innerHTML = '';
        updateContractLimit();
        updateFormFields();
        
        // Reset special approval
        const specialApprovalHiddenInput = document.getElementById('special_approval_reason');
        const specialApprovalTextarea = document.getElementById('special_approval_reason_textarea');
        const specialApprovalButtonContainer = document.getElementById('special-approval-button-container');
        if (specialApprovalHiddenInput) specialApprovalHiddenInput.value = '';
        if (specialApprovalTextarea) specialApprovalTextarea.value = '';
        if (specialApprovalButtonContainer) specialApprovalButtonContainer.classList.add('hidden');
        
        // Reset contract limit display
        if (contractLimitText) {
            contractLimitText.textContent = '';
            contractLimitText.classList.remove('text-amber-600', 'font-medium');
            contractLimitText.classList.add('text-slate-500');
        }
        if (contractLimitProgress) contractLimitProgress.style.width = '0%';
        if (selectedItemsCount) selectedItemsCount.textContent = '0';
        
        // Reset products display
        filteredUniforms = allUniforms;
        currentCategory = 'top';
        
        // Reset category tabs to show first tab as active
        document.querySelectorAll('.category-tab').forEach(tab => {
            tab.classList.remove('active', 'border-primary-600', 'text-primary-600', 'bg-primary-50');
            tab.classList.add('text-slate-600', 'border-transparent');
        });
        
        // Set first tab as active
        const firstTab = document.querySelector('.category-tab[data-category="top"]');
        if (firstTab) {
            firstTab.classList.add('active', 'border-primary-600', 'text-primary-600', 'bg-primary-50');
            firstTab.classList.remove('text-slate-600', 'border-transparent');
        }
        loadProducts();
        
        // Reset any error messages
        const errorDivs = document.querySelectorAll('.text-red-600, .text-red-800');
        errorDivs.forEach(div => {
            if (div.closest('.mb-6')) {
                div.closest('.mb-6').remove();
            }
        });
    }

    // Form validation
    document.getElementById('application-form')?.addEventListener('submit', function(e) {
        // Update form fields before validation
        updateFormFields();
        
        const mode = applicationModeInput.value;
        
        if (mode === 'individual' && !selectedEmployeeId.value) {
            e.preventDefault();
            if (window.showToast) {
                window.showToast('{{ __('pages.select_employee') }}', 'error');
            }
            return false;
        }
        
        if (mode === 'departmental' && !departmentSelect.value) {
            e.preventDefault();
            if (window.showToast) {
                window.showToast('{{ __('pages.select_department') }}', 'error');
            }
            return false;
        }
        
        // Validate line items by application type (return/transfer use loan checkboxes → return_items / items)
        const appType = applicationTypeSelect?.value || '';
        const formItems = selectedItemsContainer.querySelectorAll('input[name^="items["]');
        const formReturns = returnItemsHidden
            ? returnItemsHidden.querySelectorAll('input[name^="return_items["]')
            : [];
        const hasItems = formItems.length > 0;
        const hasReturns = formReturns.length > 0;

        let missingLines = false;
        if (appType === 'return') {
            missingLines = !hasReturns && !hasItems;
        } else if (appType === 'transfer') {
            missingLines = !hasItems && !hasReturns;
        } else if (appType === 'exchange' || appType === 'resize') {
            missingLines = !hasItems && !hasReturns;
        } else {
            missingLines = selectedItems.size === 0 || !hasItems;
        }

        if (missingLines) {
            e.preventDefault();
            if (window.showToast) {
                const msg = (appType === 'return' || appType === 'transfer')
                    ? (appType === 'return' ? '返却する貸与品を選択してください。' : '移管する貸与品を選択してください。')
                    : '{{ __('pages.select_uniform') }}';
                window.showToast(msg, 'error');
            }
            return false;
        }
        
        // Validate end_date is after start_date
        const startDateInput = document.getElementById('start-date-input');
        const endDateInput = document.getElementById('end-date-input');
        if (startDateInput && endDateInput && endDateInput.value) {
            const startDate = new Date(startDateInput.value);
            const endDate = new Date(endDateInput.value);
            if (endDate < startDate) {
                e.preventDefault();
                if (window.showToast) {
                    @if(app()->getLocale() === 'ja')
                        window.showToast('{{ __('pages.loan_end_date') }}は{{ __('pages.loan_start_date') }}より後の日付を入力してください。', 'error');
                    @else
                        window.showToast('{{ __('pages.loan_end_date') }} must be after {{ __('pages.loan_start_date') }}.', 'error');
                    @endif
                }
                endDateInput.focus();
                return false;
            }
        }
        
        // Check if special approval is needed
        const specialApprovalHiddenInput = document.getElementById('special_approval_reason');
        const specialApprovalModal = document.getElementById('special-approval-modal');
        const specialApprovalButtonContainer = document.getElementById('special-approval-button-container');
        
        // Check if special approval is required (exceeds limit or insufficient stock)
        let totalQuantity = 0;
        selectedItems.forEach((item) => {
            totalQuantity += item.quantity || 0;
        });
        
        const exceedsLimit = totalQuantity > contractLimit;
        let insufficientStock = false;
        selectedItems.forEach((item) => {
            const product = allUniforms.find(u => 
                (u.product_code || u.id) === item.product_key && u.size === item.size
            );
            if (product && item.quantity > (product.stock_quantity || 0)) {
                insufficientStock = true;
            }
        });
        
        if ((exceedsLimit || insufficientStock) && (!specialApprovalHiddenInput || !specialApprovalHiddenInput.value)) {
            // Prevent form submission and show modal
            e.preventDefault();
            
            if (specialApprovalModal) {
                specialApprovalModal.classList.remove('hidden');
                specialApprovalModal.classList.add('flex');
                const specialApprovalTextarea = document.getElementById('special_approval_reason_textarea');
                if (specialApprovalTextarea) {
                    setTimeout(() => specialApprovalTextarea.focus(), 100);
                }
            }
            
            if (window.showToast) {
                @if(app()->getLocale() === 'ja')
                    window.showToast('特別承認理由を入力してください。', 'warning');
                @else
                    window.showToast('Please enter special approval reason.', 'warning');
                @endif
            }
            
            return false;
        }
        
        // If special approval reason is provided or not needed, allow form submission
        // Mark form as submitting successfully (will be reset after redirect)
        sessionStorage.setItem('applicationSubmitted', 'true');
    });

    // Check if form was successfully submitted and reset
    @if(session('success'))
        // Reset form after successful submission
        resetApplicationForm();
    @endif
    
    // Also check sessionStorage for form submission
    if (sessionStorage.getItem('applicationSubmitted') === 'true') {
        sessionStorage.removeItem('applicationSubmitted');
        resetApplicationForm();
    }

    // Initial load
    loadProducts();
    
});
</script>
@endpush
