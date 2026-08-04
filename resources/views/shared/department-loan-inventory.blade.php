@extends('layouts.app')

@section('title', __('pages.department_loan_inventory') . ' | ' . config('app.name'))
@section('page_title', __('pages.department_loan_inventory'))

@section('content')
    <div class="mb-6">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-slate-900 tracking-tight">{{ $department->name }}</h1>
                <p class="text-[14px] text-slate-600 mt-1.5">
                    <span class="font-mono">{{ $department->code }}</span>
                    @if($department->medicalInstitution)
                        · {{ $department->medicalInstitution->name }}
                    @endif
                    — {{ __('pages.department_loan_inventory_desc') }}
                </p>
            </div>
            <a href="{{ $backUrl }}" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-lg border border-slate-300 text-slate-700 hover:bg-slate-50 text-[14px] font-medium transition-all">
                <i data-lucide="arrow-left" class="w-4 h-4"></i>
                {{ __('common.back_to_list') }}
            </a>
        </div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
        <div class="bg-white border border-slate-200 rounded-xl p-4 shadow-sm">
            <p class="text-[12px] font-semibold text-slate-500 uppercase tracking-wide">{{ __('pages.active_loan_pieces') }}</p>
            <p class="text-2xl font-bold text-slate-900 mt-1">{{ $inventory['total_quantity'] }}</p>
        </div>
        <div class="bg-white border border-slate-200 rounded-xl p-4 shadow-sm">
            <p class="text-[12px] font-semibold text-slate-500 uppercase tracking-wide">{{ __('pages.staff_with_loans') }}</p>
            <p class="text-2xl font-bold text-slate-900 mt-1">{{ $inventory['employee_count_with_loans'] }}</p>
        </div>
        <div class="bg-white border border-slate-200 rounded-xl p-4 shadow-sm">
            <p class="text-[12px] font-semibold text-slate-500 uppercase tracking-wide">{{ __('pages.product_skus_on_loan') }}</p>
            <p class="text-2xl font-bold text-slate-900 mt-1">{{ $inventory['by_product']->count() }}</p>
        </div>
    </div>

    <div class="bg-white border border-slate-200 rounded-xl shadow-sm overflow-hidden mb-6">
        <div class="px-5 py-4 border-b border-slate-200 bg-slate-50/80">
            <h2 class="text-[15px] font-semibold text-slate-900">{{ __('pages.loans_by_product') }}</h2>
            <p class="text-[12px] text-slate-500 mt-0.5">{{ __('pages.loans_by_product_desc') }}</p>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full text-[14px]">
                <thead class="bg-slate-50 border-b border-slate-200">
                    <tr>
                        <th class="px-4 py-3 text-left font-semibold text-slate-700">{{ __('pages.product_code') }}</th>
                        <th class="px-4 py-3 text-left font-semibold text-slate-700">{{ __('pages.product_name') }}</th>
                        <th class="px-4 py-3 text-left font-semibold text-slate-700">{{ __('pages.category') }}</th>
                        <th class="px-4 py-3 text-left font-semibold text-slate-700">{{ __('pages.size') }}</th>
                        <th class="px-4 py-3 text-right font-semibold text-slate-700">{{ __('pages.quantity') }}</th>
                        <th class="px-4 py-3 text-right font-semibold text-slate-700">{{ __('pages.holders') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($inventory['by_product'] as $row)
                        <tr class="hover:bg-slate-50/50">
                            <td class="px-4 py-3 font-mono text-slate-700">{{ $row->product_code }}</td>
                            <td class="px-4 py-3 text-slate-900">
                                <div>{{ $row->product_name }}</div>
                                @if($row->maker_name && $row->maker_name !== '-')
                                    <div class="text-[11px] text-slate-500">{{ $row->maker_name }}</div>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-slate-600">{{ $row->category_label }}</td>
                            <td class="px-4 py-3 text-slate-700">{{ $row->size }}</td>
                            <td class="px-4 py-3 text-right font-semibold text-slate-900">{{ $row->quantity }}</td>
                            <td class="px-4 py-3 text-right text-slate-700">{{ $row->holder_count }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-10 text-center text-slate-500">{{ __('pages.no_active_loans_in_department') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="bg-white border border-slate-200 rounded-xl shadow-sm overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-200 bg-slate-50/80">
            <h2 class="text-[15px] font-semibold text-slate-900">{{ __('pages.loans_by_employee') }}</h2>
            <p class="text-[12px] text-slate-500 mt-0.5">{{ __('pages.loans_by_employee_desc') }}</p>
        </div>
        <div class="divide-y divide-slate-100">
            @forelse($inventory['by_employee'] as $row)
                <div class="p-4 sm:p-5">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 mb-3">
                        <div>
                            <a href="{{ $employeeShowRoute($row['employee']) }}" class="text-[15px] font-semibold text-primary-700 hover:underline">
                                {{ $row['employee']?->name ?? '-' }}
                            </a>
                            <span class="text-[12px] text-slate-500 font-mono ml-2">{{ $row['employee']?->staff_no ?? '' }}</span>
                        </div>
                        <span class="text-[13px] text-slate-600">
                            {{ __('pages.active_loan_pieces') }}: <strong>{{ $row['total_quantity'] }}</strong>
                        </span>
                    </div>
                    <x-active-loans-table :loans="$row['loans']" />
                </div>
            @empty
                <div class="px-4 py-10 text-center text-slate-500">{{ __('pages.no_active_loans_in_department') }}</div>
            @endforelse
        </div>
    </div>
@endsection
