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
    <x-stat-grid :cols="4">
        <x-stat-card
            tone="amber"
            icon="clock"
            :value="$stats['pending'] ?? 0"
            :label="__('pages.pending_applications')"
            :badge="__('pages.action_required_badge')"
            :hint="__('pages.action_required')"
        />
        <x-stat-card
            tone="orange"
            icon="alert-triangle"
            :value="$stats['special'] ?? 0"
            :label="__('pages.special_approval_pending')"
            :badge="__('pages.special_approval_pending')"
            :hint="__('pages.limit_exceeded_applications')"
        />
        <x-stat-card
            tone="emerald"
            icon="check-circle"
            :value="$stats['approved_this_month'] ?? 0"
            :label="__('pages.approved_this_month')"
            :badge="__('pages.approved_this_month')"
            :hint="__('pages.this_month_count')"
        />
        <x-stat-card
            tone="red"
            icon="x-circle"
            :value="$stats['cancellations'] ?? 0"
            :label="__('pages.cancellation_requests')"
            :badge="__('pages.cancellation_requests')"
            :hint="__('pages.awaiting_approval')"
        />
    </x-stat-grid>

    <div class="space-y-6">
        {{-- Horizontal filters always visible --}}
        <div class="bg-white border border-slate-200 rounded-xl shadow-sm overflow-hidden">
            <form method="GET" action="{{ route('admin.applications.index') }}" class="p-4">
                <x-per-page-field />
                @foreach(request()->except(['institution_id','status','page']) as $key => $value)
                    <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                @endforeach
                <div class="flex flex-col lg:flex-row lg:items-end gap-3">
                    <div class="flex-1 min-w-0">
                        <label class="block text-[12px] font-semibold text-slate-600 mb-1.5">{{ __('pages.medical_institution') }}</label>
                        <select name="institution_id" onchange="this.form.submit()" class="ui-select">
                            <option value="">{{ __('pages.all_institutions') }}</option>
                            @foreach($institutions as $inst)
                                <option value="{{ $inst->id }}" @selected(request('institution_id')==$inst->id)>{{ $inst->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="flex-1 min-w-0">
                        <label class="block text-[12px] font-semibold text-slate-600 mb-1.5">{{ __('pages.status') }}</label>
                        <select name="status" onchange="this.form.submit()" class="ui-select">
                            <option value="">{{ __('pages.all_statuses') }}</option>
                            <option value="pending" @selected(request('status')==='pending')>{{ __('pages.unprocessed') }}</option>
                            <option value="waiting_special_approval" @selected(request('status')==='waiting_special_approval')>{{ __('pages.special_approval_pending') }}</option>
                            <option value="in_progress" @selected(request('status')==='in_progress')>対応中</option>
                            <option value="completed" @selected(request('status')==='completed')>完了</option>
                            <option value="rejected" @selected(request('status')==='rejected')>{{ __('pages.reject') }}</option>
                            <option value="cancel_request" @selected(request('status')==='cancel_request')>{{ __('pages.cancel_request_status') }}</option>
                        </select>
                    </div>
                    <div class="flex gap-2 shrink-0">
                        <a href="{{ route('admin.applications.index', request()->only('type')) }}" class="ui-btn ui-btn-secondary" title="{{ __('common.reset') }}">
                            <i data-lucide="x" class="w-4 h-4"></i>
                        </a>
                        <a href="{{ route('admin.applications.export', request()->query()) }}" class="ui-btn ui-btn-secondary">
                            <i data-lucide="download" class="w-4 h-4"></i>
                            {{ __('pages.csv_export') }}
                        </a>
                    </div>
                </div>
            </form>
        </div>

        {{-- テーブルセクション --}}
        <div class="bg-white border border-slate-200 rounded-xl shadow-sm overflow-hidden">
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
                                            'pending' => ['label' => '申請', 'class' => 'bg-slate-100 text-slate-700', 'icon' => 'clock'],
                                            'waiting_special_approval' => ['label' => __('pages.special_approval_pending'), 'class' => 'bg-slate-900 text-white', 'icon' => 'alert-triangle'],
                                            'in_progress' => ['label' => '対応中', 'class' => 'bg-blue-100 text-blue-700', 'icon' => 'loader'],
                                            'completed' => ['label' => '完了', 'class' => 'bg-emerald-100 text-emerald-700', 'icon' => 'check-circle'],
                                            'approved' => ['label' => '対応中', 'class' => 'bg-blue-100 text-blue-700', 'icon' => 'loader'],
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

            <x-table-pagination :paginator="$applications" />


            {{-- ページネーション --}}

        </div>
    </div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
        setTimeout(() => {
            if (typeof lucide !== 'undefined') lucide.createIcons();
        }, 100);
        setTimeout(() => {
            if (typeof lucide !== 'undefined') lucide.createIcons();
        }, 500);
    }
});
</script>
@endpush
@endsection
