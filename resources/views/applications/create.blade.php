@extends('layouts.app')

@section('title', __('pages.new_application') . ' | ' . __('auth.system_title'))
@section('page_title', __('pages.new_application'))

@section('content')

    <form method="POST" action="{{ route('applications.store') }}" id="application-form">
        @csrf
        <input type="hidden" name="application_mode" id="application-mode" value="individual">
        <input type="hidden" name="employee_id" id="selected-employee-id" value="{{ old('employee_id') }}">

        {{-- Header --}}
        <div class="mb-4 sm:mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div class="min-w-0">
                <h1 class="text-xl sm:text-2xl font-bold text-slate-900 tracking-tight">{{ __('pages.new_application') }}</h1>
                <p class="text-[13px] sm:text-[14px] text-slate-600 mt-1 sm:mt-1.5">{{ __('pages.application_description') }}</p>
            </div>
            <a href="{{ route('applications.index') }}" class="sm:shrink-0 px-4 sm:px-5 py-2.5 rounded-lg border border-slate-300 text-slate-700 hover:bg-slate-50 text-[13px] sm:text-[14px] font-medium transition-all text-center">
                {{ __('pages.cancel') }}
            </a>
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

                <div class="p-4 sm:p-6 space-y-5 sm:space-y-6 max-w-4xl">
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
                        <p id="application-type-hint" class="text-[12px] text-slate-500 mt-1">{{ __('pages.application_type_hint') }}</p>
                        @error('type')
                            <p class="text-[13px] text-red-600 mt-1.5">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Individual applications only (client confirmed) --}}
                    <div class="rounded-lg border border-slate-200 bg-slate-50 px-4 py-3 text-[13px] text-slate-600">
                        {{ __('pages.individual_applications_only_hint') }}
                    </div>

                    {{-- Department Selection (for both modes) --}}
                    <div id="department-selection-field">
                        <label class="block text-slate-700 text-[14px] font-semibold mb-2 flex items-center gap-2">
                            <i data-lucide="building-2" class="w-4 h-4 text-slate-500"></i>
                            {{ __('pages.select_department') }} <span id="department-required-mark" class="text-red-500">*</span>
                        </label>
                        <div class="relative">
                            <select name="department_id" id="department-select"
                                    class="w-full px-4 py-2.5 rounded-lg border border-slate-300 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 text-[14px] bg-white shadow-sm cursor-pointer">
                                <option value="">{{ __('pages.select_please') }}</option>
                                @foreach($departments as $dept)
                                    <option value="{{ $dept->id }}" @selected(old('department_id')==$dept->id)>
                                        {{ $dept->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <p class="text-[12px] text-slate-500 mt-1">{{ __('pages.ui_6ffe42def8') }}</p>
                        
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
                                            <span class="font-medium">{{ __('pages.ui_84515961aa') }}</span>
                                            <span id="employee-inseam" class="text-slate-900"></span>
                                        </span>
                                        <span class="text-slate-600">
                                            <span class="font-medium">{{ __('pages.ui_a0f82143d9') }}</span>
                                            <span id="employee-body-type" class="text-slate-900"></span>
                                        </span>
                                    </div>
                                    <p class="text-[12px] text-slate-600 mt-2">
                                        <span class="font-medium">{{ __('pages.contract_plan_colon') }}</span>
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

                    {{-- 現在の貸与（返却・交換・異動用） --}}
                    <div id="current-loans-panel" class="hidden">
                        <label class="block text-slate-700 text-[14px] font-semibold mb-2 flex items-center gap-2">
                            <i data-lucide="package-minus" class="w-4 h-4 text-amber-600"></i>
                            <span id="current-loans-title">{{ __('pages.return_exchange_loans') }}</span>
                        </label>
                        <div id="current-loans-list" class="space-y-2 max-h-56 overflow-y-auto border border-slate-200 rounded-lg p-3 bg-slate-50">
                            <p class="text-[13px] text-slate-500">{{ __('pages.ui_4380cbc2ab') }}</p>
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

                    {{-- Loan End Date (required for new loan / exchange / resize) --}}
                    <div>
                        <label class="block text-slate-700 text-[14px] font-semibold mb-2 flex items-center gap-2">
                            <i data-lucide="calendar-days" class="w-4 h-4 text-slate-500"></i>
                            {{ __('pages.loan_end_date') }} <span id="end-date-required-mark" class="text-red-500">*</span>
                        </label>
                        <input type="date" name="end_date" id="end-date-input" value="{{ old('end_date') }}"
                               class="w-full px-4 py-2.5 rounded-lg border border-slate-300 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 text-[14px] bg-white shadow-sm">
                        <p class="text-[12px] text-slate-500 mt-1">{{ __('pages.end_date_hint_return') }}</p>
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
                                {{ __('pages.plan_loan_point_limit') }}
                            </span>
                            <span class="text-[14px] text-slate-600">
                                <span id="selected-items-count">0</span>{{ __('pages.items') }} / 
                                <span id="contract-limit">—</span>{{ __('pages.items') }}
                                <span class="text-[12px] text-slate-500">{{ __('pages.ui_059f32bb47') }}</span>
                            </span>
                        </div>
                        <div class="w-full bg-slate-200 rounded-full h-3 overflow-hidden shadow-inner">
                            <div id="contract-limit-progress" class="h-full bg-gradient-to-r from-primary-500 via-blue-600 to-indigo-600 transition-all duration-300 rounded-full" style="width: 0%"></div>
                        </div>
                        <p class="text-[12px] text-slate-500 mt-1.5" id="contract-limit-text"></p>
                        <p class="text-[11px] text-slate-500 mt-1 hidden" id="category-limit-breakdown"></p>
                    </div>

                    {{-- Application memos (SPEC / PDF) --}}
                    <div class="space-y-3">
                        <div>
                            <label class="block text-slate-700 text-[14px] font-semibold mb-2">{{ __('pages.memo1') }}</label>
                            <input type="text" name="memo1" value="{{ old('memo1') }}" maxlength="500"
                                   class="w-full px-4 py-2.5 rounded-lg border border-slate-300 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 text-[14px]"
                                   placeholder="{{ __('pages.memo1_placeholder') }}">
                            @error('memo1')
                                <p class="text-[13px] text-red-600 mt-1.5">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label class="block text-slate-700 text-[14px] font-semibold mb-2">{{ __('pages.memo2') }}</label>
                            <input type="text" name="memo2" value="{{ old('memo2') }}" maxlength="500"
                                   class="w-full px-4 py-2.5 rounded-lg border border-slate-300 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 text-[14px]"
                                   placeholder="{{ __('pages.memo2_placeholder') }}">
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
                            {{ __('pages.special_approval_reason') }} <span id="special-approval-optional-label">({{ __('pages.optional') }})</span>
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
                <div class="p-4 sm:p-6 border-b border-slate-200 bg-gradient-to-r from-primary-50 to-blue-50">
                    <h2 class="text-base sm:text-lg font-semibold text-slate-900 flex items-center gap-2">
                        <i data-lucide="shopping-cart" class="w-5 h-5 text-primary-600"></i>
                        {{ __('pages.product_selection') }}
                    </h2>
                    <p class="text-[12px] sm:text-[13px] text-slate-600 mt-1">{{ __('pages.ui_913298c989') }}</p>
                </div>

                <div class="p-4 sm:p-6">
                    {{-- Category Tabs --}}
                    <div class="flex flex-wrap gap-1 sm:gap-2 mb-4 sm:mb-6 border-b-2 border-slate-200 -mx-1 px-1 overflow-x-auto">
                        @foreach(($categories ?? []) as $idx => $cat)
                            <button type="button"
                                    class="category-tab px-3 sm:px-5 py-2 sm:py-3 text-[12px] sm:text-[14px] font-semibold border-b-3 transition-all flex items-center gap-1.5 sm:gap-2 rounded-t-lg whitespace-nowrap {{ $idx === 0 ? 'active text-primary-600 bg-primary-50 border-primary-600' : 'text-slate-600 border-transparent hover:text-primary-600 hover:border-primary-500 hover:bg-slate-50' }}"
                                    data-category="{{ $cat->code }}"
                                    data-size-scheme="{{ $cat->size_scheme }}"
                                    data-employee-size-field="{{ $cat->employee_size_field }}">
                                <i data-lucide="tag" class="w-4 h-4 sm:w-5 sm:h-5"></i>
                                {{ $cat->name }}
                            </button>
                        @endforeach
                    </div>

                    {{-- Products Grid --}}
                    <div id="products-container" class="space-y-4 py-2 overflow-y-auto">
                        {{-- Products will be loaded here via JavaScript --}}
                        <div class="text-center py-12 text-slate-500">
                            <div class="uniform-placeholder mx-auto mb-3 w-16 h-16 rounded-xl">
                                <img src="{{ asset('images/brand/logo-mark-64.png') }}" alt="">
                            </div>
                            <p class="text-[14px]">{{ __('pages.no_products_available') }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Submit at the end (after application info + product selection) --}}
        <div class="mt-6 flex flex-col-reverse sm:flex-row sm:items-center sm:justify-end gap-2 sm:gap-3 border-t border-slate-200 pt-5">
            <a href="{{ route('applications.index') }}" class="px-4 sm:px-5 py-2.5 rounded-lg border border-slate-300 text-slate-700 hover:bg-slate-50 text-[13px] sm:text-[14px] font-medium transition-all text-center">
                {{ __('pages.cancel') }}
            </a>
            <button type="submit" class="px-4 sm:px-8 py-2.5 sm:py-3 rounded-lg bg-primary-600 hover:bg-primary-700 text-white text-[14px] sm:text-[15px] font-semibold shadow-md transition-all flex items-center justify-center gap-2">
                <i data-lucide="send" class="w-4 h-4"></i>
                {{ __('pages.submit_application') }}
            </button>
        </div>

        {{-- Hidden form fields for selected items --}}
        <div id="selected-items-container" class="hidden"></div>
    </form>
@endsection

@push('scripts')

    <style>
        /* Keep qty input compact but readable; hide native spinners that inflate width */
        .size-quantity-input {
            width: 2.75rem;
            min-width: 2.75rem;
            max-width: 3rem;
            -moz-appearance: textfield;
            appearance: textfield;
        }
        .size-quantity-input::-webkit-outer-spin-button,
        .size-quantity-input::-webkit-inner-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }
        .return-loan-qty {
            width: 3rem;
            min-width: 3rem;
            -moz-appearance: textfield;
            appearance: textfield;
        }
        .return-loan-qty::-webkit-outer-spin-button,
        .return-loan-qty::-webkit-inner-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }
    </style>

    <script>
        window.__i18nAppCreate = {
            stock: @json(__('pages.stock')),
            onLoan: @json(__('pages.on_loan')),
            size_not_match: @json(__('pages.size_not_match')),
            registered_size_hint: @json(__('pages.registered_size_hint')),
            new_qty_exceeds_change_qty: @json(__('pages.new_qty_exceeds_change_qty')),
            select_change_items_first: @json(__('pages.select_change_items')),
            rentable_limit_hard_block_short: @json(__('pages.rentable_limit_hard_block_short')),
            out_of_stock: @json(__('pages.out_of_stock')),
        };
    </script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const BRAND_UNIFORM_MARK = @json(asset('images/brand/logo-mark-64.png'));
    const uniformPlaceholderHtml = (extraMsg = '') => `
        <div class="text-center py-12 text-slate-500">
            <div class="uniform-placeholder mx-auto mb-3 w-16 h-16 rounded-xl">
                <img src="${BRAND_UNIFORM_MARK}" alt="">
            </div>
            ${extraMsg}
        </div>
    `;

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
    const categoryLimitBreakdownEl = document.getElementById('category-limit-breakdown');
    let categoryLimits = null; // { top, bottom, other, other2, other3 } or null
    const categoryLimitLabels = {
        top: @json(__('pages.cap_top')),
        bottom: @json(__('pages.cap_bottom')),
        other: @json(__('pages.cap_other')),
        other2: @json(__('pages.cap_other2')),
        other3: @json(__('pages.cap_other3')),
    };

    function normalizeLimitCategory(category) {
        if (category === 'top' || category === 'bottom' || category === 'other2' || category === 'other3') {
            return category;
        }
        return 'other';
    }

    function productLimitCategory(productOrItem) {
        return normalizeLimitCategory(
            productOrItem?.limit_category
            || productOrItem?.slot_category
            || productOrItem?.category
        );
    }

    function selectedQtyByCategory() {
        const qty = { top: 0, bottom: 0, other: 0, other2: 0, other3: 0 };
        // Current in-use loans (from loaded loans list) minus selected returns
        if (typeof returnItems !== 'undefined' && currentLoansList) {
            // loans already reflected via heldLoansByCategory if set
        }
        if (window.__heldLoansByCategory) {
            Object.keys(window.__heldLoansByCategory).forEach((cat) => {
                qty[cat] = (qty[cat] || 0) + (window.__heldLoansByCategory[cat] || 0);
            });
        }
        if (typeof returnItems !== 'undefined') {
            returnItems.forEach((item) => {
                const cat = productLimitCategory(item);
                qty[cat] = Math.max(0, (qty[cat] || 0) - (item.quantity || 0));
            });
        }
        selectedItems.forEach((item) => {
            let category = item.limit_category || item.category;
            if (!category && item.product_id) {
                const product = allUniforms.find(u => String(u.id) === String(item.product_id))
                    || allUniforms.find(u => (u.sizes_with_ids || []).some(s => String(s.product_id) === String(item.product_id)));
                category = product?.limit_category || product?.category;
            }
            const cat = normalizeLimitCategory(category);
            qty[cat] += item.quantity || 0;
        });
        return qty;
    }

    function exceedsAnyCategoryLimit(projectedByCategory = null) {
        if (!categoryLimits) return false;
        const projected = projectedByCategory || selectedQtyByCategory();
        return Object.keys(categoryLimits).some((cat) => {
            return (projected[cat] || 0) > (categoryLimits[cat] || 0);
        });
    }

    function heldLoanQtyTotal() {
        let total = 0;
        if (window.__heldLoansByCategory) {
            Object.keys(window.__heldLoansByCategory).forEach((cat) => {
                total += window.__heldLoansByCategory[cat] || 0;
            });
        }
        return total;
    }

    function projectedTotalQty(extraAdd = 0) {
        return Math.max(0, heldLoanQtyTotal() - selectedReturnQtyTotal()) + selectedNewQtyTotal() + extraAdd;
    }

    function wouldExceedLimitIfAdd(category, addQty = 1) {
        if (categoryLimits) {
            const cat = normalizeLimitCategory(category);
            const qty = selectedQtyByCategory();
            return ((qty[cat] || 0) + addQty) > (categoryLimits[cat] || 0);
        }
        if (!contractLimit || contractLimit <= 0) {
            return false;
        }
        // Total plan points: include currently held loans (minus returns) + new items
        return projectedTotalQty(addQty) > contractLimit;
    }

    function toastPlanLimit() {
        const msg = window.__i18nAppCreate?.rentable_limit_hard_block_short
            || @json(__('pages.rentable_limit_hard_block_short'));
        if (window.showToast) window.showToast(msg, 'error');
        else alert(msg);
    }

    function resolveItemCategory(item, fallbackCategory = 'other') {
        let category = item?.limit_category || item?.category || fallbackCategory;
        if ((!category || category === 'other') && item?.product_id) {
            const product = allUniforms.find(u => String(u.id) === String(item.product_id))
                || allUniforms.find(u => (u.sizes_with_ids || []).some(s => String(s.product_id) === String(item.product_id)));
            category = product?.limit_category || product?.category || category;
        }
        return normalizeLimitCategory(category);
    }

    let allUniforms = @json($uniformsJson);
    let filteredUniforms = [];
    let employeePlanFilterActive = false;
    let selectedItems = new Map();
    let returnItems = new Map(); // product_id => { quantity, max, name, size }
    const returnItemsHidden = document.getElementById('return-items-hidden');
    const currentLoansPanel = document.getElementById('current-loans-panel');
    const currentLoansList = document.getElementById('current-loans-list');
    const applicationTypeSelect = document.getElementById('application-type-select');

    function selectedReturnQtyTotal() {
        let total = 0;
        returnItems.forEach((item) => { total += item.quantity || 0; });
        return total;
    }

    function selectedNewQtyTotal() {
        let total = 0;
        selectedItems.forEach((item) => { total += item.quantity || 0; });
        return total;
    }

    function needsReturnQtyCap() {
        const t = applicationTypeSelect?.value || '';
        return ['resize', 'exchange', 'transfer'].includes(t);
    }

    function wouldExceedReturnQtyIfAdd(addQty = 1) {
        if (!needsReturnQtyCap()) return false;
        const returnQty = selectedReturnQtyTotal();
        if (returnQty <= 0) return true;
        return (selectedNewQtyTotal() + addQty) > returnQty;
    }

    function toastReturnQtyCap() {
        const msg = selectedReturnQtyTotal() <= 0
            ? (window.__i18nAppCreate?.select_change_items_first || @json(__('pages.select_change_items')))
            : (window.__i18nAppCreate?.new_qty_exceeds_change_qty || @json(__('pages.new_qty_exceeds_change_qty')));
        if (window.showToast) window.showToast(msg, 'error');
        else alert(msg);
    }

    function clampSelectedItemsToReturnQty() {
        if (!needsReturnQtyCap()) return;
        const allowed = selectedReturnQtyTotal();
        if (selectedNewQtyTotal() <= allowed) return;

        const entries = Array.from(selectedItems.entries());
        for (let i = entries.length - 1; i >= 0 && selectedNewQtyTotal() > allowed; i--) {
            const [key, item] = entries[i];
            const over = selectedNewQtyTotal() - allowed;
            const reduceBy = Math.min(item.quantity || 0, over);
            item.quantity = (item.quantity || 0) - reduceBy;
            if (item.quantity <= 0) {
                selectedItems.delete(key);
            } else {
                selectedItems.set(key, item);
            }
            if (typeof updateQuantityDisplay === 'function') {
                updateQuantityDisplay(key);
            }
        }
        if (typeof updateContractLimit === 'function') updateContractLimit();
        if (typeof updateFormFields === 'function') updateFormFields();
        if (typeof loadProducts === 'function') loadProducts();
    }

    function isOverPlanLimit() {
        if (categoryLimits) return exceedsAnyCategoryLimit();
        return contractLimit > 0 && projectedTotalQty() > contractLimit;
    }

    function clampSelectedItemsToPlanLimit() {
        if (!isOverPlanLimit()) return;
        const entries = Array.from(selectedItems.entries());
        for (let i = entries.length - 1; i >= 0 && isOverPlanLimit(); i--) {
            const [key, item] = entries[i];
            item.quantity = Math.max(0, (item.quantity || 0) - 1);
            if (item.quantity <= 0) {
                selectedItems.delete(key);
            } else {
                selectedItems.set(key, item);
            }
            if (typeof updateQuantityDisplay === 'function') {
                updateQuantityDisplay(key);
            }
        }
        if (typeof updateContractLimit === 'function') updateContractLimit();
        if (typeof updateFormFields === 'function') updateFormFields();
        if (typeof loadProducts === 'function') loadProducts();
    }
    let currentCategory = document.querySelector('.category-tab.active')?.dataset.category
        || document.querySelector('.category-tab')?.dataset.category
        || '';

    function productSizeScheme(product) {
        if (product?.size_scheme) {
            return product.size_scheme;
        }
        // Fallback when API omitted size_scheme (avoid recursive self-call)
        return product?.category === 'shoe' ? 'shoe' : 'letter';
    }
    function productEmployeeSizeField(product) {
        if (product.employee_size_field) return product.employee_size_field;
        if (product.category === 'top') return 'top_size';
        if (product.category === 'bottom') return 'bottom_size';
        if (productSizeScheme(product) === 'shoe') return 'shoe_size';
        return null;
    }
    function employeeSizeForProduct(emp, product) {
        const field = productEmployeeSizeField(product);
        return field ? (emp?.[field] || null) : null;
    }
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
                        data-employee-plans="${planLabel || @json(__('pages.unassigned'))}"
                        data-employee-rentable-limit="${emp.rentable_limit ?? ''}"
                        data-employee-rentable-limits-by-category='${JSON.stringify(emp.rentable_limits_by_category || null).replace(/'/g, "&#39;")}'
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
                // Individual: department is filter-only (server uses employee primary dept)
                departmentSelect.required = false;
                const deptMark = document.getElementById('department-required-mark');
                if (deptMark) deptMark.classList.add('hidden');
                // Clear employee selection when switching modes
                selectedEmployeeCard.classList.add('hidden');
                selectedEmployeeId.value = '';
                resetEmployeeSelect();
                filteredUniforms = [];
                employeePlanFilterActive = false;
                // Show employee search field only if department is already selected
                if (departmentSelect.value) {
                    employeeSearchField.classList.remove('hidden');
                    loadEmployeesForSelect(departmentSelect.value);
                } else {
                    employeeSearchField.classList.remove('hidden');
                    loadEmployeesForSelect('');
                }
            } else {
                employeeSearchField.classList.add('hidden');
                departmentSelectionField.classList.remove('hidden');
                departmentSelect.required = true;
                const deptMark = document.getElementById('department-required-mark');
                if (deptMark) deptMark.classList.remove('hidden');
                selectedEmployeeCard.classList.add('hidden');
                selectedEmployeeId.value = '';
                resetEmployeeSelect();
                filteredUniforms = [];
                employeePlanFilterActive = false;
                // Load employees when switching to departmental mode if department is already selected
                if (departmentSelect.value) {
                    loadDepartmentEmployees(departmentSelect.value);
                }
            }
        });
    });

    // Dashboard deep-link ?mode= ignored — individual only
    (function initIndividualMode() {
        applicationModeInput.value = 'individual';
        if (employeeSearchField) {
            employeeSearchField.classList.remove('hidden');
        }
        if (departmentSelect) {
            departmentSelect.required = false;
        }
        const deptMark = document.getElementById('department-required-mark');
        if (deptMark) deptMark.classList.add('hidden');
        loadEmployeesForSelect(departmentSelect?.value || '');
    })();

    // Department selection handler
    departmentSelect?.addEventListener('change', async function() {
        const departmentId = this.value;
        const mode = applicationModeInput.value;
        
            if (mode === 'individual') {
            // Individual mode: products only after employee + plan — department only narrows the employee list
            if (departmentId) {
                employeeSearchField.classList.remove('hidden');
                await loadEmployeesForSelect(departmentId);
            } else {
                employeeSearchField.classList.remove('hidden');
                await loadEmployeesForSelect('');
            }
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
            filteredUniforms = [];
            employeePlanFilterActive = false;
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
                    // Filter uniforms by department plan products (no full-catalog fallback)
                    await filterUniformsByDepartment(departmentId);
                }
            } else {
                departmentEmployeesList.classList.add('hidden');
                filteredUniforms = [];
                employeePlanFilterActive = false;
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
            filteredUniforms = [];
            employeePlanFilterActive = false;
            loadProducts();
            return;
        }
        
        try {
            // Get uniforms for this department
            const response = await fetch(`{{ route('api.uniforms_by_plan') }}?department_id=${departmentId}`);
            
            if (!response.ok) {
                console.error('API response not OK:', response.status, response.statusText);
                filteredUniforms = [];
                employeePlanFilterActive = true;
                loadProducts();
                return;
            }
            
            const uniforms = await response.json();
            
            if (!Array.isArray(uniforms) || uniforms.length === 0) {
                filteredUniforms = [];
                employeePlanFilterActive = true;
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
                } else if (productSizeScheme(product) === 'shoe') {
                    product.sizes.sort((a, b) => parseFloat(a) - parseFloat(b));
                    product.sizes_with_ids.sort((a, b) => parseFloat(a.size) - parseFloat(b.size));
                }
                
                return product;
            });
            
            loadProducts();
        } catch (error) {
            console.error('Uniform fetch error:', error);
            filteredUniforms = [];
            employeePlanFilterActive = true;
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
        if (plansEl) plansEl.textContent = data.employeePlans || @json(__('pages.unassigned'));
        selectedEmployeeCard.classList.remove('hidden');

        // Update plan-based rentable limit for progress bar
        if (data.employeeRentableLimit !== undefined && data.employeeRentableLimit !== '') {
            const parsed = parseInt(data.employeeRentableLimit, 10);
            if (!Number.isNaN(parsed) && parsed > 0) {
                contractLimit = parsed;
                if (contractLimitEl) contractLimitEl.textContent = String(contractLimit);
            }
        }
        categoryLimits = null;
        if (data.employeeRentableLimitsByCategory) {
            try {
                const parsedCaps = typeof data.employeeRentableLimitsByCategory === 'string'
                    ? JSON.parse(data.employeeRentableLimitsByCategory)
                    : data.employeeRentableLimitsByCategory;
                if (parsedCaps && typeof parsedCaps === 'object') {
                    categoryLimits = parsedCaps;
                }
            } catch (e) {
                categoryLimits = null;
            }
        }
        // Always load held loans for limit projection (new_loan included)
        if (data.employeeId) {
            loadEmployeeLoans(data.employeeId);
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
        
        // Products come only from the employee's confirmed contract plan
        const deptId = departmentSelect ? departmentSelect.value : (selectedEmployeeInfo.department_id || null);
        const jobId = selectedEmployeeInfo.job_type_id;
        const gender = selectedEmployeeInfo.gender;
        filterUniformsByEmployee(deptId, jobId, gender);

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
        employeePlanFilterActive = false;
        loadProducts();
    });

    // Filter uniforms by employee
    async function filterUniformsByEmployee(deptId, jobId, gender) {
        const employeeId = document.getElementById('selected-employee-id')?.value
            || document.querySelector('[name="employee_id"]')?.value;

        // Prefer plan-linked products for the selected employee
        if (employeeId) {
            try {
                let apiUrl = `{{ route('api.uniforms_by_plan') }}?employee_id=${employeeId}`;
                if (gender) {
                    apiUrl += `&gender=${gender}`;
                }
                const response = await fetch(apiUrl);
                const uniforms = await response.json();

                if (!Array.isArray(uniforms) || uniforms.length === 0) {
                    filteredUniforms = [];
                    employeePlanFilterActive = true;
                    const plansEl = document.getElementById('employee-plans');
                    const planText = plansEl ? plansEl.textContent : '';
                    let emptyMsg = '{{ __('pages.no_products_available') }}';
                    if (planText && planText.includes(@json(__('pages.before_start')))) {
                        emptyMsg = @json(__('pages.js_plan_future_start'));
                    } else if (!planText || planText === @json(__('pages.unassigned')) || planText === '-') {
                        emptyMsg = @json(__('pages.js_no_plan_assigned'));
                    } else {
                        emptyMsg = @json(__('pages.js_plan_no_products'));
                    }
                    productsContainer.innerHTML = uniformPlaceholderHtml(`
                            <p class="text-[14px]">${emptyMsg}</p>
                            <p class="text-[12px] text-slate-400 mt-2">{{ __('pages.check_category_tabs') }}</p>
                    `);
                    return;
                }

                employeePlanFilterActive = true;

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
                        category_label: first.category_label,
                        size_scheme: first.size_scheme || (first.category === 'shoe' ? 'shoe' : 'letter'),
                        employee_size_field: first.employee_size_field || null,
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
                filteredUniforms = [];
                employeePlanFilterActive = true;
                productsContainer.innerHTML = uniformPlaceholderHtml(`
                        <p class="text-[14px]">${@json(__('pages.js_plan_no_products'))}</p>
                `);
                return;
            }
        }

        // No employee selected — keep catalog empty until a plan-backed employee is chosen
        filteredUniforms = [];
        employeePlanFilterActive = false;
        loadProducts();
    }
    
    // Filter uniforms by department (plan products only — never reopen full catalog)
    async function filterUniformsByDepartment(departmentId) {
        if (!departmentId) {
            filteredUniforms = [];
            employeePlanFilterActive = false;
            loadProducts();
            return;
        }
        
        try {
            // Get uniforms for this department
            const response = await fetch(`{{ route('api.uniforms_by_plan') }}?department_id=${departmentId}`);
            
            if (!response.ok) {
                console.error('API response not OK:', response.status, response.statusText);
                filteredUniforms = [];
                employeePlanFilterActive = true;
                loadProducts();
                return;
            }
            
            const uniforms = await response.json();
            
            // Check if uniforms is an array
            if (!Array.isArray(uniforms)) {
                console.error('API did not return an array:', uniforms);
                filteredUniforms = [];
                employeePlanFilterActive = true;
                loadProducts();
                return;
            }
            
            // Empty plan catalog — show nothing (do not fall back to all products)
            if (uniforms.length === 0) {
                filteredUniforms = [];
                employeePlanFilterActive = true;
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
            employeePlanFilterActive = true;
            
            loadProducts();
        } catch (error) {
            console.error('Department filter error:', error);
            filteredUniforms = [];
            employeePlanFilterActive = true;
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
        // Catalog is plan-gated: only show products after an employee plan filter ran
        // (or temporary department fetch). Never dump the full unfiltered catalog.
        let productsToShow = [];
        if (filteredUniforms.length > 0) {
            productsToShow = filteredUniforms.slice();
        } else if (employeePlanFilterActive) {
            productsToShow = [];
        } else if (selectedEmployeeId?.value) {
            // Employee selected but plan filter not ready / failed → show nothing
            productsToShow = [];
        } else {
            // No employee yet — do not browse the full catalog
            productsContainer.innerHTML = uniformPlaceholderHtml(`
                    <p class="text-[14px]">${@json(__('pages.select_employee_for_products'))}</p>
            `);
            return;
        }
        
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
            // Individual mode: filter plan products by gender only (never reopen full catalog)
            productsToShow = productsToShow.filter(u => 
                u.gender === 'unisex' || u.gender === selectedEmployeeInfo.gender || !u.gender
            );
        }
        
        // Filter by category
        productsToShow = productsToShow.filter(u => u.category === currentCategory);

        if (productsToShow.length === 0) {
            productsContainer.innerHTML = uniformPlaceholderHtml(`
                    <p class="text-[14px]">{{ __('pages.no_products_available') }}</p>
            `);
            return;
        }

        // E-shop card grid layout
        let html = '<div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-3 sm:gap-4">';
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
                        } else if (productSizeScheme(product) === 'shoe' && emp.shoe_size) {
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
                        (productSizeScheme(product) === 'shoe' && emp.shoe_size)
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
                    } else if (productSizeScheme(product) === 'shoe' && selectedEmployeeSizes.shoe_size) {
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
                    // Employee size is reference only — do not block other sizes
                    disabled: false,
                    employeeCount: employeeCountForSize // Number of employees who need this size
                };
            });
            
            html += `
                <div class="group border border-slate-200 rounded-xl overflow-hidden hover:shadow-lg transition-all duration-300 min-w-0 ${isSelected ? 'ring-2 ring-primary-500 shadow-md' : 'hover:border-primary-300'}">
                    <div class="relative h-40 bg-gradient-to-br from-slate-50 to-slate-100 overflow-hidden">
                        ${product.image_path ? 
                            `<img src="${product.image_path}" alt="${product.name}" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">` :
                            `<div class="w-full h-full uniform-placeholder">
                                <img src="${BRAND_UNIFORM_MARK}" alt="">
                            </div>`
                        }
                        ${isSelected ? `
                            <div class="absolute top-2 right-2 bg-primary-600 text-white rounded-full p-1.5 shadow-lg">
                                <i data-lucide="check" class="w-4 h-4"></i>
                            </div>
                        ` : ''}
                    </div>
                    
                    <div class="p-3 sm:p-4 space-y-3 sm:space-y-4 min-w-0 overflow-hidden">
                        <div class="min-w-0">
                            <h3 class="font-semibold text-slate-900 text-[14px] sm:text-[15px] mb-1 line-clamp-2 leading-tight break-words">${product.name}</h3>
                            <p class="text-[11px] sm:text-[12px] text-slate-500 font-mono truncate">${product.product_code}</p>
                        </div>
                        
                        ${/* Size Selection - Multiple sizes with checkboxes for tops/bottoms, inputs for shoes */''}
                        <div>
                            <label class="block text-[13px] font-semibold text-slate-700 mb-2 flex items-center gap-1.5">
                                <i data-lucide="ruler" class="w-4 h-4 text-primary-600"></i>
                                {{ __('pages.size') }}
                            </label>
                            ${productSizeScheme(product) === 'shoe' ? 
                                /* Shoe size - Multiple number inputs in a grid */
                                `<div class="space-y-2 max-h-56 overflow-y-auto overflow-x-hidden pr-0.5">
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
                                        
                                        const wouldExceedLimit = wouldExceedLimitIfAdd(product.category, 1);
                                        const wouldExceedReturn = wouldExceedReturnQtyIfAdd(1);
                                        
                                        const shouldAutoCheck = departmentSelected && 
                                                                 selectedEmployeeId.value && 
                                                                 departmentMatches && 
                                                                 genderMatches && 
                                                                 sizeMatches && 
                                                                 isAvailable && 
                                                                 notAlreadySelected && 
                                                                 !wouldExceedLimit &&
                                                                 !wouldExceedReturn;
                                        
                                        const selectedItem = selectedItems.get(itemKey) || { quantity: shouldAutoCheck ? 1 : 0, size: sizeData.size };
                                        
                                        // If auto-checking, add to selectedItems
                                        if (shouldAutoCheck && sizeData.product_id) {
                                            selectedItems.set(itemKey, {
                                                product_id: sizeData.product_id || product.id,
                                                product_name: product.name,
                                                product_code: product.product_code || product.id,
                                                product_image: product.image_path,
                                                size: sizeData.size,
                                                category: product.category,
                                                quantity: 1
                                            });
                                        }
                                        
                                        return `
                                            <div class="size-row grid grid-cols-[minmax(0,1fr)_auto] gap-2 items-center p-2 rounded-lg border min-w-0 overflow-hidden transition-all ${selectedItem.quantity > 0 || shouldAutoCheck ? 'bg-primary-50 border-primary-400' : 'border-slate-200 hover:border-primary-300'} ${!isAvailable ? 'opacity-60' : ''}">
                                                <div class="min-w-0">
                                                    <div class="text-[12px] sm:text-[13px] font-medium leading-snug break-words text-slate-700">
                                                        <span class="font-semibold">${sizeData.size}</span>
                                                        ${sizeMatches ? ` <span class="text-[10px] sm:text-[11px] font-normal text-primary-600">(${window.__i18nAppCreate.registered_size_hint || '{{ __('pages.registered_size_hint') }}'})</span>` : ''}
                                                        ${isDepartmentalMode && sizeData.employeeCount > 0 ? ` <span class="text-[10px] sm:text-[11px] text-primary-600 font-semibold">(${sizeData.employeeCount})</span>` : ''}
                                                    </div>
                                                    ${sizeData.product_id ? `<div class="text-[10px] sm:text-[11px] text-slate-500 mt-0.5">${window.__i18nAppCreate.stock}: ${sizeData.stock_quantity || 0}</div>` : ''}
                                                </div>
                                                <div class="flex items-center gap-1 shrink-0">
                                                    <button type="button" class="size-quantity-btn minus w-7 h-7 shrink-0 rounded border border-slate-300 bg-white hover:bg-slate-50 flex items-center justify-center disabled:opacity-40 disabled:cursor-not-allowed transition-all" 
                                                            data-product-key="${productKey}" 
                                                            data-size="${sizeData.size}" 
                                                            data-product-id="${sizeData.product_id || product.id}"
                                                            data-product-name="${product.name}"
                                                            data-product-code="${product.product_code || product.id}"
                                                            data-product-image="${product.image_path}"
                                                            data-product-category="${product.category || 'other'}"
                                                            ${selectedItem.quantity <= 0 && !shouldAutoCheck || !isAvailable ? 'disabled' : ''}>
                                                        <i data-lucide="minus" class="w-3 h-3 pointer-events-none"></i>
                                                    </button>
                                                    <input type="number" min="0" step="0.5" inputmode="numeric" value="${selectedItem.quantity || (shouldAutoCheck ? 1 : 0)}" 
                                                           class="size-quantity-input px-0.5 py-1 text-center text-[13px] border border-slate-300 rounded font-medium bg-white text-slate-900 ${!isAvailable ? 'opacity-50' : ''}" 
                                                           data-product-key="${productKey}" 
                                                           data-size="${sizeData.size}"
                                                           ${!isAvailable ? 'readonly' : ''}
                                                           onchange="updateShoeQuantity('${productKey}', '${sizeData.size}', this.value, '${sizeData.product_id || product.id}', '${product.name}', '${product.product_code || product.id}', '${product.image_path}')">
                                                    <button type="button" class="size-quantity-btn plus w-7 h-7 shrink-0 rounded border border-slate-300 bg-white hover:bg-slate-50 flex items-center justify-center transition-all ${!isAvailable ? 'opacity-50 cursor-not-allowed' : ''}" 
                                                            data-product-key="${productKey}" 
                                                            data-size="${sizeData.size}"
                                                            data-product-id="${sizeData.product_id || product.id}"
                                                            data-product-name="${product.name}"
                                                            data-product-code="${product.product_code || product.id}"
                                                            data-product-image="${product.image_path}"
                                                            data-product-category="${product.category || 'other'}"
                                                            ${!isAvailable ? 'disabled' : ''}>
                                                        <i data-lucide="plus" class="w-3 h-3 pointer-events-none"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        `;
                                    }).join('')}
                                </div>` :
                                /* Top/Bottom size - Checkboxes with quantity controls */
                                `<div class="space-y-2 max-h-56 overflow-y-auto overflow-x-hidden pr-0.5">
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
                                            
                                            const wouldExceedLimit = wouldExceedLimitIfAdd(product.category, defaultQuantity);
                                            const wouldExceedReturn = wouldExceedReturnQtyIfAdd(defaultQuantity);
                                            
                                            shouldAutoCheck = genderMatches && 
                                                             sizeMatches && 
                                                             isAvailable && 
                                                             notAlreadySelected && 
                                                             !wouldExceedLimit &&
                                                             !wouldExceedReturn &&
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
                                            
                                            const wouldExceedLimit = wouldExceedLimitIfAdd(product.category, 1);
                                            const wouldExceedReturn = wouldExceedReturnQtyIfAdd(1);
                                            
                                            shouldAutoCheck = departmentMatches && 
                                                             genderMatches && 
                                                             sizeMatches && 
                                                             isAvailable && 
                                                             notAlreadySelected && 
                                                             !wouldExceedLimit &&
                                                             !wouldExceedReturn;
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
                                                category: product.category,
                                                quantity: defaultQuantity
                                            });
                                        }

                                        const isAvailableRow = sizeData.available && sizeData.stock_quantity > 0;
                                        const statusNote = !isAvailableRow
                                            ? (window.__i18nAppCreate.out_of_stock || '{{ __('pages.out_of_stock') }}')
                                            : (sizeData.matchesEmployeeSize
                                                ? (window.__i18nAppCreate.registered_size_hint || '{{ __('pages.registered_size_hint') }}')
                                                : '');
                                        
                                        return `
                                            <div class="size-row grid grid-cols-[auto_minmax(0,1fr)_auto] gap-1.5 sm:gap-2 items-center p-2 rounded-lg border min-w-0 overflow-hidden transition-all ${selectedItem.quantity > 0 || shouldAutoCheck ? 'bg-primary-50 border-primary-400' : 'border-slate-200 hover:border-primary-300'} ${!isAvailableRow ? 'opacity-60' : ''}">
                                                <input type="checkbox" 
                                                       class="size-checkbox w-4 h-4 shrink-0 text-primary-600 border-slate-300 rounded focus:ring-primary-500" 
                                                       data-product-key="${productKey}" 
                                                       data-size="${sizeData.size}"
                                                       data-product-id="${sizeData.product_id || product.id}"
                                                       data-product-name="${product.name}"
                                                       data-product-code="${product.product_code || product.id}"
                                                       data-product-image="${product.image_path}"
                                                       data-product-category="${product.category || 'other'}"
                                                       ${selectedItem.quantity > 0 || shouldAutoCheck ? 'checked' : ''}
                                                       ${!isAvailableRow ? 'disabled' : ''}>
                                                <label class="min-w-0 ${!isAvailableRow ? 'text-slate-400' : 'text-slate-700 cursor-pointer'}">
                                                    <div class="text-[12px] sm:text-[13px] font-medium leading-snug break-words">
                                                        <span class="font-semibold">${sizeData.size}</span>
                                                        ${statusNote ? ` <span class="text-[10px] sm:text-[11px] font-normal ${sizeData.matchesEmployeeSize && isAvailableRow ? 'text-primary-600' : ''}">(${statusNote})</span>` : ''}
                                                        ${isDepartmentalMode && sizeData.employeeCount > 0 ? ` <span class="text-[10px] sm:text-[11px] text-primary-600 font-semibold">(${sizeData.employeeCount})</span>` : ''}
                                                    </div>
                                                    ${sizeData.product_id ? `<div class="text-[10px] sm:text-[11px] text-slate-500 mt-0.5">${window.__i18nAppCreate.stock}: ${sizeData.stock_quantity || 0}</div>` : ''}
                                                </label>
                                                <div class="flex items-center gap-1 shrink-0 ${selectedItem.quantity > 0 || shouldAutoCheck ? '' : 'hidden'} size-quantity-control">
                                                    <button type="button" class="size-quantity-btn minus w-7 h-7 shrink-0 rounded border border-slate-300 bg-white hover:bg-slate-50 flex items-center justify-center disabled:opacity-40 disabled:cursor-not-allowed transition-all" 
                                                            data-product-key="${productKey}" 
                                                            data-size="${sizeData.size}"
                                                            data-product-id="${sizeData.product_id || product.id}"
                                                            data-product-name="${product.name}"
                                                            data-product-code="${product.product_code || product.id}"
                                                            data-product-image="${product.image_path}"
                                                            data-product-category="${product.category || 'other'}"
                                                            ${selectedItem.quantity <= 0 && !shouldAutoCheck ? 'disabled' : ''}>
                                                        <i data-lucide="minus" class="w-3 h-3 pointer-events-none"></i>
                                                    </button>
                                                    <input type="number" min="0" inputmode="numeric" value="${selectedItem.quantity || (shouldAutoCheck ? defaultQuantity : 0)}" 
                                                           class="size-quantity-input px-0.5 py-1 text-center text-[13px] border border-slate-300 rounded font-medium bg-white text-slate-900" 
                                                           data-product-key="${productKey}" 
                                                           data-size="${sizeData.size}"
                                                           readonly>
                                                    <button type="button" class="size-quantity-btn plus w-7 h-7 shrink-0 rounded border border-slate-300 bg-white hover:bg-slate-50 flex items-center justify-center transition-all" 
                                                            data-product-key="${productKey}" 
                                                            data-size="${sizeData.size}"
                                                            data-product-id="${sizeData.product_id || product.id}"
                                                            data-product-name="${product.name}"
                                                            data-product-code="${product.product_code || product.id}"
                                                            data-product-image="${product.image_path}"
                                                            data-product-category="${product.category || 'other'}">
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
                        <div class="flex items-center justify-between gap-3 pt-3 border-t-2 border-slate-200 min-w-0">
                            <div class="flex items-center gap-2 text-[13px] text-slate-600 font-medium shrink-0">
                                <i data-lucide="package" class="w-4 h-4 text-primary-600"></i>
                                <span>{{ __('pages.stock') }}:</span>
                            </div>
                            <span class="text-[13px] font-bold text-slate-900 bg-slate-100 px-2.5 py-1 rounded-lg whitespace-nowrap">${product.stock_quantity}{{ __('pages.items') }}</span>
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
                const quantityControl = this.closest('.size-row')?.querySelector('.size-quantity-control');
                
                if (this.checked) {
                    const already = selectedItems.get(itemKey)?.quantity || 0;
                    const needAdd = already > 0 ? 0 : 1;
                    if (needAdd > 0 && wouldExceedReturnQtyIfAdd(needAdd)) {
                        this.checked = false;
                        toastReturnQtyCap();
                        return;
                    }
                    if (needAdd > 0 && wouldExceedLimitIfAdd(this.dataset.productCategory || 'other', needAdd)) {
                        this.checked = false;
                        toastPlanLimit();
                        return;
                    }
                    // Add item with quantity 1
                    if (!selectedItems.has(itemKey)) {
                        selectedItems.set(itemKey, {
                            product_id: productId,
                            product_key: productKey,
                            size: size,
                            quantity: 1,
                            category: this.dataset.productCategory || 'other',
                            name: this.dataset.productName,
                            code: this.dataset.productCode,
                            image: this.dataset.productImage
                        });
                    } else {
                        const item = selectedItems.get(itemKey);
                        if (item.quantity === 0) {
                            item.quantity = 1;
                        }
                        if (!item.category) item.category = this.dataset.productCategory || 'other';
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
            let qty = parseFloat(quantity) || 0;
            const existing = selectedItems.get(itemKey)?.quantity || 0;
            const delta = qty - existing;

            if (delta > 0 && wouldExceedReturnQtyIfAdd(delta)) {
                toastReturnQtyCap();
                const maxAllowed = Math.max(0, selectedReturnQtyTotal() - (selectedNewQtyTotal() - existing));
                qty = Math.min(existing, maxAllowed);
                const input = document.querySelector(`.size-quantity-input[data-product-key="${productKey}"][data-size="${size}"]`);
                if (input) input.value = qty;
                if (qty <= 0) {
                    selectedItems.delete(itemKey);
                    updateQuantityDisplay(itemKey);
                    updateContractLimit();
                    updateFormFields();
                    return;
                }
            }

            if (delta > 0) {
                const product = allUniforms.find(u => (u.sizes_with_ids || []).some(s => String(s.product_id) === String(productId)))
                    || allUniforms.find(u => String(u.product_code || u.id) === String(productKey));
                if (wouldExceedLimitIfAdd(product?.category || 'other', delta)) {
                    toastPlanLimit();
                    const input = document.querySelector(`.size-quantity-input[data-product-key="${productKey}"][data-size="${size}"]`);
                    if (input) input.value = existing;
                    updateQuantityDisplay(itemKey);
                    updateContractLimit();
                    updateFormFields();
                    return;
                }
            }
            
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
                const sizeRow = document.querySelector(`.size-quantity-input[data-product-key="${productKey}"][data-size="${size}"]`)?.closest('.size-row');
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
                const sizeRow = document.querySelector(`.size-quantity-input[data-product-key="${productKey}"][data-size="${size}"]`)?.closest('.size-row');
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
            if (wouldExceedReturnQtyIfAdd(1)) {
                toastReturnQtyCap();
                return;
            }
            if (wouldExceedLimitIfAdd(resolveItemCategory(item), 1)) {
                toastPlanLimit();
                return;
            }
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
                const quantityControl = checkbox.closest('.size-row')?.querySelector('.size-quantity-control');
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
            const quantityControl = checkbox.closest('.size-row')?.querySelector('.size-quantity-control');
            if (quantityControl) {
                if (quantity > 0) {
                    quantityControl.classList.remove('hidden');
                } else {
                    quantityControl.classList.add('hidden');
                }
            }
        }
        
        // Update product card selected state
        const productCard = document.querySelector(`.size-checkbox[data-product-key="${productKey}"]`)?.closest('.group') ||
                           document.querySelector(`.size-quantity-btn[data-product-key="${productKey}"]`)?.closest('.group');
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
        
        // Check if exceeds contract limit (total or per-category)
        // Progress uses projected holdings: currently held − returns + newly selected
        const byCategory = selectedQtyByCategory();
        const projectedTotal = categoryLimits
            ? Object.values(byCategory).reduce((sum, n) => sum + (n || 0), 0)
            : projectedTotalQty();
        if (categoryLimits) {
            exceedsLimit = exceedsAnyCategoryLimit(byCategory);
        } else if (contractLimit > 0 && projectedTotal > contractLimit) {
            exceedsLimit = true;
        }
        
        selectedItemsCount.textContent = projectedTotal;
        const percentage = Math.min(100, (projectedTotal / Math.max(contractLimit, 1)) * 100);
        contractLimitProgress.style.width = percentage + '%';

        if (categoryLimitBreakdownEl) {
            if (categoryLimits) {
                const parts = Object.keys(categoryLimitLabels).map((cat) => {
                    const used = byCategory[cat] || 0;
                    const limit = categoryLimits[cat] || 0;
                    if (limit <= 0 && used <= 0) return null;
                    return `${categoryLimitLabels[cat]} ${used}/${limit}`;
                }).filter(Boolean);
                categoryLimitBreakdownEl.textContent = parts.join(' · ');
                categoryLimitBreakdownEl.classList.toggle('hidden', parts.length === 0);
            } else {
                const held = heldLoanQtyTotal();
                const room = Math.max(0, contractLimit - projectedTotal);
                categoryLimitBreakdownEl.textContent = @json(__('pages.plan_limit_projection_hint'))
                    .replace(':held', String(held))
                    .replace(':new', String(total))
                    .replace(':projected', String(projectedTotal))
                    .replace(':limit', String(contractLimit))
                    .replace(':room', String(room));
                categoryLimitBreakdownEl.classList.remove('hidden');
            }
        }
        
        // Show/hide special approval for stock only; over-limit is hard-blocked
        const specialApprovalButtonContainer = document.getElementById('special-approval-button-container');
        
        if (exceedsLimit) {
            if (specialApprovalButtonContainer) {
                specialApprovalButtonContainer.classList.add('hidden');
            }
            const saOpt = document.getElementById('special-approval-optional-label');
            if (saOpt) {
                saOpt.textContent = '({{ __('pages.optional') }})';
                saOpt.classList.remove('text-amber-800', 'font-semibold');
            }
            contractLimitText.textContent = '{{ __('pages.rentable_limit_hard_block_short') }}';
            contractLimitText.classList.remove('text-slate-500', 'text-amber-600');
            contractLimitText.classList.add('text-red-600', 'font-medium');
        } else if (insufficientStock) {
            if (specialApprovalButtonContainer) {
                specialApprovalButtonContainer.classList.remove('hidden');
            }
            const saOpt = document.getElementById('special-approval-optional-label');
            if (saOpt) {
                saOpt.textContent = '({{ __('pages.required') }})';
                saOpt.classList.add('text-amber-800', 'font-semibold');
            }
            contractLimitText.textContent = '{{ __('pages.special_approval_required') }}';
            contractLimitText.classList.remove('text-slate-500', 'text-red-600');
            contractLimitText.classList.add('text-amber-600', 'font-medium');
            
            const specialApprovalModal = document.getElementById('special-approval-modal');
            const specialApprovalHiddenInput = document.getElementById('special_approval_reason');
            if (specialApprovalModal && specialApprovalHiddenInput && !specialApprovalHiddenInput.value) {
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
            const saOptOff = document.getElementById('special-approval-optional-label');
            if (saOptOff) {
                saOptOff.textContent = '({{ __('pages.optional') }})';
                saOptOff.classList.remove('text-amber-800', 'font-semibold');
            }
            const specialApprovalReason = document.getElementById('special_approval_reason');
            if (specialApprovalReason) {
                specialApprovalReason.value = '';
            }
            contractLimitText.textContent = '';
            contractLimitText.classList.remove('text-amber-600', 'text-red-600', 'font-medium');
            contractLimitText.classList.add('text-slate-500');
        }
    }

    // Update form fields
    function updateFormFields() {
        selectedItemsContainer.innerHTML = '';
        let index = 0;
        const appType = applicationTypeSelect?.value || '';
        // Pure return posts only return_items[] — posting items[] too duplicated lines
        if (appType !== 'return') {
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
        }

        if (returnItemsHidden) {
            returnItemsHidden.innerHTML = '';
            let rIndex = 0;

            // Same-employee change types: selected loans -> return_items[]
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

    function syncTypeDependentUi() {
        const type = applicationTypeSelect?.value || '';
        const needsLoans = ['return', 'exchange', 'resize', 'transfer'].includes(type);
        const needsEndDate = ['new_loan', 'exchange', 'resize', 'transfer'].includes(type);

        const endDateInput = document.getElementById('end-date-input');
        const endDateMark = document.getElementById('end-date-required-mark');
        if (endDateInput) {
            endDateInput.required = needsEndDate;
        }
        if (endDateMark) {
            endDateMark.classList.toggle('hidden', !needsEndDate);
        }

        if (currentLoansPanel) {
            currentLoansPanel.classList.toggle('hidden', !needsLoans);
        }

        const loansTitle = document.getElementById('current-loans-title');
        if (loansTitle) {
            if (type === 'transfer') loansTitle.textContent = @json(__('pages.transfer_old_items_title'));
            else if (type === 'return') loansTitle.textContent = @json(__('pages.return_items_title'));
            else if (type === 'resize') loansTitle.textContent = @json(__('pages.resize_items_title'));
            else loansTitle.textContent = @json(__('pages.exchange_items_title'));
        }
        const typeHintEl = document.getElementById('application-type-hint');
        if (typeHintEl) {
            const hints = {
                new_loan: @json(__('pages.new_loan_hint')),
                resize: @json(__('pages.resize_hint')),
                exchange: @json(__('pages.exchange_hint')),
                return: @json(__('pages.return_hint')),
                transfer: @json(__('pages.transfer_hint')),
            };
            typeHintEl.textContent = hints[type] || @json(__('pages.application_type_hint'));
        }

        // 移管時はカート＝移管対象。返却時はカート非表示寄りだが既存UIを維持し return_items を主に使う
        if (type === 'return') {
            selectedItems.clear();
            updateFormFields();
            if (typeof updateContractLimit === 'function') updateContractLimit();
        }
        const employeeId = document.getElementById('selected-employee-id')?.value;
        if (needsLoans && employeeId) {
            loadEmployeeLoans(employeeId);
        }
    }

    async function loadEmployeeLoans(employeeId) {
        if (!currentLoansList || !employeeId) return;
        currentLoansList.innerHTML = '<p class="text-[13px] text-slate-500">{{ __('common.loading') }}</p>';
        try {
            const res = await fetch(`{{ url('/api/employees') }}/${employeeId}/loans`, {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            });
            if (!res.ok) throw new Error('load failed');
            const loans = await res.json();
            window.__heldLoansByCategory = { top: 0, bottom: 0, other: 0, other2: 0, other3: 0 };
            if (Array.isArray(loans)) {
                loans.forEach((loan) => {
                    const cat = normalizeLimitCategory(loan.limit_category || loan.category);
                    window.__heldLoansByCategory[cat] = (window.__heldLoansByCategory[cat] || 0) + (loan.quantity || 0);
                });
            }
            if (typeof updateContractLimit === 'function') {
                updateContractLimit();
            }
            clampSelectedItemsToPlanLimit();
            if (!Array.isArray(loans) || loans.length === 0) {
                if (currentLoansList) {
                    currentLoansList.innerHTML = '<p class="text-[13px] text-slate-500">{{ __('pages.no_active_loans') }}</p>';
                }
                returnItems.clear();
                updateFormFields();
                return;
            }
            if (!currentLoansList) return;
            currentLoansList.innerHTML = loans.map(loan => {
                const checked = returnItems.has(String(loan.uniform_product_id)) ? 'checked' : '';
                const qty = returnItems.get(String(loan.uniform_product_id))?.quantity || 1;
                return `
                    <label class="flex items-center gap-2 sm:gap-3 p-2 rounded-lg bg-white border border-slate-200 hover:border-primary-300 cursor-pointer min-w-0">
                        <input type="checkbox" class="return-loan-check text-primary-600 shrink-0" data-product-id="${loan.uniform_product_id}"
                               data-max="${loan.quantity}" data-name="${loan.product_name || ''}" data-size="${loan.size || ''}"
                               data-category="${loan.category || 'other'}" data-limit-category="${loan.limit_category || loan.category || 'other'}" ${checked}>
                        <div class="flex-1 min-w-0">
                            <div class="text-[12px] sm:text-[13px] font-medium text-slate-900 truncate">${loan.product_name || ''} (${loan.size || '-'})</div>
                            <div class="text-[10px] sm:text-[11px] text-slate-500 truncate">${loan.product_code || ''} · ${window.__i18nAppCreate.onLoan} ${loan.quantity}</div>
                        </div>
                        <input type="number" min="1" max="${loan.quantity}" value="${Math.min(qty, loan.quantity)}"
                               class="return-loan-qty px-1 py-1 border border-slate-300 rounded text-[12px] text-center"
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
            currentLoansList.innerHTML = '<p class="text-[13px] text-red-600">{{ __('pages.ui_7cffc9ba1a') }}</p>';
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
                category: cb.dataset.category || 'other',
                limit_category: cb.dataset.limitCategory || cb.dataset.category || 'other',
            });
        });
        clampSelectedItemsToReturnQty();
        updateFormFields();
        if (typeof updateContractLimit === 'function') {
            updateContractLimit();
        }
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
            departmentSelect.required = false;
        }
        const deptMark = document.getElementById('department-required-mark');
        if (deptMark) deptMark.classList.add('hidden');
        departmentEmployeesList?.classList.add('hidden');
        employeeSearchField?.classList.remove('hidden');
        loadEmployeesForSelect('');
        
        // Reset dates
        const startDateInput = document.getElementById('start-date-input');
        const endDateInput = document.getElementById('end-date-input');
        if (startDateInput) startDateInput.value = new Date().toISOString().slice(0, 10);
        if (endDateInput) {
            endDateInput.value = '';
            endDateInput.required = true;
        }
        const endMark = document.getElementById('end-date-required-mark');
        if (endMark) endMark.classList.remove('hidden');
        syncTypeDependentUi();
        
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
        
        // Reset products display — wait for employee + plan again
        filteredUniforms = [];
        employeePlanFilterActive = false;
        currentCategory = document.querySelector('.category-tab')?.dataset.category || '';
        
        // Reset category tabs to show first tab as active
        document.querySelectorAll('.category-tab').forEach(tab => {
            tab.classList.remove('active', 'border-primary-600', 'text-primary-600', 'bg-primary-50');
            tab.classList.add('text-slate-600', 'border-transparent');
        });
        
        // Set first tab as active
        const firstTab = document.querySelector('.category-tab');
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
                    ? (appType === 'return' ? @json(__('pages.select_return_items')) : @json(__('pages.select_change_items')))
                    : '{{ __('pages.select_uniform') }}';
                window.showToast(msg, 'error');
            }
            return false;
        }

        if (['resize', 'exchange', 'transfer'].includes(appType)) {
            if (selectedReturnQtyTotal() <= 0) {
                e.preventDefault();
                toastReturnQtyCap();
                return false;
            }
            if (selectedNewQtyTotal() > selectedReturnQtyTotal()) {
                e.preventDefault();
                toastReturnQtyCap();
                return false;
            }
            if (appType !== 'transfer' && selectedNewQtyTotal() <= 0) {
                e.preventDefault();
                if (window.showToast) window.showToast('{{ __('pages.select_uniform') }}', 'error');
                return false;
            }
        }
        
        // Validate end_date is after start_date when both present
        const startDateInput = document.getElementById('start-date-input');
        const endDateInput = document.getElementById('end-date-input');
        const needsEndDate = ['new_loan', 'exchange', 'resize'].includes(appType);
        if (needsEndDate && endDateInput && !endDateInput.value) {
            e.preventDefault();
            if (window.showToast) {
                window.showToast(@json(__('pages.enter_loan_end_date')), 'error');
            }
            endDateInput.focus();
            return false;
        }
        if (startDateInput && endDateInput && endDateInput.value) {
            const startDate = new Date(startDateInput.value);
            const endDate = new Date(endDateInput.value);
            if (endDate < startDate) {
                e.preventDefault();
                if (window.showToast) {
                    @if(app()->getLocale() === 'ja')
                        window.showToast(@json(__('pages.loan_end_after_start', ['end' => __('pages.loan_end_date'), 'start' => __('pages.loan_start_date')])), 'error');
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
        
        // Check if special approval is required (stock shortage only; over-limit is hard-blocked)
        let totalQuantity = 0;
        selectedItems.forEach((item) => {
            totalQuantity += item.quantity || 0;
        });
        
        const exceedsLimit = categoryLimits
            ? exceedsAnyCategoryLimit()
            : (contractLimit > 0 && projectedTotalQty() > contractLimit);
        if (exceedsLimit) {
            e.preventDefault();
            if (window.showToast) {
                window.showToast('{{ __('pages.rentable_limit_hard_block_short') }}', 'error');
            }
            return false;
        }

        // Match updateContractLimit stock logic (grouped products use sizes_with_ids)
        let insufficientStock = false;
        selectedItems.forEach((item) => {
            const productId = item.product_id;
            let stockQty = null;
            for (const product of allUniforms) {
                if (product.sizes_with_ids) {
                    const sizeInfo = product.sizes_with_ids.find(s =>
                        String(s.product_id) === String(productId) || (s.size === item.size && (product.product_code || product.id) === item.product_key)
                    );
                    if (sizeInfo) {
                        stockQty = sizeInfo.stock_quantity || 0;
                        break;
                    }
                } else if (String(product.id) === String(productId)) {
                    stockQty = product.stock_quantity || 0;
                    break;
                }
            }
            if (stockQty !== null && (item.quantity || 0) > stockQty) {
                insufficientStock = true;
            }
        });

        if (insufficientStock && (!specialApprovalHiddenInput || !specialApprovalHiddenInput.value)) {
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
                window.showToast('{{ __('pages.enter_special_approval_reason') }}', 'error');
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
