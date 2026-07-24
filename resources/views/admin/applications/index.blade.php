@extends('layouts.app')

@section('title', __('menu.applications') . ' | ' . __('auth.system_title'))
@section('page_title', __('menu.applications'))

@section('content')
    {{-- Application Overview Section --}}
    <div class="mb-8">
        <h2 class="text-xl font-bold text-slate-900 mb-1">{{ __('pages.application_overview') }}</h2>
        <p class="text-[14px] text-slate-500">{{ __('pages.application_description') }}</p>
    </div>

    {{-- Statistics Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
        {{-- Pending Applications Card --}}
        <div class="relative overflow-hidden rounded-xl bg-gradient-to-br from-amber-50 via-orange-50 to-red-50 border border-amber-200/50 p-4 shadow-sm hover:shadow-md transition-all duration-300">
            <div class="relative z-10">
                <div class="flex items-center justify-between mb-3">
                    <div class="h-10 w-10 rounded-lg bg-amber-500/10 flex items-center justify-center">
                        <i data-lucide="clock" class="w-5 h-5 text-amber-600"></i>
                    </div>
                    <span class="px-2 py-0.5 rounded-full bg-amber-500/10 text-amber-700 text-[10px] font-semibold">{{ __('pages.action_required_badge') }}</span>
                </div>
                <p class="text-3xl font-bold text-slate-900 mb-1">{{ number_format($stats['pending'] ?? 0) }}</p>
                <p class="text-slate-600 text-[13px] font-medium">{{ __('pages.pending_applications') }}</p>
                <div class="mt-1.5 flex items-center gap-2 text-slate-500 text-[12px]">
                    <i data-lucide="alert-circle" class="w-4 h-4"></i>
                    <span>{{ __('pages.action_required') }}</span>
                </div>
            </div>
        </div>

        {{-- Special Approval Pending Card --}}
        <div class="relative overflow-hidden rounded-xl bg-gradient-to-br from-orange-50 via-red-50 to-pink-50 border border-orange-200/50 p-4 shadow-sm hover:shadow-md transition-all duration-300">
            <div class="relative z-10">
                <div class="flex items-center justify-between mb-3">
                    <div class="h-10 w-10 rounded-lg bg-orange-500/10 flex items-center justify-center">
                        <i data-lucide="alert-triangle" class="w-5 h-5 text-orange-600"></i>
                    </div>
                    <span class="px-2 py-0.5 rounded-full bg-orange-500/10 text-orange-700 text-[10px] font-semibold">{{ __('pages.special_approval_pending') }}</span>
                </div>
                <p class="text-3xl font-bold text-slate-900 mb-1">{{ number_format($stats['special'] ?? 0) }}</p>
                <p class="text-slate-600 text-[13px] font-medium">{{ __('pages.special_approval_pending') }}</p>
                <div class="mt-1.5 flex items-center gap-2 text-slate-500 text-[12px]">
                    <i data-lucide="alert-octagon" class="w-4 h-4"></i>
                    <span>{{ __('pages.limit_exceeded_applications') }}</span>
                </div>
            </div>
        </div>

        {{-- Approved This Month Card --}}
        <div class="relative overflow-hidden rounded-xl bg-gradient-to-br from-emerald-50 via-teal-50 to-cyan-50 border border-emerald-200/50 p-4 shadow-sm hover:shadow-md transition-all duration-300">
            <div class="relative z-10">
                <div class="flex items-center justify-between mb-3">
                    <div class="h-10 w-10 rounded-lg bg-emerald-500/10 flex items-center justify-center">
                        <i data-lucide="check-circle" class="w-5 h-5 text-emerald-600"></i>
                    </div>
                    <span class="px-2 py-0.5 rounded-full bg-emerald-500/10 text-emerald-700 text-[10px] font-semibold">{{ __('pages.approved_this_month') }}</span>
                </div>
                <p class="text-3xl font-bold text-slate-900 mb-1">{{ number_format($stats['approved_this_month'] ?? 0) }}</p>
                <p class="text-slate-600 text-[13px] font-medium">{{ __('pages.approved_this_month') }}</p>
                <div class="mt-1.5 flex items-center gap-2 text-slate-500 text-[12px]">
                    <i data-lucide="trending-up" class="w-4 h-4"></i>
                    <span>{{ __('pages.this_month_count') }}</span>
                </div>
            </div>
        </div>

        {{-- Cancellation Requests Card --}}
        <div class="relative overflow-hidden rounded-xl bg-gradient-to-br from-red-50 via-rose-50 to-pink-50 border border-red-200/50 p-4 shadow-sm hover:shadow-md transition-all duration-300">
            <div class="relative z-10">
                <div class="flex items-center justify-between mb-3">
                    <div class="h-10 w-10 rounded-lg bg-red-500/10 flex items-center justify-center">
                        <i data-lucide="x-circle" class="w-5 h-5 text-red-600"></i>
                    </div>
                    <span class="px-2 py-0.5 rounded-full bg-red-500/10 text-red-700 text-[10px] font-semibold">{{ __('pages.cancellation_requests') }}</span>
                </div>
                <p class="text-3xl font-bold text-slate-900 mb-1">{{ number_format($stats['cancellations'] ?? 0) }}</p>
                <p class="text-slate-600 text-[13px] font-medium">{{ __('pages.cancellation_requests') }}</p>
                <div class="mt-1.5 flex items-center gap-2 text-slate-500 text-[12px]">
                    <i data-lucide="clock" class="w-4 h-4"></i>
                    <span>{{ __('pages.awaiting_approval') }}</span>
                </div>
            </div>
        </div>
    </div>

    {{-- テーブルセクション --}}
    <div class="bg-white border border-slate-200 rounded-xl shadow-sm overflow-hidden">
        {{-- Action Bar (Export CSV, Filters) --}}
        <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-end gap-3">
            <a href="{{ route('admin.applications.export', request()->query()) }}" 
               class="inline-flex items-center gap-2 px-4 py-2 rounded-lg border border-slate-300 text-slate-700 hover:bg-slate-50 text-[13px] font-medium transition-all">
                <i data-lucide="download" class="w-4 h-4"></i>
                {{ __('pages.csv_export') }}
            </a>
            <button id="filter-toggle-btn" class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-slate-900 text-white hover:bg-slate-800 text-[13px] font-medium transition-all">
                <i data-lucide="filter" class="w-4 h-4"></i>
                {{ __('pages.filters') }}
                <i data-lucide="chevron-down" id="filter-chevron" class="w-3 h-3 transition-transform"></i>
            </button>
        </div>

        {{-- タブ --}}
        <div class="px-6 pt-4 border-b border-slate-200">
            <div class="flex items-center gap-1 overflow-x-auto">
                @php
                    $type = request('type');
                @endphp
                    <a href="{{ route('admin.applications.index', array_merge(request()->except('page'), ['type' => null])) }}"
                   class="px-4 py-2 text-[13px] font-medium border-b-2 transition-colors whitespace-nowrap flex items-center gap-2 {{ !$type ? 'border-primary-600 text-primary-600' : 'border-transparent text-slate-600 hover:text-slate-900' }}">
                    <i data-lucide="grid" class="w-4 h-4"></i>
                    {{ __('pages.all_applications') }}
                </a>
                <a href="{{ route('admin.applications.index', array_merge(request()->except('page'), ['type' => 'new_loan'])) }}"
                   class="px-4 py-2 text-[13px] font-medium border-b-2 transition-colors whitespace-nowrap flex items-center gap-2 {{ $type === 'new_loan' ? 'border-primary-600 text-primary-600' : 'border-transparent text-slate-600 hover:text-slate-900' }}">
                    <i data-lucide="plus" class="w-4 h-4"></i>
                    {{ __('pages.new_loan') }}
                </a>
                <a href="{{ route('admin.applications.index', array_merge(request()->except('page'), ['type' => 'resize'])) }}"
                   class="px-4 py-2 text-[13px] font-medium border-b-2 transition-colors whitespace-nowrap flex items-center gap-2 {{ $type === 'resize' ? 'border-primary-600 text-primary-600' : 'border-transparent text-slate-600 hover:text-slate-900' }}">
                    <i data-lucide="move" class="w-4 h-4"></i>
                    {{ __('pages.size_exchange') }}
                </a>
                <a href="{{ route('admin.applications.index', array_merge(request()->except('page'), ['type' => 'return'])) }}"
                   class="px-4 py-2 text-[13px] font-medium border-b-2 transition-colors whitespace-nowrap flex items-center gap-2 {{ $type === 'return' ? 'border-primary-600 text-primary-600' : 'border-transparent text-slate-600 hover:text-slate-900' }}">
                    <i data-lucide="arrow-left" class="w-4 h-4"></i>
                    {{ __('pages.return') }}
                </a>
                <a href="{{ route('admin.applications.index', array_merge(request()->except('page'), ['type' => 'exchange'])) }}"
                   class="px-4 py-2 text-[13px] font-medium border-b-2 transition-colors whitespace-nowrap flex items-center gap-2 {{ $type === 'exchange' ? 'border-primary-600 text-primary-600' : 'border-transparent text-slate-600 hover:text-slate-900' }}">
                    <i data-lucide="refresh-cw" class="w-4 h-4"></i>
                    {{ __('pages.exchange') }}
                </a>
                <a href="{{ route('admin.applications.index', array_merge(request()->except('page'), ['type' => 'transfer'])) }}"
                   class="px-4 py-2 text-[13px] font-medium border-b-2 transition-colors whitespace-nowrap flex items-center gap-2 {{ $type === 'transfer' ? 'border-primary-600 text-primary-600' : 'border-transparent text-slate-600 hover:text-slate-900' }}">
                    <i data-lucide="arrow-right-left" class="w-4 h-4"></i>
                    {{ __('pages.transfer') }}
                </a>
            </div>
        </div>

        {{-- フィルタドロップダウン --}}
        <div id="filter-dropdown" class="px-6 py-3 border-b border-slate-200 flex items-center gap-3 hidden">
            <form method="GET" action="{{ route('admin.applications.index') }}" class="flex items-center gap-3">
                @foreach(request()->except(['institution_id','status','page']) as $key => $value)
                    <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                @endforeach
                <div class="relative">
                    <i data-lucide="building-2" class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400 pointer-events-none"></i>
                    <select name="institution_id" onchange="this.form.submit()" class="pl-9 pr-3 py-1.5 rounded-lg border-slate-300 text-[13px] focus:border-primary-500 focus:ring-primary-500 bg-white">
                        <option value="">{{ __('pages.all_institutions') }}</option>
                        @foreach($institutions as $inst)
                            <option value="{{ $inst->id }}" @selected(request('institution_id')==$inst->id)>{{ $inst->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="relative">
                    <i data-lucide="activity" class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400 pointer-events-none"></i>
                    <select name="status" onchange="this.form.submit()" class="pl-9 pr-3 py-1.5 rounded-lg border-slate-300 text-[13px] focus:border-primary-500 focus:ring-primary-500 bg-white">
                        <option value="">{{ __('pages.all_statuses') }}</option>
                        <option value="pending" @selected(request('status')==='pending')>{{ __('pages.unprocessed') }}</option>
                        <option value="waiting_special_approval" @selected(request('status')==='waiting_special_approval')>{{ __('pages.special_approval_pending') }}</option>
                        <option value="approved" @selected(request('status')==='approved')>{{ __('pages.approved') }}</option>
                        <option value="rejected" @selected(request('status')==='rejected')>{{ __('pages.reject') }}</option>
                        <option value="cancel_request" @selected(request('status')==='cancel_request')>{{ __('pages.cancel_request_status') }}</option>
                    </select>
                </div>
            </form>
        </div>

        {{-- テーブル --}}
        <div class="overflow-x-auto">
            <table class="min-w-full text-[13px]">
                <thead class="bg-slate-50 border-b border-slate-200">
                    <tr>
                        <th class="px-5 py-3.5 text-left font-semibold text-slate-700 uppercase text-[11px] tracking-wide">{{ __('pages.application_id_label') }}</th>
                        <th class="px-5 py-3.5 text-left font-semibold text-slate-700 uppercase text-[11px] tracking-wide">{{ __('pages.type_label') }}</th>
                        <th class="px-5 py-3.5 text-left font-semibold text-slate-700 uppercase text-[11px] tracking-wide">{{ __('pages.institution_label') }}</th>
                        <th class="px-5 py-3.5 text-left font-semibold text-slate-700 uppercase text-[11px] tracking-wide">{{ __('pages.employee_label') }}</th>
                        <th class="px-5 py-3.5 text-left font-semibold text-slate-700 uppercase text-[11px] tracking-wide">{{ __('pages.department_label') }}</th>
                        <th class="px-5 py-3.5 text-left font-semibold text-slate-700 uppercase text-[11px] tracking-wide">{{ __('pages.job_type_label') }}</th>
                        <th class="px-5 py-3.5 text-left font-semibold text-slate-700 uppercase text-[11px] tracking-wide">{{ __('pages.start_date_label') }}</th>
                        <th class="px-5 py-3.5 text-left font-semibold text-slate-700 uppercase text-[11px] tracking-wide">{{ __('pages.status_label') }}</th>
                        <th class="px-5 py-3.5 text-right font-semibold text-slate-700 uppercase text-[11px] tracking-wide">{{ __('pages.actions_label') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($applications as $app)
                        <tr class="hover:bg-slate-50/50 transition-colors">
                            <td class="px-5 py-4 font-semibold text-slate-900">
                                <div class="flex items-center gap-2">
                                    <i data-lucide="hash" class="w-4 h-4 text-primary-500"></i>
                                    <span>#APP-{{ str_pad($app->id, 4, '0', STR_PAD_LEFT) }}</span>
                                </div>
                            </td>
                            <td class="px-5 py-4">
                                @php
                                    $typeConfig = [
                                        'new_loan' => ['label' => __('pages.new_loan'), 'class' => 'bg-slate-900 text-white', 'icon' => 'plus'],
                                        'resize' => ['label' => __('pages.size_exchange'), 'class' => 'bg-slate-100 text-slate-700', 'icon' => 'move'],
                                        'return' => ['label' => __('pages.return'), 'class' => 'bg-slate-100 text-slate-700', 'icon' => 'arrow-left'],
                                        'exchange' => ['label' => __('pages.exchange'), 'class' => 'bg-slate-100 text-slate-700', 'icon' => 'refresh-cw'],
                                        'transfer' => ['label' => __('pages.transfer'), 'class' => 'bg-slate-100 text-slate-700', 'icon' => 'arrow-right-left'],
                                    ];
                                    $typeInfo = $typeConfig[$app->type] ?? ['label' => $app->type, 'class' => 'bg-slate-100 text-slate-700', 'icon' => 'file'];
                                @endphp
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full {{ $typeInfo['class'] }} text-[12px] font-medium">
                                    <i data-lucide="{{ $typeInfo['icon'] ?? 'file' }}" class="w-3.5 h-3.5"></i>
                                    {{ $typeInfo['label'] }}
                                </span>
                            </td>
                            <td class="px-5 py-4 text-slate-800">
                                <div class="flex items-center gap-2">
                                    <i data-lucide="building-2" class="w-4 h-4 text-slate-400"></i>
                                    <span>{{ $app->medicalInstitution?->name ?? '-' }}</span>
                                </div>
                            </td>
                            <td class="px-5 py-4">
                                <div class="flex items-center gap-2">
                                    <div class="h-8 w-8 rounded-full bg-slate-100 flex items-center justify-center text-slate-500">
                                        <i data-lucide="user" class="w-4 h-4"></i>
                                    </div>
                                    <div>
                                        <div class="text-[13px] font-medium text-slate-900">{{ $app->employee?->name ?? '-' }}</div>
                                        <div class="text-[11px] text-slate-500">{{ __('pages.employee_number') }}: {{ $app->employee?->staff_no ?? '-' }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-5 py-4 text-slate-600">
                                <div class="flex items-center gap-2">
                                    <i data-lucide="briefcase" class="w-4 h-4 text-slate-400"></i>
                                    <span>{{ $app->department?->name ?? '-' }}</span>
                                </div>
                            </td>
                            <td class="px-5 py-4 text-slate-600">
                                <div class="flex items-center gap-2">
                                    <i data-lucide="briefcase" class="w-4 h-4 text-slate-400"></i>
                                    <span>{{ $app->employee?->positions->first()?->jobType?->name ?? '-' }}</span>
                                </div>
                            </td>
                            <td class="px-5 py-4 text-slate-600">
                                <div class="flex items-center gap-2">
                                    <i data-lucide="calendar" class="w-4 h-4 text-slate-400"></i>
                                    <span>{{ $app->start_date?->format('Y-m-d') ?? '-' }}</span>
                                </div>
                            </td>
                            <td class="px-5 py-4">
                                @php
                                    $statusConfig = [
                                        'pending' => ['label' => __('pages.unprocessed'), 'class' => 'bg-slate-100 text-slate-700', 'icon' => 'clock'],
                                        'waiting_special_approval' => ['label' => __('pages.special_approval_pending'), 'class' => 'bg-slate-900 text-white', 'icon' => 'alert-triangle'],
                                        'approved' => ['label' => __('pages.approved'), 'class' => 'bg-emerald-100 text-emerald-700', 'icon' => 'check-circle'],
                                        'rejected' => ['label' => __('pages.reject'), 'class' => 'bg-red-50 text-red-700', 'icon' => 'x-circle'],
                                        'cancel_request' => ['label' => __('pages.cancel_request_status'), 'class' => 'bg-slate-900 text-white', 'icon' => 'alert-circle'],
                                        'cancel_approved' => ['label' => __('pages.cancel_approved'), 'class' => 'bg-slate-100 text-slate-700', 'icon' => 'check'],
                                        'cancel_rejected' => ['label' => __('pages.cancel_rejected'), 'class' => 'bg-slate-100 text-slate-700', 'icon' => 'x'],
                                    ];
                                    $statusInfo = $statusConfig[$app->status] ?? ['label' => $app->status, 'class' => 'bg-slate-100 text-slate-700', 'icon' => 'circle'];
                                @endphp
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full {{ $statusInfo['class'] }} text-[12px] font-medium">
                                    <i data-lucide="{{ $statusInfo['icon'] ?? 'circle' }}" class="w-3.5 h-3.5"></i>
                                    {{ $statusInfo['label'] }}
                                </span>
                            </td>
                            <td class="px-5 py-4 text-right">
                                <a href="{{ route('admin.applications.show', $app) }}"
                                   class="inline-flex items-center justify-center w-8 h-8 rounded-full border border-slate-300 hover:bg-slate-100 text-slate-500 transition-all"
                                   title="{{ __('pages.view_details') }}">
                                   <i data-lucide="eye" class="w-4 h-4"></i>
                                   {{ __('common.view') }}
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-5 py-12 text-center">
                                <div class="flex flex-col items-center gap-3">
                                    <i data-lucide="clipboard-list" class="w-12 h-12 text-slate-300"></i>
                                    <p class="text-slate-500 text-[14px]">{{ __('pages.no_applications') }}</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- ページネーション --}}
        @if($applications->hasPages())
            <div class="px-6 py-4 border-t border-slate-200 flex items-center justify-between text-[13px] text-slate-600">
                <span>{{ __('pages.showing_results', ['firstItem' => $applications->firstItem() ?? 0, 'lastItem' => $applications->lastItem() ?? 0, 'total' => $applications->total()]) }}</span>
                <div class="flex items-center gap-2">
                    {{ $applications->onEachSide(1)->links('pagination::tailwind') }}
                </div>
            </div>
        @endif
    </div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Ensure Lucide icons are initialized for this page
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
        // Retry after delays to catch all icons
        setTimeout(() => {
            if (typeof lucide !== 'undefined') lucide.createIcons();
        }, 100);
        setTimeout(() => {
            if (typeof lucide !== 'undefined') lucide.createIcons();
        }, 500);
    }
    
    // Filter toggle functionality
    const filterToggleBtn = document.getElementById('filter-toggle-btn');
    const filterDropdown = document.getElementById('filter-dropdown');
    const filterChevron = document.getElementById('filter-chevron');
    
    if (filterToggleBtn && filterDropdown) {
        // Check if any filters are active (show dropdown if filters are applied)
        const urlParams = new URLSearchParams(window.location.search);
        const hasActiveFilters = urlParams.has('institution_id') || urlParams.has('status') || urlParams.has('type');
        
        if (hasActiveFilters) {
            filterDropdown.classList.remove('hidden');
            if (filterChevron) {
                filterChevron.classList.add('rotate-180');
            }
        }
        
        filterToggleBtn.addEventListener('click', function() {
            filterDropdown.classList.toggle('hidden');
            if (filterChevron) {
                filterChevron.classList.toggle('rotate-180');
            }
            // Reinitialize icons after toggle
            if (typeof lucide !== 'undefined') {
                setTimeout(() => lucide.createIcons(), 50);
            }
        });
    }
});
</script>
@endpush
@endsection
