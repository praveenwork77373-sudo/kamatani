@extends('layouts.app')

@section('title', __('pages.billing_data') . ' | ' . config('app.name'))
@section('page_title', __('pages.billing_data'))

@section('content')
    <div class="mb-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-slate-900 tracking-tight flex items-center gap-2">
                    <i data-lucide="receipt" class="w-6 h-6 text-primary-600"></i>
                    {{ __('pages.billing_data_list') }}
                </h1>
                <p class="text-[14px] text-slate-600 mt-1.5">{{ __('pages.billing_data_list_description') }}</p>
            </div>
            <a href="{{ route('admin.billing.create') }}" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-lg bg-primary-600 hover:bg-primary-700 text-white text-[14px] font-medium shadow-md transition-all">
                <i data-lucide="plus" class="w-4 h-4"></i>
                {{ __('pages.generate_new') }}
            </a>
        </div>
    </div>

    @if(session('status'))
        <div class="mb-6 p-4 bg-emerald-50 border-l-4 border-emerald-500 rounded-lg">
            <div class="flex items-center gap-2">
                <i data-lucide="check-circle" class="w-5 h-5 text-emerald-600"></i>
                <span class="text-[14px] text-emerald-800 font-medium">{{ session('status') }}</span>
            </div>
        </div>
    @endif

    {{-- Statistics Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
        {{-- Total Snapshots --}}
        <div class="relative overflow-hidden rounded-xl bg-gradient-to-br from-blue-50 via-blue-100 to-indigo-100 border border-blue-200/50 p-4 shadow-sm hover:shadow-md transition-all duration-300">
            <div class="relative z-10">
                <div class="flex items-center justify-between mb-3">
                    <div class="h-10 w-10 rounded-lg bg-blue-500/10 flex items-center justify-center">
                        <i data-lucide="file-text" class="w-5 h-5 text-blue-600"></i>
                    </div>
                    <span class="px-2 py-0.5 rounded-full bg-blue-500/10 text-blue-700 text-[10px] font-semibold">{{ __('pages.total') }}</span>
                </div>
                <p class="text-3xl font-bold text-slate-900 mb-1">{{ number_format($stats['total_snapshots'] ?? 0) }}</p>
                <p class="text-slate-600 text-[13px] font-medium">{{ __('pages.total_billing_snapshots') }}</p>
            </div>
        </div>

        {{-- This Month Snapshots --}}
        <div class="relative overflow-hidden rounded-xl bg-gradient-to-br from-emerald-50 via-teal-50 to-cyan-50 border border-emerald-200/50 p-4 shadow-sm hover:shadow-md transition-all duration-300">
            <div class="relative z-10">
                <div class="flex items-center justify-between mb-3">
                    <div class="h-10 w-10 rounded-lg bg-emerald-500/10 flex items-center justify-center">
                        <i data-lucide="calendar" class="w-5 h-5 text-emerald-600"></i>
                    </div>
                    <span class="px-2 py-0.5 rounded-full bg-emerald-500/10 text-emerald-700 text-[10px] font-semibold">今月</span>
                </div>
                <p class="text-3xl font-bold text-slate-900 mb-1">{{ number_format($stats['this_month_snapshots'] ?? 0) }}</p>
                <p class="text-slate-600 text-[13px] font-medium">今月の生成数</p>
            </div>
        </div>

        {{-- Total Billing Amount --}}
        <div class="relative overflow-hidden rounded-xl bg-gradient-to-br from-purple-50 via-pink-50 to-rose-50 border border-purple-200/50 p-4 shadow-sm hover:shadow-md transition-all duration-300">
            <div class="relative z-10">
                <div class="flex items-center justify-between mb-3">
                    <div class="h-10 w-10 rounded-lg bg-purple-500/10 flex items-center justify-center">
                        <i data-lucide="yen-sign" class="w-5 h-5 text-purple-600"></i>
                    </div>
                    <span class="px-2 py-0.5 rounded-full bg-purple-500/10 text-purple-700 text-[10px] font-semibold">{{ __('pages.total') }}</span>
                </div>
                <p class="text-3xl font-bold text-slate-900 mb-1">¥{{ number_format($stats['total_billing_amount'] ?? 0) }}</p>
                <p class="text-slate-600 text-[13px] font-medium">{{ __('pages.total_billing_amount') }}</p>
            </div>
        </div>

        {{-- This Month Billing Amount --}}
        <div class="relative overflow-hidden rounded-xl bg-gradient-to-br from-amber-50 via-orange-50 to-red-50 border border-amber-200/50 p-4 shadow-sm hover:shadow-md transition-all duration-300">
            <div class="relative z-10">
                <div class="flex items-center justify-between mb-3">
                    <div class="h-10 w-10 rounded-lg bg-amber-500/10 flex items-center justify-center">
                        <i data-lucide="dollar-sign" class="w-5 h-5 text-amber-600"></i>
                    </div>
                    <span class="px-2 py-0.5 rounded-full bg-amber-500/10 text-amber-700 text-[10px] font-semibold">今月</span>
                </div>
                <p class="text-3xl font-bold text-slate-900 mb-1">¥{{ number_format($stats['this_month_billing_amount'] ?? 0) }}</p>
                <p class="text-slate-600 text-[13px] font-medium">今月の請求金額</p>
            </div>
        </div>
    </div>

    {{-- Main Content: Tree Structure + Billing List --}}
    <div class="flex gap-6">
        {{-- Left Sidebar: Organization/Department Tree --}}
        <div class="w-80 flex-shrink-0 bg-white border border-slate-200 rounded-xl shadow-sm overflow-hidden">
            <div class="p-4 border-b border-slate-200 bg-gradient-to-r from-slate-50 to-slate-100">
                <h2 class="text-[15px] font-semibold text-slate-900 flex items-center gap-2">
                    <i data-lucide="building-2" class="w-4 h-4 text-primary-600"></i>
                    {{ __('pages.organizations_departments') }}
                </h2>
            </div>
            <div class="p-4 overflow-y-auto max-h-[calc(100vh-300px)]">
                <div class="space-y-1">
                    @foreach($institutionsWithDepartments as $institution)
                        <div class="org-item" data-org-id="{{ $institution->id }}">
                            <button type="button" class="org-header w-full flex items-center justify-between px-3 py-2.5 rounded-lg hover:bg-slate-50 transition-all text-left" onclick="toggleOrg({{ $institution->id }})">
                                <div class="flex items-center gap-2 flex-1 min-w-0">
                                    <i data-lucide="chevron-right" class="w-4 h-4 text-slate-400 org-chevron transition-transform"></i>
                                    <i data-lucide="building" class="w-4 h-4 text-slate-500 flex-shrink-0"></i>
                                    <span class="text-[14px] font-medium text-slate-900 truncate">{{ $institution->name }}</span>
                                </div>
                                @if(isset($pendingBillingCounts['institution'][$institution->id]) && $pendingBillingCounts['institution'][$institution->id] > 0)
                                    <span class="px-2 py-0.5 rounded-full bg-amber-100 text-amber-700 text-[11px] font-semibold flex-shrink-0">
                                        {{ $pendingBillingCounts['institution'][$institution->id] }}
                                    </span>
                                @endif
                            </button>
                            <div class="org-departments hidden ml-6 mt-1 space-y-1" id="org-{{ $institution->id }}-depts">
                                @foreach($institution->departments as $department)
                                    <button type="button" class="dept-item w-full flex items-center justify-between px-3 py-2 rounded-lg hover:bg-slate-50 transition-all text-left" 
                                            onclick="filterByDept({{ $department->id }}, {{ $institution->id }})"
                                            data-dept-id="{{ $department->id }}"
                                            data-org-id="{{ $institution->id }}">
                                        <div class="flex items-center gap-2 flex-1 min-w-0">
                                            <i data-lucide="folder" class="w-3.5 h-3.5 text-slate-400"></i>
                                            <span class="text-[13px] text-slate-700 truncate">{{ $department->name }}</span>
                                        </div>
                                        @if(isset($pendingBillingCounts['department'][$department->id]) && $pendingBillingCounts['department'][$department->id] > 0)
                                            <span class="px-2 py-0.5 rounded-full bg-amber-100 text-amber-700 text-[11px] font-semibold flex-shrink-0">
                                                {{ $pendingBillingCounts['department'][$department->id] }}
                                            </span>
                                        @endif
                                    </button>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Right Side: Billing List --}}
        <div class="flex-1 bg-white border border-slate-200 rounded-xl shadow-sm overflow-hidden">
            <div class="p-4 border-b border-slate-200 bg-gradient-to-r from-slate-50 to-slate-100">
                <div class="flex items-center justify-between">
                    <h2 class="text-[15px] font-semibold text-slate-900 flex items-center gap-2">
                        <i data-lucide="receipt" class="w-4 h-4 text-primary-600"></i>
                        <span id="billing-list-title">{{ __('pages.billing_list_title') }}</span>
                    </h2>
                    <span class="text-[13px] text-slate-600">
                        {{ __('pages.all_items_showing') }} <span id="billing-count">{{ $snapshots->total() }}</span>{{ __('pages.items_showing') }} {{ $snapshots->firstItem() ?? 0 }}-{{ $snapshots->lastItem() ?? 0 }}{{ __('pages.items_displayed') }}
                    </span>
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full text-[14px]">
                    <thead class="bg-slate-50 border-b border-slate-200">
                        <tr>
                            <th class="px-5 py-3.5 text-left font-semibold text-slate-700">{{ __('pages.target_date_label') }}</th>
                            <th class="px-5 py-3.5 text-left font-semibold text-slate-700">{{ __('pages.medical_institution_label') }}</th>
                            <th class="px-5 py-3.5 text-left font-semibold text-slate-700">{{ __('pages.billing_model_label_table') }}</th>
                            <th class="px-5 py-3.5 text-right font-semibold text-slate-700">{{ __('pages.employee_count') }}</th>
                            <th class="px-5 py-3.5 text-right font-semibold text-slate-700">{{ __('pages.loan_count') }}</th>
                            <th class="px-5 py-3.5 text-right font-semibold text-slate-700">{{ __('pages.billing_amount_label') }}</th>
                            <th class="px-5 py-3.5 text-left font-semibold text-slate-700">{{ __('pages.generated_at_label') }}</th>
                            <th class="px-5 py-3.5 text-right font-semibold text-slate-700">{{ __('pages.actions_label') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200" id="billing-table-body">
                        @forelse($snapshots as $snapshot)
                            @php
                                $data = $snapshot->data;
                                $summary = $data['summary'] ?? [];
                            @endphp
                            <tr class="hover:bg-slate-50 transition-colors">
                                <td class="px-5 py-4 font-semibold text-slate-900">
                                    <div class="flex items-center gap-2">
                                        <i data-lucide="calendar" class="w-4 h-4 text-slate-400"></i>
                                        <span>{{ $snapshot->target_date->format('Y-m-d') }}</span>
                                    </div>
                                </td>
                                <td class="px-5 py-4 text-slate-800">
                                    <div class="flex items-center gap-2">
                                        <i data-lucide="building" class="w-4 h-4 text-slate-400"></i>
                                        <span>{{ $snapshot->medicalInstitution?->name ?? '-' }}</span>
                                    </div>
                                </td>
                                <td class="px-5 py-4 text-slate-600">
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[12px] font-medium {{ ($data['contract']['billing_model'] ?? '') === 'fixed_per_person' ? 'bg-emerald-100 text-emerald-700 border border-emerald-200' : 'bg-blue-100 text-blue-700 border border-blue-200' }}">
                                        <i data-lucide="{{ ($data['contract']['billing_model'] ?? '') === 'fixed_per_person' ? 'dollar-sign' : 'coins' }}" class="w-3.5 h-3.5"></i>
                                        {{ ($data['contract']['billing_model'] ?? '') === 'fixed_per_person' ? __('pages.fixed_per_person') : __('pages.point_based') }}
                                    </span>
                                </td>
                                <td class="px-5 py-4 text-right text-slate-900 font-medium">
                                    <div class="flex items-center justify-end gap-2">
                                        <i data-lucide="users" class="w-4 h-4 text-slate-400"></i>
                                        <span>{{ $summary['total_targets'] ?? 0 }}</span>
                                    </div>
                                </td>
                                <td class="px-5 py-4 text-right text-slate-900 font-medium">
                                    <div class="flex items-center justify-end gap-2">
                                        <i data-lucide="package" class="w-4 h-4 text-slate-400"></i>
                                        <span>{{ $summary['total_loan_points'] ?? 0 }}</span>
                                    </div>
                                </td>
                                <td class="px-5 py-4 text-right font-semibold text-slate-900">
                                    <div class="flex items-center justify-end gap-1">
                                        <i data-lucide="yen-sign" class="w-4 h-4 text-slate-500"></i>
                                        <span>¥{{ number_format($data['billing_amount'] ?? 0) }}</span>
                                    </div>
                                </td>
                                <td class="px-5 py-4 text-slate-600">
                                    <div class="flex items-center gap-2">
                                        <i data-lucide="clock" class="w-4 h-4 text-slate-400"></i>
                                        <span>{{ $snapshot->created_at->format('Y/m/d H:i') }}</span>
                                    </div>
                                </td>
                                <td class="px-5 py-4 text-right">
                                    <a href="{{ route('admin.billing.show', $snapshot) }}"
                                       class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-lg border border-slate-300 text-slate-700 hover:bg-slate-50 hover:border-slate-400 text-[13px] font-medium transition-all">
                                        <i data-lucide="eye" class="w-4 h-4"></i>
                                        {{ __('pages.details') }}
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-5 py-12 text-center">
                                    <div class="flex flex-col items-center gap-3">
                                        <div class="w-16 h-16 rounded-full bg-slate-100 flex items-center justify-center">
                                            <i data-lucide="file-text" class="w-8 h-8 text-slate-300"></i>
                                        </div>
                                        <p class="text-slate-500 text-[14px] font-medium">{{ __('pages.no_billing_data') }}</p>
                                        <a href="{{ route('admin.billing.create') }}" class="text-primary-600 hover:text-primary-700 text-[13px] font-medium">
                                            {{ __('pages.generate_new_billing') }}
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($snapshots->hasPages())
                <div class="px-5 py-4 border-t border-slate-200 bg-slate-50">
                    <div class="flex items-center justify-between text-[13px] text-slate-600">
                        <span>{{ __('pages.all_items_showing') }} {{ $snapshots->total() }} {{ __('pages.items_showing') }} {{ $snapshots->firstItem() ?? 0 }} - {{ $snapshots->lastItem() ?? 0 }} {{ __('pages.items_displayed') }}</span>
                        <div class="flex items-center gap-2">
                            {{ $snapshots->onEachSide(1)->links('pagination::tailwind') }}
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>

    <script>
        function toggleOrg(orgId) {
            const deptContainer = document.getElementById(`org-${orgId}-depts`);
            const chevron = event.currentTarget.querySelector('.org-chevron');
            
            if (deptContainer) {
                deptContainer.classList.toggle('hidden');
                chevron.classList.toggle('rotate-90');
            }
        }

        function filterByDept(deptId, orgId) {
            // Remove active class from all items
            document.querySelectorAll('.dept-item, .org-header').forEach(item => {
                item.classList.remove('bg-primary-50', 'border-primary-200', 'border');
            });
            
            // Add active class to selected department
            const selectedDept = document.querySelector(`[data-dept-id="${deptId}"]`);
            if (selectedDept) {
                selectedDept.classList.add('bg-primary-50', 'border-primary-200', 'border');
            }
            
            // Reload page with filter
            const url = new URL(window.location.href);
            url.searchParams.set('department_id', deptId);
            url.searchParams.set('medical_institution_id', orgId);
            url.searchParams.delete('page');
            window.location.href = url.toString();
        }

        // Auto-expand org if filtered
        @if(request('medical_institution_id'))
            const orgId = {{ request('medical_institution_id') }};
            const deptContainer = document.getElementById(`org-${orgId}-depts`);
            if (deptContainer) {
                deptContainer.classList.remove('hidden');
                const chevron = document.querySelector(`[data-org-id="${orgId}"] .org-chevron`);
                if (chevron) {
                    chevron.classList.add('rotate-90');
                }
            }
        @endif

        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
    </script>
@endsection

