@extends('layouts.app')

@section('title', __('menu.orders') . ' | ' . __('auth.system_title'))
@section('page_title', __('menu.orders'))

@section('content')
<div class="ui-page-header">
    <div>
        <h2 class="text-xl font-bold text-slate-900 mb-1">{{ __('menu.orders') }}</h2>
        <p class="text-[14px] text-slate-500">{{ __('pages.orders_description') }}</p>
    </div>
    <x-table-action :href="route('admin.applications.index')" icon="clipboard-list" :label="__('pages.to_applications_list')" tone="muted" />
</div>

@php
    $ordersFiltersActive = filled(request('institution_id')) || filled(request('status'));
@endphp
<x-filter-bar :action="route('admin.orders.index')" form-id="orders-filter-form" :active="$ordersFiltersActive">
    <div class="ui-filter-field">
        <label class="ui-filter-field__label">{{ __('pages.medical_institution') }}</label>
        <select name="institution_id" class="ui-select">
            <option value="">{{ __('pages.all_institutions') }}</option>
            @foreach($institutions as $inst)
                <option value="{{ $inst->id }}" @selected((string) request('institution_id') === (string) $inst->id)>{{ $inst->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="ui-filter-field ui-filter-field--sm">
        <label class="ui-filter-field__label">{{ __('pages.status') }}</label>
        <select name="status" class="ui-select">
            <option value="">{{ __('pages.all_statuses') }}</option>
            <option value="in_progress" @selected(request('status') === 'in_progress')>{{ __('pages.in_progress') }}</option>
            <option value="completed" @selected(request('status') === 'completed')>{{ __('pages.completed') }}</option>
        </select>
    </div>
    <x-slot:actions>
        <button type="submit" class="ui-btn ui-btn-secondary">{{ __('pages.apply_filter') }}</button>
        @if($ordersFiltersActive)
            <a href="{{ route('admin.orders.index') }}" class="ui-btn ui-btn-secondary" title="{{ __('common.reset') }}">
                <i data-lucide="x" class="w-4 h-4"></i>
            </a>
        @endif
    </x-slot:actions>
</x-filter-bar>

<form method="POST" action="{{ route('admin.applications.documents.bulk_issue') }}">
    @csrf
    <div class="mb-3">
        <button type="submit" class="ui-btn ui-btn-primary inline-flex items-center gap-2">
            <i data-lucide="file-stack" class="w-4 h-4"></i>
            {{ __('pages.bulk_issue_documents') }}
        </button>
    </div>

    <div class="ui-card overflow-hidden">
        <div class="ui-table-wrap">
            <table class="ui-table">
                <thead>
                    <tr>
                        <th><input type="checkbox" id="orders-select-all" class="rounded"></th>
                        <th>{{ __('pages.application_id_label') }}</th>
                        <th>{{ __('pages.type_label') }}</th>
                        <th class="hidden md:table-cell">{{ __('pages.institution_label') }}</th>
                        <th>{{ __('pages.employee_label') }}</th>
                        <th class="hidden sm:table-cell">{{ __('pages.accepted_at') }}</th>
                        <th>{{ __('pages.status') }}</th>
                        <th class="hidden lg:table-cell">{{ __('pages.documents_col') }}</th>
                        <th class="ui-table__actions">{{ __('common.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($applications as $app)
                        <tr>
                            <td><input type="checkbox" name="application_ids[]" value="{{ $app->id }}" class="order-check rounded"></td>
                            <td class="font-mono font-semibold">#APP-{{ str_pad($app->id, 4, '0', STR_PAD_LEFT) }}</td>
                            <td>{{ __('pages.'.$app->type) === 'pages.'.$app->type ? $app->type : __('pages.'.$app->type) }}</td>
                            <td class="hidden md:table-cell">{{ $app->medicalInstitution?->name }}</td>
                            <td>
                                <div class="font-medium">{{ $app->employee?->name }}</div>
                                <div class="text-[11px] text-slate-500">{{ $app->employee?->staff_no }}</div>
                            </td>
                            <td class="hidden sm:table-cell whitespace-nowrap">{{ optional($app->accepted_at)->format('Y-m-d H:i') ?? '—' }}</td>
                            <td>
                                @php
                                    $pill = match($app->status) {
                                        'in_progress', 'approved' => 'brand',
                                        'completed' => 'success',
                                        default => 'muted',
                                    };
                                @endphp
                                <span class="ui-status-pill ui-status-pill--{{ $pill }}">{{ $app->statusLabel() }}</span>
                            </td>
                            <td class="hidden lg:table-cell">{{ __('pages.documents_count', ['count' => $app->documents->count()]) }}</td>
                            <td class="ui-table__actions">
                                <div class="ui-table__actions-row">
                                    <x-table-action
                                        :href="route('admin.applications.show', $app)"
                                        icon="eye"
                                        :label="__('pages.details')"
                                    />
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="9" class="px-4 py-10 text-center text-slate-500">{{ __('pages.no_orders') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</form>

<div class="mt-4">{{ $applications->links() }}</div>

@push('scripts')
<script>
document.getElementById('orders-select-all')?.addEventListener('change', function () {
    document.querySelectorAll('.order-check').forEach(cb => { cb.checked = this.checked; });
});
</script>
@endpush
@endsection
