@extends('layouts.app')

@section('title', __('menu.loan_status') . ' | ' . config('app.name'))
@section('page_title', __('menu.loan_status'))

@section('content')
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-slate-900 tracking-tight">{{ __('menu.loan_status') }}</h1>
        <p class="text-[14px] text-slate-600 mt-1.5">
            {{ $mode === 'admin' ? __('pages.loan_status_admin_desc') : __('pages.loan_status_hospital_desc') }}
        </p>
    </div>

    <div class="grid grid-cols-2 {{ $mode === 'admin' ? 'lg:grid-cols-5' : 'lg:grid-cols-4' }} gap-4 mb-6">
        <div class="bg-white border border-slate-200 rounded-xl p-4 shadow-sm">
            <p class="text-[12px] font-semibold text-slate-500 uppercase tracking-wide">{{ __('pages.active_loan_pieces') }}</p>
            <p class="text-2xl font-bold text-slate-900 mt-1">{{ $stats['total_quantity'] ?? 0 }}</p>
        </div>
        <div class="bg-white border border-slate-200 rounded-xl p-4 shadow-sm">
            <p class="text-[12px] font-semibold text-slate-500 uppercase tracking-wide">{{ __('pages.staff_with_loans') }}</p>
            <p class="text-2xl font-bold text-slate-900 mt-1">{{ $stats['employee_count'] ?? 0 }}</p>
        </div>
        @if($mode === 'admin')
            <div class="bg-white border border-slate-200 rounded-xl p-4 shadow-sm">
                <p class="text-[12px] font-semibold text-slate-500 uppercase tracking-wide">{{ __('pages.institutions_with_loans') }}</p>
                <p class="text-2xl font-bold text-slate-900 mt-1">{{ $stats['institution_count'] ?? 0 }}</p>
            </div>
        @endif
        <div class="bg-white border border-slate-200 rounded-xl p-4 shadow-sm">
            <p class="text-[12px] font-semibold text-slate-500 uppercase tracking-wide">{{ __('pages.departments_with_loans') }}</p>
            <p class="text-2xl font-bold text-slate-900 mt-1">{{ $stats['department_count'] ?? 0 }}</p>
        </div>
        <div class="bg-white border border-slate-200 rounded-xl p-4 shadow-sm">
            <p class="text-[12px] font-semibold text-slate-500 uppercase tracking-wide">{{ __('pages.product_skus_on_loan') }}</p>
            <p class="text-2xl font-bold text-slate-900 mt-1">{{ $stats['sku_count'] ?? 0 }}</p>
        </div>
    </div>

    @php
        $loanFiltersActive = filled($filters['medical_institution_id'] ?? null)
            || filled($filters['department_id'] ?? null)
            || filled($filters['q'] ?? null)
            || filled($filters['product_q'] ?? null);
    @endphp
    <x-filter-bar :action="$formAction" form-id="loan-status-filter-form" :active="$loanFiltersActive">
        @if($mode === 'admin')
            <div class="ui-filter-field">
                <label class="ui-filter-field__label">{{ __('pages.medical_institution') }}</label>
                <select name="medical_institution_id" class="ui-select" onchange="this.form.submit()">
                    <option value="">{{ __('pages.all_medical_institutions') }}</option>
                    @foreach($institutions as $inst)
                        <option value="{{ $inst->id }}" @selected((string)($filters['medical_institution_id'] ?? '') === (string)$inst->id)>
                            {{ $inst->name }}
                        </option>
                    @endforeach
                </select>
            </div>
        @endif
        <div class="ui-filter-field">
            <label class="ui-filter-field__label">{{ __('pages.department') }}</label>
            <select name="department_id" class="ui-select" @disabled($lockDepartment) onchange="this.form.submit()">
                @if(! $lockDepartment)
                    <option value="">{{ __('pages.all_departments') }}</option>
                @endif
                @forelse($departments as $dept)
                    <option value="{{ $dept->id }}" @selected((string)($filters['department_id'] ?? '') === (string)$dept->id)>
                        {{ $dept->name }}
                    </option>
                @empty
                    @if($mode === 'admin' && empty($filters['medical_institution_id']))
                        <option value="" disabled>{{ __('pages.select_institution_first') }}</option>
                    @endif
                @endforelse
            </select>
            @if($lockDepartment)
                <input type="hidden" name="department_id" value="{{ $filters['department_id'] }}">
            @endif
        </div>
        <div class="ui-filter-field ui-filter-field--grow">
            <label class="ui-filter-field__label">{{ __('pages.employee') }}</label>
            <input type="text" name="q" value="{{ $filters['q'] ?? '' }}" class="ui-input"
                   placeholder="{{ __('pages.search_employee_placeholder') }}">
        </div>
        <div class="ui-filter-field ui-filter-field--grow">
            <label class="ui-filter-field__label">{{ __('pages.uniform_product') }}</label>
            <input type="text" name="product_q" value="{{ $filters['product_q'] ?? '' }}" class="ui-input"
                   placeholder="{{ __('pages.search_product_placeholder') }}">
        </div>
        <x-slot:actions>
            <button type="submit" class="ui-btn ui-btn-primary">
                <i data-lucide="search" class="w-4 h-4"></i>
                {{ __('common.search') }}
            </button>
            <a href="{{ $formAction }}" class="ui-btn ui-btn-secondary">
                {{ __('pages.clear_filters') }}
            </a>
        </x-slot:actions>
    </x-filter-bar>

    <div class="bg-white border border-slate-200 rounded-xl shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full text-[14px]">
                <thead class="bg-slate-50 border-b border-slate-200">
                    <tr>
                        @if($mode === 'admin')
                            <th class="px-4 py-3 text-left font-semibold text-slate-700">{{ __('pages.medical_institution') }}</th>
                        @endif
                        <th class="px-4 py-3 text-left font-semibold text-slate-700">{{ __('pages.department') }}</th>
                        <th class="px-4 py-3 text-left font-semibold text-slate-700">{{ __('pages.employee') }}</th>
                        <th class="px-4 py-3 text-left font-semibold text-slate-700">{{ __('pages.product_code') }}</th>
                        <th class="px-4 py-3 text-left font-semibold text-slate-700">{{ __('pages.product_name') }}</th>
                        <th class="px-4 py-3 text-left font-semibold text-slate-700">{{ __('pages.category') }}</th>
                        <th class="px-4 py-3 text-left font-semibold text-slate-700">{{ __('pages.size') }}</th>
                        <th class="px-4 py-3 text-right font-semibold text-slate-700">{{ __('pages.quantity') }}</th>
                        <th class="px-4 py-3 text-left font-semibold text-slate-700">{{ __('pages.loan_start_date_short') }}</th>
                        <th class="px-4 py-3 text-right font-semibold text-slate-700">{{ __('common.actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($loans as $loan)
                        @php
                            $employee = $loan->employee;
                            $product = $loan->uniformProduct;
                            $department = $employee?->primaryDepartment;
                            $institution = $employee?->medicalInstitution ?? $department?->medicalInstitution;
                        @endphp
                        <tr class="hover:bg-slate-50/50">
                            @if($mode === 'admin')
                                <td class="px-4 py-3 text-slate-800">{{ $institution?->name ?? '-' }}</td>
                            @endif
                            <td class="px-4 py-3 text-slate-800">
                                @if($department)
                                    <a href="{{ route($departmentShowRoute, $department) }}" class="text-primary-700 hover:underline">
                                        {{ $department->name }}
                                    </a>
                                @else
                                    -
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                @if($employee)
                                    <a href="{{ route($employeeShowRoute, $employee) }}" class="font-medium text-primary-700 hover:underline">
                                        {{ $employee->name }}
                                    </a>
                                    <div class="text-[11px] text-slate-500 font-mono">{{ $employee->staff_no }}</div>
                                @else
                                    -
                                @endif
                            </td>
                            <td class="px-4 py-3 font-mono text-slate-700">{{ $product?->product_code ?? '-' }}</td>
                            <td class="px-4 py-3 text-slate-900">
                                <div>{{ $product?->name ?? '-' }}</div>
                                @if($product?->maker?->name)
                                    <div class="text-[11px] text-slate-500">{{ $product->maker->name }}</div>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-slate-600">{{ \App\Models\UniformCategory::labelFor($product?->category) }}</td>
                            <td class="px-4 py-3 text-slate-700">{{ $product?->size ?? '-' }}</td>
                            <td class="px-4 py-3 text-right font-semibold text-slate-900">{{ $loan->quantity }}</td>
                            <td class="px-4 py-3 text-slate-600 whitespace-nowrap">{{ optional($loan->loan_start_date)->format('Y-m-d') ?? '-' }}</td>
                            <td class="px-4 py-3 text-right">
                                @if($employee)
                                    <a href="{{ route($employeeShowRoute, $employee) }}"
                                       class="inline-flex items-center gap-1 px-2.5 py-1 rounded border border-slate-300 text-[12px] text-slate-700 hover:bg-white">
                                        {{ __('common.view') }}
                                    </a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $mode === 'admin' ? 10 : 9 }}" class="px-4 py-12 text-center text-slate-500">
                                {{ __('pages.no_active_loans') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <x-table-pagination :paginator="$loans" />
    </div>
@endsection
