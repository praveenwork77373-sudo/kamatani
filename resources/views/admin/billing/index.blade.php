@extends('layouts.app')

@section('title', __('pages.billing_data') . ' | ' . config('app.name'))
@section('page_title', __('pages.billing_data'))

@section('content')
    @php
        $user = auth()->user();
        $admin = $user->adminUser ?? null;
        $adminRole = $admin->role ?? null;
        $canManageBilling = in_array($adminRole, ['system_admin', 'operation', 'accounting']);
    @endphp
    
    <div class="mb-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-slate-900 tracking-tight flex items-center gap-2">
                    <i data-lucide="receipt" class="w-6 h-6 text-primary-600"></i>
                    {{ __('pages.billing_approval') }}
                </h1>
                <p class="text-[14px] text-slate-600 mt-1.5">{{ __('pages.billing_approval_description') }}</p>
            </div>
            @if($canManageBilling)
                <a href="{{ route('admin.billing.create') }}" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-lg bg-gradient-to-r from-primary-600 to-primary-700 hover:from-primary-700 hover:to-primary-800 text-white text-[14px] font-semibold shadow-lg transition-all">
                    <i data-lucide="plus" class="w-4 h-4"></i>
                    {{ __('pages.generate_billing_data') }}
                </a>
            @endif
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
    <x-stat-grid :cols="4">
        <x-stat-card tone="amber" icon="clock" :value="$stats['total_billing_snapshots'] ?? 0" :label="__('pages.billing_snapshots')" :badge="__('pages.total')" />
        <x-stat-card tone="blue" icon="calendar" :value="$stats['this_month_billing_snapshots'] ?? 0" :label="__('pages.this_month_billing_snapshots')" badge="今月" />
        <x-stat-card tone="purple" icon="japanese-yen" :value="$stats['total_billing_amount'] ?? 0" :label="__('pages.total_billing_amount')" :badge="__('pages.total')" prefix="¥" />
        <x-stat-card tone="emerald" icon="japanese-yen" :value="$stats['this_month_billing_amount'] ?? 0" :label="__('pages.this_month_billing_amount')" badge="今月" prefix="¥" />
    </x-stat-grid>

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
                    @forelse($institutionsWithDepartments ?? [] as $institution)
                        <div class="org-item" data-org-id="{{ $institution->id }}">
                            <button type="button" class="org-header w-full flex items-center justify-between px-3 py-2.5 rounded-lg hover:bg-slate-50 transition-all text-left {{ request('medical_institution_id') == $institution->id ? 'bg-primary-50 border border-primary-200' : '' }}" onclick="filterByOrg({{ $institution->id }})">
                                <div class="flex items-center gap-2 flex-1 min-w-0">
                                    <i data-lucide="chevron-right" class="w-4 h-4 text-slate-400 org-chevron transition-transform {{ request('medical_institution_id') == $institution->id ? 'rotate-90' : '' }}"></i>
                                    <i data-lucide="building" class="w-4 h-4 text-slate-500 flex-shrink-0"></i>
                                    <span class="text-[14px] font-medium text-slate-900 truncate">{{ $institution->name }}</span>
                                </div>
                                @if(isset($billingCounts['institution'][$institution->id]) && $billingCounts['institution'][$institution->id] > 0)
                                    <span class="px-2 py-0.5 rounded-full bg-primary-100 text-primary-700 text-[11px] font-semibold flex-shrink-0">
                                        {{ $billingCounts['institution'][$institution->id] }}
                                    </span>
                                @endif
                            </button>
                            <div class="org-departments ml-6 mt-1 space-y-1 {{ request('medical_institution_id') == $institution->id ? '' : 'hidden' }}" id="org-{{ $institution->id }}-depts">
                                @foreach($institution->departments ?? [] as $department)
                                    <button type="button" class="dept-item w-full flex items-center justify-between px-3 py-2 rounded-lg hover:bg-slate-50 transition-all text-left {{ request('department_id') == $department->id && request('medical_institution_id') == $institution->id ? 'bg-primary-50 border border-primary-200' : '' }}" 
                                            onclick="filterByDept({{ $department->id }}, {{ $institution->id }})"
                                            data-dept-id="{{ $department->id }}"
                                            data-org-id="{{ $institution->id }}">
                                        <div class="flex items-center gap-2 flex-1 min-w-0">
                                            <i data-lucide="folder" class="w-3.5 h-3.5 text-slate-400"></i>
                                            <span class="text-[13px] text-slate-700 truncate">{{ $department->name }}</span>
                                        </div>
                                        @if(isset($billingCounts['department'][$department->id]) && $billingCounts['department'][$department->id]['count'] > 0)
                                            <div class="flex items-center gap-1.5 flex-shrink-0">
                                                <span class="px-2 py-0.5 rounded-full bg-blue-100 text-blue-700 text-[10px] font-semibold" title="請求データ数: {{ $billingCounts['department'][$department->id]['count'] }}">
                                                    {{ $billingCounts['department'][$department->id]['count'] }}
                                                </span>
                                            </div>
                                        @endif
                                    </button>
                                @endforeach
                            </div>
                        </div>
                    @empty
                        <div class="p-4 text-center text-slate-500 text-[13px]">
                            <i data-lucide="building-2" class="w-8 h-8 mx-auto mb-2 text-slate-300"></i>
                            <p>医療機関が登録されていません</p>
                        </div>
                    @endforelse
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
                        {{ __('pages.all_items_showing') }} <span id="billing-count">{{ $billingSnapshots->total() }}</span>{{ __('pages.items_showing') }} {{ $billingSnapshots->firstItem() ?? 0 }}-{{ $billingSnapshots->lastItem() ?? 0 }}{{ __('pages.items_displayed') }}
                    </span>
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full text-[14px]">
                    <thead class="bg-slate-50 border-b border-slate-200">
                        <tr>
                            <th class="px-5 py-3.5 text-left font-semibold text-slate-700">{{ __('pages.billing_id') }}</th>
                            <th class="px-5 py-3.5 text-left font-semibold text-slate-700">{{ __('pages.medical_institution_label') }}</th>
                            <th class="px-5 py-3.5 text-left font-semibold text-slate-700">{{ __('pages.department') }}</th>
                            <th class="px-5 py-3.5 text-left font-semibold text-slate-700">{{ __('pages.target_month') }}</th>
                            <th class="px-5 py-3.5 text-left font-semibold text-slate-700">{{ __('pages.invoice_number') }}</th>
                            <th class="px-5 py-3.5 text-right font-semibold text-slate-700">{{ __('pages.billing_amount') }}</th>
                            <th class="px-5 py-3.5 text-left font-semibold text-slate-700">{{ __('pages.generated_at') }}</th>
                            <th class="px-5 py-3.5 text-left font-semibold text-slate-700">{{ __('pages.generated_by') }}</th>
                            <th class="px-5 py-3.5 text-right font-semibold text-slate-700">{{ __('pages.actions_label') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200" id="billing-table-body">
                        @forelse($billingSnapshots as $snapshot)
                            <tr class="hover:bg-slate-50 transition-colors">
                                <td class="px-5 py-4 font-semibold text-slate-900">
                                    <div class="flex items-center gap-2">
                                        <i data-lucide="receipt" class="w-4 h-4 text-slate-400"></i>
                                        <span>#BILL-{{ str_pad($snapshot->id, 4, '0', STR_PAD_LEFT) }}</span>
                                    </div>
                                </td>
                                <td class="px-5 py-4 text-slate-800">
                                    <div class="flex items-center gap-2">
                                        <i data-lucide="building" class="w-4 h-4 text-slate-400"></i>
                                        <span>{{ $snapshot->medicalInstitution?->name ?? '-' }}</span>
                                    </div>
                                </td>
                                <td class="px-5 py-4 text-slate-700">
                                    <div class="flex items-center gap-2">
                                        <i data-lucide="building-2" class="w-4 h-4 text-slate-400"></i>
                                        <span>{{ $snapshot->department?->name ?? ($snapshot->data['department']['name'] ?? '-') }}</span>
                                    </div>
                                </td>
                                <td class="px-5 py-4 text-slate-700">
                                    <div class="flex items-center gap-2">
                                        <i data-lucide="calendar" class="w-4 h-4 text-slate-400"></i>
                                        <span>{{ $snapshot->target_date->format('Y年n月') }}</span>
                                    </div>
                                </td>
                                <td class="px-5 py-4 text-slate-700">
                                    <div class="flex items-center gap-2">
                                        <i data-lucide="file-text" class="w-4 h-4 text-slate-400"></i>
                                        <span>{{ $snapshot->data['invoice']['invoice_number'] ?? '-' }}</span>
                                    </div>
                                </td>
                                <td class="px-5 py-4 text-right text-slate-900 font-semibold">
                                    <div class="flex items-center justify-end gap-2">
                                        <i data-lucide="yen-sign" class="w-4 h-4 text-slate-400"></i>
                                        <span class="text-primary-700">¥{{ number_format($snapshot->data['summary']['billing_amount'] ?? 0) }}</span>
                                    </div>
                                </td>
                                <td class="px-5 py-4 text-slate-600">
                                    <div class="flex items-center gap-2">
                                        <i data-lucide="clock" class="w-4 h-4 text-slate-400"></i>
                                        <span>{{ $snapshot->created_at->format('Y/m/d H:i') }}</span>
                                    </div>
                                </td>
                                <td class="px-5 py-4 text-slate-600">
                                    <div class="flex items-center gap-2">
                                        <i data-lucide="user" class="w-4 h-4 text-slate-400"></i>
                                        <span>{{ $snapshot->generatedBy?->name ?? '-' }}</span>
                                    </div>
                                </td>
                                <td class="px-5 py-4 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <a href="{{ route('admin.billing.show', $snapshot) }}"
                                           class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-slate-300 text-slate-700 hover:bg-slate-50 hover:border-slate-400 text-[13px] font-medium transition-all">
                                            <i data-lucide="eye" class="w-4 h-4"></i>
                                            {{ __('pages.details') }}
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="px-5 py-12 text-center">
                                    <div class="flex flex-col items-center gap-3">
                                        <div class="w-16 h-16 rounded-full bg-slate-100 flex items-center justify-center">
                                            <i data-lucide="receipt" class="w-8 h-8 text-slate-300"></i>
                                        </div>
                                        <p class="text-slate-500 text-[14px] font-medium">{{ __('pages.no_billing_snapshots') }}</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($billingSnapshots->hasPages())
                <div class="px-5 py-4 border-t border-slate-200 bg-slate-50">
                    <div class="flex items-center justify-between text-[13px] text-slate-600">
                        <span>{{ __('pages.all_items_showing') }} {{ $billingSnapshots->total() }} {{ __('pages.items_showing') }} {{ $billingSnapshots->firstItem() ?? 0 }} - {{ $billingSnapshots->lastItem() ?? 0 }} {{ __('pages.items_displayed') }}</span>
                        <div class="flex items-center gap-2">
                            {{ $billingSnapshots->onEachSide(1)->links('pagination::tailwind') }}
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
                if (chevron) {
                    chevron.classList.toggle('rotate-90');
                }
            }
        }

        function filterByDept(deptId, orgId) {
            // Filter by both institution and department
            const url = new URL(window.location.href);
            url.searchParams.set('medical_institution_id', orgId);
            url.searchParams.set('department_id', deptId);
            url.searchParams.delete('page');
            window.location.href = url.toString();
        }
        
        function filterByOrg(orgId) {
            // Toggle department list
            const deptContainer = document.getElementById(`org-${orgId}-depts`);
            const orgHeader = document.querySelector(`[data-org-id="${orgId}"] .org-header`);
            const chevron = orgHeader?.querySelector('.org-chevron');
            
            if (deptContainer) {
                deptContainer.classList.toggle('hidden');
                if (chevron) {
                    chevron.classList.toggle('rotate-90');
                }
            }
            
            // Reload page with filter
            const url = new URL(window.location.href);
            if (url.searchParams.get('medical_institution_id') == orgId) {
                // If already selected, clear filter
                url.searchParams.delete('medical_institution_id');
            } else {
                url.searchParams.set('medical_institution_id', orgId);
            }
            url.searchParams.delete('department_id');
            url.searchParams.delete('page');
            window.location.href = url.toString();
        }

        // Auto-expand org if filtered
        @if(request('medical_institution_id'))
            const orgId = {{ request('medical_institution_id') }};
            const deptContainer = document.getElementById(`org-${orgId}-depts`);
            if (deptContainer) {
                deptContainer.classList.remove('hidden');
                const orgHeader = document.querySelector(`[data-org-id="${orgId}"] .org-header`);
                const chevron = orgHeader?.querySelector('.org-chevron');
                if (chevron) {
                    chevron.classList.add('rotate-90');
                }
            }
        @endif

        // Initialize icons
        document.addEventListener('DOMContentLoaded', function() {
            if (typeof lucide !== 'undefined') {
                lucide.createIcons();
            }
        });
    </script>
@endsection
