@extends('layouts.app')

@section('title', __('menu.dashboard') . ' | ' . __('auth.system_title'))
@section('page_title', __('menu.dashboard'))

@section('content')
    @php
        $user = auth()->user();
        $customer = $user->customerUser;
        $admin = $user->adminUser;
    @endphp


    {{-- Dashboard Header Section --}}
    <div class="mb-1">
        <div class="p-3">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div class="flex-1">
                    <div class="flex items-center gap-3 mb-2">
                        <div class="h-10 w-10 rounded-lg bg-gradient-to-br from-primary-500 to-primary-600 flex items-center justify-center">
                            <i data-lucide="layout-dashboard" class="w-5 h-5 text-white"></i>
                        </div>
                        <div>
                            <h2 class="text-xl font-bold text-slate-900">ダッシュボード</h2>
                            <p class="text-[13px] text-slate-600 flex items-center gap-2 mt-0.5">
                                @if($admin)
                                    <span>{{ __('pages.system_admin') }}</span>
                                @elseif($customer)
                                    <i data-lucide="building" class="w-3.5 h-3.5 text-slate-400"></i>
                                    <span class="font-medium text-slate-700">{{ $customer->medicalInstitution->name ?? '' }}</span>
                                    <span class="text-slate-500">{{ __('roles.' . $customer->role) }}</span>
                                @endif
                            </p>
                        </div>
                    </div>
                </div>
                @if($closingDay)
                    <div class="bg-gradient-to-r from-primary-50 to-blue-50 rounded-lg px-4 py-2.5 border border-primary-200/50 flex items-center gap-3">
                        <div class="h-8 w-8 rounded-lg bg-primary-500/10 flex items-center justify-center">
                            <i data-lucide="calendar" class="w-4 h-4 text-primary-600"></i>
                        </div>
                        <div>
                            <p class="text-[11px] font-semibold text-primary-700 mb-0.5">締め日（請求基準日）</p>
                            <p class="text-[13px] font-bold text-slate-900">毎月 {{ $closingDay }}日</p>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    @if($admin)
        {{-- ============================================ --}}
        {{-- KAMATANI ADMIN DASHBOARD --}}
        {{-- ============================================ --}}
        
        {{-- Summary Cards --}}
        <x-stat-grid :cols="4">
            <x-stat-card
                tone="blue"
                icon="file-text"
                :value="$stats['total_applications'] ?? 0"
                :label="__('pages.total_applications')"
                :badge="__('pages.total')"
                :hint="__('pages.month_over_month')"
            />
            <x-stat-card
                tone="amber"
                icon="clock"
                :value="$stats['pending_approvals'] ?? 0"
                :label="__('pages.pending_approval')"
                :badge="__('pages.action_required_badge')"
                :hint="__('pages.immediate_action_required')"
            />
            <x-stat-card
                tone="emerald"
                icon="users"
                :value="$stats['total_employees'] ?? 0"
                :label="__('pages.registered_employees')"
                :badge="__('pages.active_employees')"
                :hint="__('pages.all_institutions_total')"
            />
            <x-stat-card
                tone="red"
                icon="shopping-cart"
                :value="$stats['inventory_alerts'] ?? 0"
                label="在庫アラート"
                badge="補充必要"
                hint="在庫不足商品"
            />
        </x-stat-grid>

        {{-- Charts and Recent Applications --}}
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-6">
            @php
                $trendData = $trendData ?? [];
            @endphp
            @if(!empty($trendData))
            {{-- Application Trend Chart --}}
            <div class="lg:col-span-2 bg-white rounded-xl border border-slate-200 shadow-sm  flex flex-col">
                <div class="px-4 pt-4 pb-3 flex-shrink-0">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-base font-bold text-slate-900 flex items-center gap-2">
                                <i data-lucide="trending-up" class="w-4 h-4 text-primary-600"></i>
                                {{ __('pages.application_trend') }}
                            </h3>
                            <p class="text-[12px] text-slate-500 mt-0.5">{{ __('pages.last_6_months') }}</p>
                        </div>
                    </div>
                </div>
                <div class="px-4 pb-4 flex-1 min-h-0" style="position: relative;">
                    <canvas id="applicationTrendChart"></canvas>
                </div>
            </div>
            @endif

            {{-- Quick Actions --}}
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm  flex flex-col">
                <div class="px-4 pt-6 pb-6 flex-shrink-0">
                    <h3 class="text-base font-bold text-slate-900 flex items-center gap-2">
                        <i data-lucide="zap" class="w-4 h-4 text-primary-600"></i>
                        {{ __('pages.quick_actions') }}
                    </h3>
                </div>
                <div class="px-4 pb-4 space-y-2 flex-1 overflow-y-auto min-h-0">
                    @php
                        $adminRole = $admin->role ?? null;
                    @endphp
                    
                    {{-- System Admin & Operations: Applications --}}
                    @if(in_array($adminRole, ['system_admin', 'operation']))
                    <a href="{{ route('admin.applications.index') }}" class="flex items-center gap-2.5 p-3 rounded-lg bg-gradient-to-r from-primary-50 to-blue-50 border border-primary-200 hover:border-primary-400 hover:shadow-sm transition-all group">
                        <div class="h-9 w-9 rounded-lg bg-gradient-to-br from-primary-500 to-primary-600 flex items-center justify-center group-hover:scale-105 transition-transform">
                            <i data-lucide="clipboard-list" class="w-4 h-4 text-white"></i>
                        </div>
                        <div class="flex-1">
                            <p class="text-[13px] font-semibold text-slate-900">{{ __('pages.application_management') }}</p>
                            <p class="text-[11px] text-slate-600">{{ __('pages.application_management_desc') }}</p>
                        </div>
                        <i data-lucide="chevron-right" class="w-4 h-4 text-slate-400 group-hover:text-primary-600"></i>
                    </a>
                    @endif
                    
                    {{-- System Admin, Operations, Sales: Customer Contract Plans (SPEC) --}}
                    @if(in_array($adminRole, ['system_admin', 'operation', 'sales', 'contract_plan_manager']))
                    <a href="{{ route('admin.customer-contract-plans.index') }}" class="flex items-center gap-2.5 p-3 rounded-lg bg-gradient-to-r from-slate-50 to-slate-100 border border-slate-200 hover:border-slate-300 hover:shadow-sm transition-all group">
                        <div class="h-9 w-9 rounded-lg bg-gradient-to-br from-slate-500 to-slate-600 flex items-center justify-center group-hover:scale-105 transition-transform">
                            <i data-lucide="file-signature" class="w-4 h-4 text-white"></i>
                        </div>
                        <div class="flex-1">
                            <p class="text-[13px] font-semibold text-slate-900">{{ __('menu.customer_contract_plans') }}</p>
                            <p class="text-[11px] text-slate-600">契約プランの確認・管理</p>
                        </div>
                        <i data-lucide="chevron-right" class="w-4 h-4 text-slate-400 group-hover:text-slate-600"></i>
                    </a>
                    @endif
                    
                    {{-- System Admin, Operations, Sales, Accounting: Billing --}}
                    @if(in_array($adminRole, ['system_admin', 'operation', 'sales', 'accounting']))
                    <a href="{{ route('admin.billing.index') }}" class="flex items-center gap-2.5 p-3 rounded-lg bg-gradient-to-r from-amber-50 to-orange-50 border border-amber-200 hover:border-amber-300 hover:shadow-sm transition-all group">
                        <div class="h-9 w-9 rounded-lg bg-gradient-to-br from-amber-500 to-orange-600 flex items-center justify-center group-hover:scale-105 transition-transform">
                            <i data-lucide="receipt-text" class="w-4 h-4 text-white"></i>
                        </div>
                        <div class="flex-1">
                            <p class="text-[13px] font-semibold text-slate-900">{{ __('menu.billing') }}</p>
                            <p class="text-[11px] text-slate-600">{{ __('pages.billing_management_desc') ?? '請求データの確認・管理' }}</p>
                        </div>
                        <i data-lucide="chevron-right" class="w-4 h-4 text-slate-400 group-hover:text-amber-600"></i>
                    </a>
                    @endif
                    
                    {{-- System Admin & Operations: Uniform Products --}}
                    @if(in_array($adminRole, ['system_admin', 'operation']))
                    <a href="{{ route('admin.uniform-products.index') }}" class="flex items-center gap-2.5 p-3 rounded-lg bg-gradient-to-r from-slate-50 to-slate-100 border border-slate-200 hover:border-slate-300 hover:shadow-sm transition-all group">
                        <div class="h-9 w-9 rounded-lg bg-gradient-to-br from-purple-500 to-purple-600 flex items-center justify-center group-hover:scale-105 transition-transform">
                            <i data-lucide="shirt" class="w-4 h-4 text-white"></i>
                        </div>
                        <div class="flex-1">
                            <p class="text-[13px] font-semibold text-slate-900">{{ __('pages.uniform_products') }}</p>
                            <p class="text-[11px] text-slate-600">{{ __('pages.uniform_products_desc') }}</p>
                        </div>
                        <i data-lucide="chevron-right" class="w-4 h-4 text-slate-400 group-hover:text-slate-600"></i>
                    </a>
                    @endif
                    
                    {{-- System Admin & Operations: Employees --}}
                    @if(in_array($adminRole, ['system_admin', 'operation']))
                    <a href="{{ route('admin.employees.index') }}" class="flex items-center gap-2.5 p-3 rounded-lg bg-gradient-to-r from-emerald-50 to-teal-50 border border-emerald-200 hover:border-emerald-400 hover:shadow-sm transition-all group">
                        <div class="h-9 w-9 rounded-lg bg-gradient-to-br from-emerald-500 to-emerald-600 flex items-center justify-center group-hover:scale-105 transition-transform">
                            <i data-lucide="users" class="w-4 h-4 text-white"></i>
                        </div>
                        <div class="flex-1">
                            <p class="text-[13px] font-semibold text-slate-900">{{ __('menu.all_employees') }}</p>
                            <p class="text-[11px] text-slate-600">{{ __('pages.all_employees_description') }}</p>
                        </div>
                        <i data-lucide="chevron-right" class="w-4 h-4 text-slate-400 group-hover:text-emerald-600"></i>
                    </a>
                    @endif
                    
                    {{-- System Admin Only: Master Data Management --}}
                    @if($adminRole === 'system_admin')
                    <a href="{{ route('admin.masters.institutions') }}" class="flex items-center gap-2.5 p-3 rounded-lg bg-gradient-to-r from-indigo-50 to-purple-50 border border-indigo-200 hover:border-indigo-300 hover:shadow-sm transition-all group">
                        <div class="h-9 w-9 rounded-lg bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center group-hover:scale-105 transition-transform">
                            <i data-lucide="database" class="w-4 h-4 text-white"></i>
                        </div>
                        <div class="flex-1">
                            <p class="text-[13px] font-semibold text-slate-900">{{ __('menu.menu_master_data') }}</p>
                            <p class="text-[11px] text-slate-600">{{ __('pages.master_data_management_desc') ?? 'マスタデータの管理' }}</p>
                        </div>
                        <i data-lucide="chevron-right" class="w-4 h-4 text-slate-400 group-hover:text-indigo-600"></i>
                    </a>
                    @endif
                </div>
            </div>
        </div>

        {{-- Recent Applications Table --}}
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden mb-6">
            <div class="px-4 py-3 border-b border-slate-200 bg-gradient-to-r from-slate-50 to-white flex items-center justify-between">
                <h3 class="text-base font-bold text-slate-900 flex items-center gap-2">
                    <i data-lucide="clock" class="w-4 h-4 text-primary-600"></i>
                    {{ __('pages.recent_applications') }}
                </h3>
                <a href="{{ route('admin.applications.index') }}" class="text-primary-600 hover:text-primary-700 text-[12px] font-medium flex items-center gap-1">
                    {{ __('pages.view_all') }}
                    <i data-lucide="arrow-right" class="w-3.5 h-3.5"></i>
                </a>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-4 py-2.5 text-left text-[12px] font-semibold text-slate-700 uppercase tracking-wider">{{ __('pages.application_id') }}</th>
                            <th class="px-4 py-2.5 text-left text-[12px] font-semibold text-slate-700 uppercase tracking-wider">{{ __('pages.employee_name') }}</th>
                            <th class="px-4 py-2.5 text-left text-[12px] font-semibold text-slate-700 uppercase tracking-wider">{{ __('pages.department') }}</th>
                            <th class="px-4 py-2.5 text-left text-[12px] font-semibold text-slate-700 uppercase tracking-wider">{{ __('pages.application_type') }}</th>
                            <th class="px-4 py-2.5 text-left text-[12px] font-semibold text-slate-700 uppercase tracking-wider">{{ __('pages.status') }}</th>
                            <th class="px-4 py-2.5 text-left text-[12px] font-semibold text-slate-700 uppercase tracking-wider">{{ __('pages.application_date') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                        @forelse($recentApplications as $app)
                            <tr class="hover:bg-slate-50 transition-colors">
                                <td class="px-4 py-3">
                                    <span class="text-[13px] font-mono text-slate-600">APP-{{ str_pad($app->id, 6, '0', STR_PAD_LEFT) }}</span>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-2">
                                        <div class="h-8 w-8 rounded-full bg-gradient-to-br from-primary-400 to-primary-600 flex items-center justify-center text-white text-xs font-bold">
                                            {{ mb_substr($app->employee->name ?? 'N', 0, 1) }}
                                        </div>
                                        <span class="text-[14px] font-medium text-slate-900">{{ $app->employee->name ?? '-' }}</span>
                                    </div>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="text-[13px] text-slate-700">{{ $app->department->name ?? '-' }}</span>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="text-[13px] text-slate-700">
                                        @if($app->type === 'new_loan') 新規貸出
                                        @elseif($app->type === 'return') 返却
                                        @elseif($app->type === 'resize') サイズ変更
                                        @elseif($app->type === 'exchange') 交換
                                        @else 異動
                                        @endif
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    @php
                                        $statusColors = [
                                            'pending' => 'bg-amber-100 text-amber-700',
                                            'waiting_special_approval' => 'bg-orange-100 text-orange-700',
                                            'approved' => 'bg-emerald-100 text-emerald-700',
                                            'rejected' => 'bg-red-100 text-red-700',
                                        ];
                                        $statusText = [
                                            'pending' => __('pages.pending_approval'),
                                            'waiting_special_approval' => __('pages.special_approval_pending'),
                                            'approved' => __('pages.approved'),
                                            'rejected' => __('pages.reject'),
                                        ];
                                    @endphp
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-[12px] font-semibold {{ $statusColors[$app->status] ?? 'bg-slate-100 text-slate-700' }}">
                                        {{ $statusText[$app->status] ?? $app->status }}
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="text-[13px] text-slate-600">{{ $app->created_at->format('Y-m-d') }}</span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-12 text-center">
                                    <div class="flex flex-col items-center gap-3">
                                        <i data-lucide="inbox" class="w-12 h-12 text-slate-300"></i>
                                        <p class="text-[14px] text-slate-500">{{ __('pages.no_application_data') }}</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    @else
        {{-- ============================================ --}}
        {{-- CUSTOMER (MEDICAL INSTITUTION) DASHBOARD --}}
        {{-- ============================================ --}}
        
        {{-- Summary Cards --}}
        @php
            $appChange = ($stats['applications_this_month'] ?? 0) - ($stats['applications_last_month'] ?? 0);
            $appChangePercent = ($stats['applications_last_month'] ?? 0) > 0
                ? round(($appChange / ($stats['applications_last_month'] ?? 1)) * 100)
                : ($appChange > 0 ? 100 : 0);
        @endphp
        <x-stat-grid :cols="4">
            <x-stat-card
                tone="emerald"
                icon="users"
                :value="$stats['total_employees'] ?? 0"
                label="登録従業員数"
                :badge="'+'.($stats['new_employees_this_month'] ?? 0)"
                :hint="'在籍中: '.($stats['employed_employees'] ?? 0).'名'"
            />
            <x-stat-card
                tone="blue"
                icon="shirt"
                :value="$stats['active_loans'] ?? 0"
                :label="__('pages.loaned_uniforms')"
                badge="今月"
                :hint="__('pages.contract_limit').': '.number_format($stats['contract_limit'] ?? 0).__('pages.points')"
            />
            <x-stat-card
                tone="purple"
                icon="file-text"
                :value="$stats['applications_this_month'] ?? 0"
                label="今月の申請数"
                :badge="($appChange >= 0 ? '+' : '').$appChangePercent.'%'"
                :hint="'前月比: '.($appChange >= 0 ? '+' : '').$appChange.'件'"
            />
            <x-stat-card
                tone="amber"
                icon="file-check"
                :value="$stats['pending_approvals'] ?? 0"
                :label="__('pages.pending_approval_applications')"
                badge="要対応"
                :hint="__('pages.special_approval_pending_count').': '.($stats['special_approval_pending'] ?? 0).__('pages.items')"
            />
        </x-stat-grid>

        {{-- Quick Actions and Department Stats --}}
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-6">
            {{-- Quick Actions --}}
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm flex flex-col" >
                <div class="px-4 pt-6 pb-6 flex-shrink-0">
                    <h3 class="text-base font-bold text-slate-900 flex items-center gap-2">
                        <i data-lucide="zap" class="w-4 h-4 text-primary-600"></i>
                        {{ __('pages.quick_actions') }}
                    </h3>
                </div>
                <div class="px-4 pb-4 space-y-2 flex-1 overflow-y-auto min-h-0">
                    @php
                        $customerRole = $customer->role ?? null;
                    @endphp
                    
                    {{-- All Customer Roles: Individual Application --}}
                    <a href="{{ route('applications.create') }}" class="flex items-center gap-2.5 p-2 xl:p-[1em] lg:p-[0.9em] rounded-lg bg-gradient-to-r from-primary-50 to-blue-50 border border-primary-200 hover:border-primary-400 hover:shadow-sm transition-all group">
                        <div class="h-9 w-9 rounded-lg bg-gradient-to-br from-primary-500 to-primary-600 flex items-center justify-center group-hover:scale-105 transition-transform">
                            <i data-lucide="user-plus" class="w-4 h-4 text-white"></i>
                        </div>
                        <div class="flex-1">
                            <p class="text-[13px] font-semibold text-slate-900">{{ __('pages.individual_application') }}</p>
                            <p class="text-[11px] text-slate-600">{{ __('pages.individual_application_desc') }}</p>
                        </div>
                        <i data-lucide="chevron-right" class="w-4 h-4 text-slate-400 group-hover:text-primary-600"></i>
                    </a>
                    
                    {{-- Institution Admin Only: Departmental Application --}}
                    @if($customer && $customerRole === 'institution_admin')
                    <a href="{{ route('applications.create') }}" class="flex items-center gap-2.5 p-2 xl:p-[1em] lg:p-[0.9em] rounded-lg bg-gradient-to-r from-slate-50 to-slate-100 border border-slate-200 hover:border-slate-300 hover:shadow-sm transition-all group">
                        <div class="h-9 w-9 rounded-lg bg-gradient-to-br from-slate-500 to-slate-600 flex items-center justify-center group-hover:scale-105 transition-transform">
                            <i data-lucide="users" class="w-4 h-4 text-white"></i>
                        </div>
                        <div class="flex-1">
                            <p class="text-[13px] font-semibold text-slate-900">{{ __('pages.departmental_application') }}</p>
                            <p class="text-[11px] text-slate-600">{{ __('pages.departmental_application_desc') }}</p>
                        </div>
                        <i data-lucide="chevron-right" class="w-4 h-4 text-slate-400 group-hover:text-slate-600"></i>
                    </a>
                    @endif
                    
                    {{-- All Customer Roles: Employee Registration --}}
                    <a href="{{ route('employees.index') }}" class="flex items-center gap-2.5 p-2 xl:p-[1em] lg:p-[0.9em] rounded-lg bg-gradient-to-r from-emerald-50 to-teal-50 border border-emerald-200 hover:border-emerald-400 hover:shadow-sm transition-all group">
                        <div class="h-9 w-9 rounded-lg bg-gradient-to-br from-emerald-500 to-emerald-600 flex items-center justify-center group-hover:scale-105 transition-transform">
                            <i data-lucide="user-plus" class="w-4 h-4 text-white"></i>
                        </div>
                        <div class="flex-1">
                            <p class="text-[13px] font-semibold text-slate-900">従業員登録</p>
                            <p class="text-[11px] text-slate-600">{{ __('pages.new_employee_registration') }}</p>
                        </div>
                        <i data-lucide="chevron-right" class="w-4 h-4 text-slate-400 group-hover:text-emerald-600"></i>
                    </a>
                    
                    {{-- All Customer Roles: Applications History --}}
                    <a href="{{ route('applications.index') }}" class="flex items-center gap-2.5 p-2 xl:p-[1em] lg:p-[0.9em] rounded-lg bg-gradient-to-r from-slate-50 to-slate-100 border border-slate-200 hover:border-slate-300 hover:shadow-sm transition-all group">
                        <div class="h-9 w-9 rounded-lg bg-gradient-to-br from-blue-500 to-blue-600 flex items-center justify-center group-hover:scale-105 transition-transform">
                            <i data-lucide="file-text" class="w-4 h-4 text-white"></i>
                        </div>
                        <div class="flex-1">
                            <p class="text-[13px] font-semibold text-slate-900">{{ __('menu.applications') }}</p>
                            <p class="text-[11px] text-slate-600">申請履歴の確認・管理</p>
                        </div>
                        <i data-lucide="chevron-right" class="w-4 h-4 text-slate-400 group-hover:text-slate-600"></i>
                    </a>
                    
                    {{-- Institution Admin Only: Department Management --}}
                    @if($customer && $customerRole === 'institution_admin')
                    <a href="{{ route('customer.masters.departments') }}" class="flex items-center gap-2.5 p-2 xl:p-[1em] lg:p-[0.9em] rounded-lg bg-gradient-to-r from-slate-50 to-slate-100 border border-slate-200 hover:border-slate-300 hover:shadow-sm transition-all group">
                        <div class="h-9 w-9 rounded-lg bg-gradient-to-br from-purple-500 to-purple-600 flex items-center justify-center group-hover:scale-105 transition-transform">
                            <i data-lucide="briefcase" class="w-4 h-4 text-white"></i>
                        </div>
                        <div class="flex-1">
                            <p class="text-[13px] font-semibold text-slate-900">{{ __('menu.departments') }}</p>
                            <p class="text-[11px] text-slate-600">部署マスタ管理</p>
                        </div>
                        <i data-lucide="chevron-right" class="w-4 h-4 text-slate-400 group-hover:text-slate-600"></i>
                    </a>
                    @endif
                </div>
            </div>

            {{-- Department Loan Status --}}
            <div class="lg:col-span-2 bg-white rounded-xl border border-slate-200 shadow-sm flex flex-col" >
                <div class="px-4 pt-4 pb-3 flex-shrink-0">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-base font-bold text-slate-900 flex items-center gap-2">
                                <i data-lucide="bar-chart-3" class="w-4 h-4 text-primary-600"></i>
                                部門別貸出状況
                            </h3>
                            <p class="text-[12px] text-slate-500 mt-0.5">今月の貸出状況</p>
                        </div>
                    </div>
                </div>
                <div class="px-4 pb-4 space-y-3 flex-1 overflow-y-auto min-h-0">
                    @forelse($departmentStats as $dept)
                        <div class="flex items-center gap-3 p-3 rounded-lg bg-gradient-to-r from-slate-50 to-white border border-slate-200 hover:shadow-sm transition-all">
                            <div class="h-10 w-10 rounded-lg bg-gradient-to-br from-primary-400 to-primary-600 flex items-center justify-center flex-shrink-0">
                                <i data-lucide="building" class="w-5 h-5 text-white"></i>
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center justify-between mb-1.5">
                                    <p class="text-[13px] font-semibold text-slate-900">{{ $dept['name'] ?? $dept->name ?? '-' }}</p>
                                    <span class="text-[12px] font-bold text-slate-700">{{ $dept['utilization'] ?? 0 }}%</span>
                                </div>
                                <div class="flex items-center gap-3 text-[11px] text-slate-600 mb-1.5">
                                    <span>従業員数: <strong>{{ $dept['employees_count'] ?? 0 }}</strong></span>
                                    <span>貸出点数: <strong>{{ $dept['loans'] ?? 0 }}</strong></span>
                                    <span>上限: <strong>{{ $dept['limit'] ?? 0 }}</strong></span>
                                </div>
                                <div class="w-full bg-slate-200 rounded-full h-2.5 overflow-hidden">
                                    <div class="h-full rounded-full transition-all duration-500 {{ ($dept['utilization'] ?? 0) >= 90 ? 'bg-gradient-to-r from-red-500 to-red-600' : (($dept['utilization'] ?? 0) >= 70 ? 'bg-gradient-to-r from-amber-500 to-orange-500' : 'bg-gradient-to-r from-emerald-500 to-teal-600') }}" style="width: {{ min($dept['utilization'] ?? 0, 100) }}%"></div>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-8">
                            <i data-lucide="inbox" class="w-12 h-12 text-slate-300 mx-auto mb-3"></i>
                            <p class="text-[14px] text-slate-500">部署データがありません</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- Recent Applications --}}
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="px-4 py-3 border-b border-slate-200 bg-gradient-to-r from-slate-50 to-white flex items-center justify-between">
                <h3 class="text-base font-bold text-slate-900 flex items-center gap-2">
                    <i data-lucide="clock" class="w-4 h-4 text-primary-600"></i>
                    最近の申請
                </h3>
                <a href="{{ route('applications.index') }}" class="text-primary-600 hover:text-primary-700 text-[12px] font-medium flex items-center gap-1">
                    {{ __('pages.view_all') }}
                    <i data-lucide="arrow-right" class="w-3.5 h-3.5"></i>
                </a>
            </div>
            <div class="p-4">
                <div class="space-y-2.5">
                    @forelse($recentApplications as $app)
                        <div class="flex items-center gap-3 p-3 rounded-lg border border-slate-200 hover:border-primary-300 hover:shadow-sm transition-all group">
                            <div class="h-10 w-10 rounded-lg bg-gradient-to-br from-primary-400 to-primary-600 flex items-center justify-center flex-shrink-0 text-white font-bold text-xs">
                                {{ mb_substr($app->employee->name ?? 'N', 0, 1) }}
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2.5 mb-1">
                                    <p class="text-[13px] font-semibold text-slate-900">{{ $app->employee->name ?? '-' }}</p>
                                    <span class="px-2 py-0.5 rounded text-[10px] font-medium {{ $app->status === 'approved' ? 'bg-emerald-100 text-emerald-700' : ($app->status === 'pending' ? 'bg-amber-100 text-amber-700' : 'bg-slate-100 text-slate-700') }}">
                                        @if($app->status === 'approved') 承認済み
                                        @elseif($app->status === 'pending') 処理中
                                        @elseif($app->status === 'waiting_special_approval') 特別承認待ち
                                        @else 完了
                                        @endif
                                    </span>
                                </div>
                                <p class="text-[12px] text-slate-600">
                                    @if($app->type === 'new_loan') 新規貸出
                                    @elseif($app->type === 'resize') サイズ変更
                                    @elseif($app->type === 'return') 返却
                                    @elseif($app->type === 'exchange') 交換
                                    @else 異動
                                    @endif
                                    @if($app->items->count() > 0)
                                        - {{ $app->items->first()->uniformProduct->name ?? '' }} × {{ $app->items->sum('quantity') }}
                                    @endif
                                </p>
                                <p class="text-[11px] text-slate-500 mt-0.5">{{ $app->created_at->format('Y/m/d') }}</p>
                            </div>
                            <a href="{{ route('applications.show', $app) }}" class="p-1.5 text-slate-400 hover:text-primary-600 hover:bg-primary-50 rounded-lg transition-all">
                                <i data-lucide="chevron-right" class="w-4 h-4"></i>
                            </a>
                        </div>
                    @empty
                        <div class="text-center py-6">
                            <i data-lucide="inbox" class="w-10 h-10 text-slate-300 mx-auto mb-2"></i>
                            <p class="text-[13px] text-slate-500">申請データがありません</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    @endif
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize Lucide icons
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }

    // Application Trend Chart - Real data from database
    @if(!empty($trendData))
    const trendCtx = document.getElementById('applicationTrendChart');
    if (trendCtx && typeof Chart !== 'undefined') {
        const trendData = @json($trendData ?? []);
        
        // Check if we have any data to display
        const hasData = trendData.some(d => d.new_loan > 0 || d.return > 0 || d.resize > 0 || d.exchange > 0);
        
        if (hasData) {
            new Chart(trendCtx, {
                type: 'line',
                data: {
                    labels: trendData.map(d => d.month),
                    datasets: [
                        {
                            label: '{{ __('pages.new_loan') }}',
                            data: trendData.map(d => d.new_loan || 0),
                            borderColor: 'rgb(59, 130, 246)',
                            backgroundColor: 'rgba(59, 130, 246, 0.1)',
                            tension: 0.4,
                            fill: true,
                            pointBackgroundColor: 'rgb(59, 130, 246)',
                            pointBorderColor: '#fff',
                            pointBorderWidth: 2,
                            pointRadius: 5,
                            pointHoverRadius: 7,
                        },
                        {
                            label: '{{ __('pages.return') }}',
                            data: trendData.map(d => d.return || 0),
                            borderColor: 'rgb(34, 197, 94)',
                            backgroundColor: 'rgba(34, 197, 94, 0.1)',
                            tension: 0.4,
                            fill: true,
                            pointBackgroundColor: 'rgb(34, 197, 94)',
                            pointBorderColor: '#fff',
                            pointBorderWidth: 2,
                            pointRadius: 5,
                            pointHoverRadius: 7,
                        },
                        {
                            label: '{{ __('pages.resize') }}',
                            data: trendData.map(d => d.resize || 0),
                            borderColor: 'rgb(251, 146, 60)',
                            backgroundColor: 'rgba(251, 146, 60, 0.1)',
                            tension: 0.4,
                            fill: true,
                            pointBackgroundColor: 'rgb(251, 146, 60)',
                            pointBorderColor: '#fff',
                            pointBorderWidth: 2,
                            pointRadius: 5,
                            pointHoverRadius: 7,
                        },
                        @if(array_sum(array_column($trendData, 'exchange')) > 0)
                        {
                            label: '{{ __('pages.exchange') }}',
                            data: trendData.map(d => d.exchange || 0),
                            borderColor: 'rgb(168, 85, 247)',
                            backgroundColor: 'rgba(168, 85, 247, 0.1)',
                            tension: 0.4,
                            fill: true,
                            pointBackgroundColor: 'rgb(168, 85, 247)',
                            pointBorderColor: '#fff',
                            pointBorderWidth: 2,
                            pointRadius: 5,
                            pointHoverRadius: 7,
                        },
                        @endif
                    ],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: {
                        intersect: false,
                        mode: 'index',
                    },
                    plugins: {
                        legend: {
                            display: true,
                            position: 'top',
                            labels: {
                                usePointStyle: true,
                                padding: 15,
                                font: {
                                    size: 12,
                                    family: '"Noto Sans JP", sans-serif',
                                },
                                color: '#475569',
                            },
                        },
                        tooltip: {
                            backgroundColor: 'rgba(15, 23, 42, 0.95)',
                            padding: 12,
                            titleFont: {
                                size: 13,
                                weight: 'bold',
                            },
                            bodyFont: {
                                size: 12,
                            },
                            borderColor: 'rgba(255, 255, 255, 0.1)',
                            borderWidth: 1,
                            cornerRadius: 8,
                            displayColors: true,
                        },
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: 'rgba(148, 163, 184, 0.1)',
                                drawBorder: false,
                            },
                            ticks: {
                                font: {
                                    size: 11,
                                },
                                color: '#64748b',
                                precision: 0,
                            },
                        },
                        x: {
                            grid: {
                                display: false,
                            },
                            ticks: {
                                font: {
                                    size: 11,
                                },
                                color: '#64748b',
                            },
                        },
                    },
                },
            });
        } else {
            // Show message if no data
            trendCtx.parentElement.innerHTML = '<div class="flex flex-col items-center justify-center py-12"><i data-lucide="bar-chart-3" class="w-16 h-16 text-slate-300 mb-4"></i><p class="text-slate-500 text-[14px]">{{ __('pages.no_application_data') }}</p></div>';
            if (typeof lucide !== 'undefined') {
                lucide.createIcons();
            }
        }
    } else if (trendCtx) {
        console.error('Chart.js library not loaded');
    }
    @endif
});
</script>
@endpush