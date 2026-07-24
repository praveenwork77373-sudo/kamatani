@extends('layouts.app')

@section('title', __('pages.billing_data_detail') . ' | ' . config('app.name'))
@section('page_title', __('pages.billing_data_detail'))

@section('content')
    @php
        $data = $snapshot->data ?? [];
        $summary = $data['summary'] ?? [
            'total_targets' => 0,
            'total_loan_points' => 0,
            'return_points' => 0,
            'cleaning_count' => 0,
            'billing_amount' => 0,
            'employees_with_loans' => 0,
            'unit_price' => 0,
        ];
        $contract = $data['contract'] ?? null;
        $departmentBreakdown = $data['department_breakdown'] ?? [];
        $itemBreakdown = $data['item_breakdown'] ?? [];
        $invoice = $data['invoice'] ?? [
            'invoice_number' => '-',
            'issue_date' => '-',
            'payment_due_date' => '-',
            'created_at_formatted' => $snapshot->created_at->format('Y年n月j日 G時i分'),
            'subtotal' => 0,
            'tax_rate' => 10,
            'tax_amount' => 0,
            'total_amount' => 0,
        ];
        $targetMonth = $data['target_month'] ?? \Carbon\Carbon::parse($snapshot->target_date)->format('Y年n月');
    @endphp

    {{-- Header --}}
    <div class="mb-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-slate-900 tracking-tight">{{ __('pages.generated_billing_data') }} ({{ $targetMonth }}{{ __('pages.month') }})</h1>
                <div class="mt-2 flex items-center gap-4 text-[14px] text-slate-600">
                    <div class="flex items-center gap-2">
                        <i data-lucide="building" class="w-4 h-4 text-slate-400"></i>
                        <span class="font-medium">{{ $snapshot->medicalInstitution?->name ?? '-' }}</span>
                    </div>
                    @if(count($departmentBreakdown) > 0)
                        <div class="flex items-center gap-2">
                            <i data-lucide="building-2" class="w-4 h-4 text-slate-400"></i>
                            <span class="font-medium">{{ count($departmentBreakdown) }}部署</span>
                            <span class="text-slate-400">|</span>
                            <span class="text-slate-500">
                                @foreach(array_slice(array_column($departmentBreakdown, 'name'), 0, 3) as $deptName)
                                    {{ $deptName }}@if(!$loop->last)、@endif
                                @endforeach
                                @if(count($departmentBreakdown) > 3)
                                    ...他{{ count($departmentBreakdown) - 3 }}部署
                                @endif
                            </span>
                        </div>
                    @endif
                </div>
            </div>
            <div class="flex items-center gap-3">
                <button onclick="exportToExcel()" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-lg bg-primary-600 hover:bg-primary-700 text-white text-[14px] font-medium shadow-md transition-all">
                    <i data-lucide="download" class="w-4 h-4"></i>
                    {{ __('pages.excel_export') }}
                </button>
                <a href="{{ route('admin.billing.index') }}" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-lg border border-slate-300 text-slate-700 hover:bg-slate-50 text-[14px] font-medium transition-all">
                    <i data-lucide="arrow-left" class="w-4 h-4"></i>
                    {{ __('pages.past_billing_history') }}
                </a>
            </div>
        </div>
    </div>

    {{-- Summary Cards with Lighter Gradients --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 mb-6">
        {{-- 対象員数 Card --}}
        <div class="relative overflow-hidden rounded-lg bg-gradient-to-br from-blue-50/50 via-blue-50/30 to-blue-50/50 border border-blue-100/50 p-3 shadow-sm hover:shadow transition-all duration-200">
            <div class="relative z-10">
                <div class="flex items-center justify-between mb-2">
                    <div class="h-8 w-8 rounded-lg bg-blue-100/50 flex items-center justify-center">
                        <i data-lucide="users" class="w-4 h-4 text-blue-600"></i>
                    </div>
                    <span class="px-1.5 py-0.5 rounded-full bg-blue-100/50 text-blue-700 text-[10px] font-semibold">在籍中</span>
                </div>
                <p class="text-2xl font-bold text-slate-900 mb-0.5">{{ number_format($summary['total_targets'] ?? 0) }}{{ __('pages.people') }}</p>
                <p class="text-slate-600 text-[12px] font-medium">{{ __('pages.number_of_targets') }}</p>
            </div>
        </div>
        
        {{-- 総与点数 Card --}}
        <div class="relative overflow-hidden rounded-lg bg-gradient-to-br from-emerald-50/50 via-emerald-50/30 to-emerald-50/50 border border-emerald-100/50 p-3 shadow-sm hover:shadow transition-all duration-200">
            <div class="relative z-10">
                <div class="flex items-center justify-between mb-2">
                    <div class="h-8 w-8 rounded-lg bg-emerald-100/50 flex items-center justify-center">
                        <i data-lucide="package" class="w-4 h-4 text-emerald-600"></i>
                    </div>
                    <span class="px-1.5 py-0.5 rounded-full bg-emerald-100/50 text-emerald-700 text-[10px] font-semibold">貸与中</span>
                </div>
                <p class="text-2xl font-bold text-slate-900 mb-0.5">{{ number_format($summary['total_loan_points'] ?? $summary['return_points'] ?? 0) }}{{ __('pages.points') }}</p>
                <p class="text-slate-600 text-[12px] font-medium">{{ __('pages.total_loan_points') }} ({{ __('pages.items_on_loan') }})</p>
            </div>
        </div>
        
        {{-- クリーニング回数 Card --}}
        <div class="relative overflow-hidden rounded-lg bg-gradient-to-br from-purple-50/50 via-purple-50/30 to-purple-50/50 border border-purple-100/50 p-3 shadow-sm hover:shadow transition-all duration-200">
            <div class="relative z-10">
                <div class="flex items-center justify-between mb-2">
                    <div class="h-8 w-8 rounded-lg bg-purple-100/50 flex items-center justify-center">
                        <i data-lucide="refresh-cw" class="w-4 h-4 text-purple-600"></i>
                    </div>
                    <span class="px-1.5 py-0.5 rounded-full bg-purple-100/50 text-purple-700 text-[10px] font-semibold">処理済み</span>
                </div>
                <p class="text-2xl font-bold text-slate-900 mb-0.5">{{ number_format($summary['cleaning_count'] ?? 0) }}{{ __('pages.times') }}</p>
                <p class="text-slate-600 text-[12px] font-medium">{{ __('pages.cleaning_count') }}</p>
            </div>
        </div>
        
        {{-- 請求金額 Card --}}
        <div class="relative overflow-hidden rounded-lg bg-gradient-to-br from-amber-50/50 via-amber-50/30 to-amber-50/50 border border-amber-100/50 p-3 shadow-sm hover:shadow transition-all duration-200">
            <div class="relative z-10">
                <div class="flex items-center justify-between mb-2">
                    <div class="h-8 w-8 rounded-lg bg-amber-100/50 flex items-center justify-center">
                        <i data-lucide="yen-sign" class="w-4 h-4 text-amber-600"></i>
                    </div>
                    <span class="px-1.5 py-0.5 rounded-full bg-amber-100/50 text-amber-700 text-[10px] font-semibold">税抜</span>
                </div>
                <p class="text-2xl font-bold text-slate-900 mb-0.5">¥{{ number_format($summary['billing_amount'] ?? 0) }}</p>
                <p class="text-slate-600 text-[12px] font-medium">{{ __('pages.billing_amount_excl_tax') }}</p>
                @if(($summary['unit_price'] ?? 0) > 0)
                <div class="mt-2 p-2 bg-slate-50/50 rounded-lg border border-slate-200/50">
                    <div class="flex items-center gap-1.5 text-slate-700 text-[11px] mb-1">
                        <i data-lucide="calculator" class="w-3.5 h-3.5 text-slate-500"></i>
                        <span class="font-semibold">{{ __('pages.billing_calculation') }}</span>
                    </div>
                    <div class="text-[11px] text-slate-600 font-medium">
                        @if($contract && ($contract['billing_model'] ?? '') === 'fixed_per_person')
                            {{ number_format($summary['total_targets'] ?? 0) }}{{ __('pages.people') }} × ¥{{ number_format($summary['unit_price'] ?? 0) }} = <span class="text-primary-700 font-bold">¥{{ number_format($summary['billing_amount'] ?? 0) }}</span>
                        @else
                            {{ number_format($summary['return_points'] ?? 0) }}{{ __('pages.points') }} × ¥{{ number_format($summary['unit_price'] ?? 0) }} = <span class="text-primary-700 font-bold">¥{{ number_format($summary['billing_amount'] ?? 0) }}</span>
                        @endif
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Department Breakdown Table --}}
    <div class="bg-white border border-slate-200 rounded-xl shadow-sm overflow-hidden mb-6">
        <div class="px-6 py-4 border-b border-slate-200 bg-gradient-to-r from-slate-50 via-white to-slate-50">
            <h2 class="text-lg font-bold text-slate-900 flex items-center gap-2">
                <i data-lucide="building-2" class="w-5 h-5 text-primary-600"></i>
                {{ __('pages.department_breakdown') }}
            </h2>
            <p class="text-[13px] text-slate-600 mt-1">{{ __('pages.department_breakdown_description') ?? '各部署別の請求内訳を表示します。' }}</p>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full text-[14px]">
                <thead class="bg-gradient-to-r from-slate-50 to-slate-100 border-b-2 border-slate-200">
                    <tr>
                        <th class="px-5 py-4 text-left font-bold text-slate-700">
                            <div class="flex items-center gap-2">
                                <i data-lucide="building-2" class="w-4 h-4 text-slate-500"></i>
                                {{ __('pages.department_name') }}
                            </div>
                        </th>
                        <th class="px-5 py-4 text-right font-bold text-slate-700">
                            <div class="flex items-center justify-end gap-2">
                                <i data-lucide="users" class="w-4 h-4 text-slate-500"></i>
                                {{ __('pages.number_of_targets') }}
                            </div>
                        </th>
                        <th class="px-5 py-4 text-right font-bold text-slate-700">
                            <div class="flex items-center justify-end gap-2">
                                <i data-lucide="package" class="w-4 h-4 text-slate-500"></i>
                                {{ __('pages.total_loan_points') }}
                            </div>
                        </th>
                        <th class="px-5 py-4 text-right font-bold text-slate-700">
                            <div class="flex items-center justify-end gap-2">
                                <i data-lucide="refresh-cw" class="w-4 h-4 text-slate-500"></i>
                                {{ __('pages.cleaning_count') }}
                            </div>
                        </th>
                        <th class="px-5 py-4 text-right font-bold text-slate-700">
                            <div class="flex items-center justify-end gap-2">
                                <i data-lucide="yen-sign" class="w-4 h-4 text-slate-500"></i>
                                {{ __('pages.billing_amount') }}
                            </div>
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($departmentBreakdown as $dept)
                    <tr class="hover:bg-gradient-to-r hover:from-slate-50 hover:to-white transition-colors">
                        <td class="px-5 py-4 font-semibold text-slate-900">
                            <div class="flex items-center gap-2">
                                <i data-lucide="folder" class="w-4 h-4 text-slate-400"></i>
                                <span>{{ $dept['name'] ?? '-' }}</span>
                            </div>
                        </td>
                        <td class="px-5 py-4 text-right text-slate-900 font-medium">
                            <span class="inline-flex items-center gap-1">
                                {{ number_format($dept['employee_count'] ?? 0) }}
                                <span class="text-[12px] text-slate-500">{{ __('pages.people') }}</span>
                            </span>
                        </td>
                        <td class="px-5 py-4 text-right text-slate-900 font-medium">
                            <span class="inline-flex items-center gap-1">
                                {{ number_format($dept['return_points'] ?? 0) }}
                                <span class="text-[12px] text-slate-500">{{ __('pages.points') }}</span>
                            </span>
                        </td>
                        <td class="px-5 py-4 text-right text-slate-900 font-medium">
                            <span class="inline-flex items-center gap-1">
                                {{ number_format($dept['cleaning_count'] ?? 0) }}
                                <span class="text-[12px] text-slate-500">{{ __('pages.times') }}</span>
                            </span>
                        </td>
                        <td class="px-5 py-4 text-right font-bold text-slate-900">
                            <span class="inline-flex items-center gap-1 text-primary-700">
                                <i data-lucide="yen-sign" class="w-3.5 h-3.5"></i>
                                {{ number_format($dept['billing_amount'] ?? 0) }}
                            </span>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-5 py-8 text-center text-slate-500">
                            <div class="flex flex-col items-center gap-2">
                                <i data-lucide="building-2" class="w-8 h-8 text-slate-300"></i>
                                <span>{{ __('pages.no_department_data') }}</span>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                    <tr class="bg-gradient-to-r from-slate-100 to-slate-50 font-bold border-t-2 border-slate-300">
                        <td class="px-5 py-4 text-slate-900">
                            <div class="flex items-center gap-2">
                                <i data-lucide="calculator" class="w-4 h-4 text-slate-600"></i>
                                <span>{{ __('pages.total') }}</span>
                            </div>
                        </td>
                        <td class="px-5 py-4 text-right text-slate-900">
                            <span class="inline-flex items-center gap-1">
                                {{ number_format($summary['total_targets'] ?? 0) }}
                                <span class="text-[12px] text-slate-500">{{ __('pages.people') }}</span>
                            </span>
                        </td>
                        <td class="px-5 py-4 text-right text-slate-900">
                            <span class="inline-flex items-center gap-1 font-semibold">
                                {{ number_format($summary['total_loan_points'] ?? $summary['return_points'] ?? 0) }}
                                <span class="text-[12px] text-slate-500">{{ __('pages.points') }}</span>
                            </span>
                        </td>
                        <td class="px-5 py-4 text-right text-slate-900">
                            <span class="inline-flex items-center gap-1">
                                {{ number_format($summary['cleaning_count'] ?? 0) }}
                                <span class="text-[12px] text-slate-500">{{ __('pages.times') }}</span>
                            </span>
                        </td>
                        <td class="px-5 py-4 text-right text-slate-900">
                            <span class="inline-flex items-center gap-1 text-primary-700 font-bold">
                                <i data-lucide="yen-sign" class="w-3.5 h-3.5"></i>
                                ¥{{ number_format($summary['billing_amount'] ?? 0) }}
                            </span>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    {{-- Item Breakdown --}}
    <div class="bg-white border border-slate-200 rounded-xl shadow-sm overflow-hidden mb-6">
        <div class="px-6 py-4 border-b border-slate-200 bg-gradient-to-r from-slate-50 via-white to-slate-50">
            <h2 class="text-lg font-bold text-slate-900 flex items-center gap-2">
                <i data-lucide="package" class="w-5 h-5 text-primary-600"></i>
                {{ __('pages.item_breakdown') }}
            </h2>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full text-[14px]">
                <thead class="bg-gradient-to-r from-slate-50 to-slate-100 border-b-2 border-slate-200">
                    <tr>
                        <th class="px-5 py-4 text-left font-bold text-slate-700">{{ __('pages.product_code') }}</th>
                        <th class="px-5 py-4 text-left font-bold text-slate-700">{{ __('pages.product_name') }}</th>
                        <th class="px-5 py-4 text-left font-bold text-slate-700">{{ __('pages.category') }}</th>
                        <th class="px-5 py-4 text-left font-bold text-slate-700">{{ __('pages.size') }}</th>
                        <th class="px-5 py-4 text-left font-bold text-slate-700">{{ __('pages.gender') }}</th>
                        <th class="px-5 py-4 text-right font-bold text-slate-700">{{ __('pages.quantity') }}</th>
                        <th class="px-5 py-4 text-left font-bold text-slate-700">{{ __('pages.applications') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($itemBreakdown as $item)
                    <tr class="hover:bg-gradient-to-r hover:from-slate-50 hover:to-white transition-colors">
                        <td class="px-5 py-4 font-medium text-slate-900">{{ $item['product_code'] ?? '-' }}</td>
                        <td class="px-5 py-4 font-semibold text-slate-900">{{ $item['product_name'] ?? '-' }}</td>
                        <td class="px-5 py-4 text-slate-700">
                            @if(isset($item['category']))
                                @if($item['category'] === 'top')
                                    {{ __('pages.category_top') }}
                                @elseif($item['category'] === 'bottom')
                                    {{ __('pages.category_bottom') }}
                                @elseif($item['category'] === 'shoe')
                                    {{ __('pages.category_shoe') }}
                                @else
                                    {{ $item['category'] }}
                                @endif
                            @else
                                -
                            @endif
                        </td>
                        <td class="px-5 py-4 text-slate-700">{{ $item['size'] ?? '-' }}</td>
                        <td class="px-5 py-4 text-slate-700">
                            @if(isset($item['gender']))
                                @if($item['gender'] === 'male')
                                    男性
                                @elseif($item['gender'] === 'female')
                                    女性
                                @elseif($item['gender'] === 'unisex')
                                    ユニセックス
                                @else
                                    {{ $item['gender'] }}
                                @endif
                            @else
                                -
                            @endif
                        </td>
                        <td class="px-5 py-4 text-right font-bold text-slate-900">{{ number_format($item['quantity'] ?? 0) }}{{ __('pages.items') }}</td>
                        <td class="px-5 py-4">
                            @if(!empty($item['applications']) && is_array($item['applications']))
                                <div class="space-y-1">
                                    @foreach(array_slice($item['applications'], 0, 3) as $app)
                                        <div class="text-[12px] text-slate-600">
                                            <span class="font-medium">#{{ str_pad($app['application_id'] ?? '', 4, '0', STR_PAD_LEFT) }}</span>
                                            @if(isset($app['employee_name']))
                                                <span class="text-slate-500"> - {{ $app['employee_name'] }}</span>
                                            @endif
                                            <span class="text-slate-500"> ({{ $app['quantity'] ?? 0 }}{{ __('pages.items') }})</span>
                                        </div>
                                    @endforeach
                                    @if(count($item['applications']) > 3)
                                        <div class="text-[11px] text-slate-500 italic">
                                            +{{ count($item['applications']) - 3 }}件
                                        </div>
                                    @endif
                                </div>
                            @else
                                <span class="text-slate-400">-</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-5 py-8 text-center text-slate-500">{{ __('pages.no_item_data') }}</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Notes/Precautions --}}
    <div class="bg-gradient-to-br from-blue-50/50 via-blue-50/30 to-blue-50/50 border border-blue-200/50 rounded-xl p-5 mb-6 shadow-sm">
        <div class="flex items-start gap-3">
            <div class="w-9 h-9 rounded-lg bg-blue-100/50 flex items-center justify-center flex-shrink-0">
                <i data-lucide="info" class="w-5 h-5 text-blue-600"></i>
            </div>
            <div class="flex-1">
                <h3 class="text-[15px] font-bold text-slate-900 mb-3 flex items-center gap-2">
                    <i data-lucide="file-text" class="w-4 h-4 text-slate-500"></i>
                    {{ __('pages.notes_precautions') }}
                </h3>
                <ul class="space-y-2.5 text-[13px] text-slate-700">
                    <li class="flex items-start gap-2.5">
                        <span class="text-blue-500 mt-0.5 font-bold">・</span>
                        <span>{{ __('pages.billing_note_1') }}</span>
                    </li>
                    <li class="flex items-start gap-2.5">
                        <span class="text-blue-500 mt-0.5 font-bold">・</span>
                        <span>{{ __('pages.billing_note_2') }}</span>
                    </li>
                    <li class="flex items-start gap-2.5">
                        <span class="text-blue-500 mt-0.5 font-bold">・</span>
                        <span>{{ __('pages.billing_note_3') }}</span>
                    </li>
                    <li class="flex items-start gap-2.5">
                        <span class="text-blue-500 mt-0.5 font-bold">・</span>
                        <span>{{ __('pages.billing_note_4') }}</span>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    {{-- Invoice Information --}}
    <div class="bg-white border border-slate-200 rounded-xl shadow-sm overflow-hidden mb-6">
        <div class="px-6 py-4 border-b border-slate-200 bg-gradient-to-r from-slate-50 via-white to-slate-50">
            <h2 class="text-lg font-bold text-slate-900 flex items-center gap-2">
                <i data-lucide="receipt-text" class="w-5 h-5 text-primary-600"></i>
                {{ __('pages.invoice_information') }}
            </h2>
        </div>
        <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                {{-- Amount Breakdown --}}
                <div class="space-y-4 bg-gradient-to-br from-slate-50 to-white rounded-lg p-5 border border-slate-200">
                    <div class="pb-3 border-b-2 border-slate-200">
                        <dt class="text-[12px] text-slate-500 mb-1.5 font-medium flex items-center gap-1.5">
                            <i data-lucide="calculator" class="w-3.5 h-3.5"></i>
                            {{ __('pages.subtotal_excl_tax') }}
                        </dt>
                        <dd class="text-2xl font-bold text-slate-900">¥{{ number_format($invoice['subtotal'] ?? 0) }}</dd>
                    </div>
                    <div class="pb-3 border-b border-slate-200">
                        <dt class="text-[12px] text-slate-500 mb-1.5 font-medium flex items-center gap-1.5">
                            <i data-lucide="percent" class="w-3.5 h-3.5"></i>
                            {{ __('pages.consumption_tax') }} ({{ $invoice['tax_rate'] ?? 10 }}%)
                        </dt>
                        <dd class="text-xl font-semibold text-slate-700">¥{{ number_format($invoice['tax_amount'] ?? 0) }}</dd>
                    </div>
                    <div>
                        <dt class="text-[12px] text-slate-500 mb-1.5 font-medium flex items-center gap-1.5">
                            <i data-lucide="yen-sign" class="w-3.5 h-3.5"></i>
                            {{ __('pages.total_amount_incl_tax') }}
                        </dt>
                        <dd class="text-3xl font-bold bg-gradient-to-r from-primary-600 to-primary-700 bg-clip-text text-transparent">¥{{ number_format($invoice['total_amount'] ?? 0) }}</dd>
                    </div>
                </div>
                
                {{-- Invoice Details --}}
                <div class="space-y-4 bg-gradient-to-br from-blue-50/30 to-white rounded-lg p-5 border border-blue-100">
                    <div class="flex items-center gap-2 mb-3 pb-2 border-b border-blue-100">
                        <i data-lucide="file-text" class="w-4 h-4 text-blue-600"></i>
                        <span class="text-[13px] font-semibold text-slate-700">{{ __('pages.invoice_details') }}</span>
                    </div>
                    <div>
                        <dt class="text-[12px] text-slate-500 mb-1.5 font-medium flex items-center gap-1.5">
                            <i data-lucide="hash" class="w-3.5 h-3.5"></i>
                            {{ __('pages.invoice_number') }}
                        </dt>
                        <dd class="text-[14px] font-bold text-slate-900">{{ $invoice['invoice_number'] ?? '-' }}</dd>
                    </div>
                    <div>
                        <dt class="text-[12px] text-slate-500 mb-1.5 font-medium flex items-center gap-1.5">
                            <i data-lucide="calendar" class="w-3.5 h-3.5"></i>
                            {{ __('pages.issue_date') }}
                        </dt>
                        <dd class="text-[14px] text-slate-900">{{ $invoice['issue_date'] ?? '-' }}</dd>
                    </div>
                    <div>
                        <dt class="text-[12px] text-slate-500 mb-1.5 font-medium flex items-center gap-1.5">
                            <i data-lucide="clock" class="w-3.5 h-3.5"></i>
                            {{ __('pages.payment_due_date') }}
                        </dt>
                        <dd class="text-[14px] text-slate-900">{{ $invoice['payment_due_date'] ?? '-' }}</dd>
                    </div>
                    <div>
                        <dt class="text-[12px] text-slate-500 mb-1.5 font-medium flex items-center gap-1.5">
                            <i data-lucide="calendar-clock" class="w-3.5 h-3.5"></i>
                            {{ __('pages.created_at') }}
                        </dt>
                        <dd class="text-[14px] text-slate-900">{{ $invoice['created_at_formatted'] ?? $snapshot->created_at->format('Y年n月j日 G時i分') }}</dd>
                    </div>
                </div>
            </div>
            
            <div class="flex items-center justify-end gap-3 pt-5 border-t border-slate-200">
                <button onclick="window.print()" class="inline-flex items-center gap-2 px-6 py-3 rounded-lg border-2 border-slate-300 text-slate-700 hover:bg-slate-50 hover:border-slate-400 text-[14px] font-semibold transition-all shadow-sm">
                    <i data-lucide="printer" class="w-4 h-4"></i>
                    {{ __('pages.print_preview') }}
                </button>
                <button onclick="confirmBilling()" class="inline-flex items-center gap-2 px-7 py-3 rounded-lg bg-gradient-to-r from-primary-600 to-primary-700 hover:from-primary-700 hover:to-primary-800 text-white text-[14px] font-bold shadow-lg hover:shadow-xl transition-all transform hover:scale-105">
                    <i data-lucide="check-circle" class="w-5 h-5"></i>
                    {{ __('pages.confirm_billing_data') }}
                </button>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        // Initialize Lucide icons
        document.addEventListener('DOMContentLoaded', function() {
            if (typeof lucide !== 'undefined') {
                lucide.createIcons();
            }
        });

        function exportToExcel() {
            // TODO: Implement Excel export
            const message = '{{ app()->getLocale() === "ja" ? "Excelエクスポート機能は近日実装予定です。" : "Excel export functionality will be implemented soon." }}';
            alert(message);
        }

        function confirmBilling() {
            const confirmMessage = '{{ app()->getLocale() === "ja" ? "請求データを確定しますか？" : "Do you want to confirm this billing data?" }}';
            const successMessage = '{{ app()->getLocale() === "ja" ? "請求データを確定しました。" : "Billing data has been confirmed." }}';
            
            if (confirm(confirmMessage)) {
                // TODO: Implement billing confirmation API call
                alert(successMessage);
            }
        }
    </script>
    @endpush
@endsection

