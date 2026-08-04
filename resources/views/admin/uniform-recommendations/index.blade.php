@extends('layouts.app')

@section('title', __('menu.uniform_recommendations'))
@section('page_title', __('menu.uniform_recommendations'))

@section('content')

<div class="ui-page-header">
    <div>
        <h1 class="text-xl sm:text-2xl font-bold text-slate-900 tracking-tight">{{ __('menu.uniform_recommendations') }}</h1>
        <p class="text-[13px] text-slate-600 mt-1">{{ __('pages.recommendations_desc') }}</p>
    </div>
    <a href="{{ route('admin.uniform-recommendations.create') }}" class="ui-btn ui-btn-primary">{{ __('pages.new_registration') }}</a>
</div>

<form method="GET" class="mb-4 flex flex-wrap gap-2 items-end">
    <x-per-page-field />
    <div>
        <label class="block text-xs text-slate-500 mb-1">{{ __('pages.medical_institution') }}</label>
        <select name="medical_institution_id" class="rounded-lg border-slate-300 text-sm">
            <option value="">{{ __('pages.all') }}</option>
            @foreach($institutions as $inst)
                <option value="{{ $inst->id }}" @selected((string) request('medical_institution_id') === (string) $inst->id)>{{ $inst->name }}</option>
            @endforeach
        </select>
    </div>
    <button class="px-3 py-2 rounded-lg border text-sm">{{ __('pages.apply_filter') }}</button>
</form>

<div class="ui-card overflow-hidden">
    <div class="ui-table-wrap">
        <table class="ui-table">
            <thead>
                <tr>
                    <th>{{ __('pages.medical_institution') }}</th>
                    <th class="hidden md:table-cell">{{ __('pages.department') }}</th>
                    <th class="hidden lg:table-cell">{{ __('pages.job_type') }}</th>
                    <th>{{ __('menu.menu_products') }}</th>
                    <th>{{ __('pages.classification') }}</th>
                    <th class="ui-table__actions">{{ __('common.actions') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($recommendations as $row)
                    <tr>
                        <td>{{ $row->medicalInstitution?->name }}</td>
                        <td class="hidden md:table-cell">{{ $row->department?->name ?? '—' }}</td>
                        <td class="hidden lg:table-cell">{{ $row->jobType?->name ?? '—' }}</td>
                        <td>
                            <div class="font-mono text-[12px] text-slate-500">{{ $row->uniformProduct?->product_code }}</div>
                            <div>{{ $row->uniformProduct?->name }} ({{ $row->uniformProduct?->size }})</div>
                        </td>
                        <td>
                            @if($row->is_required)
                                <span class="ui-status-pill ui-status-pill--warning">{{ __('pages.required_flag') }}</span>
                            @endif
                            @if($row->is_recommended)
                                <span class="ui-status-pill ui-status-pill--info">{{ __('pages.recommended') }}</span>
                            @endif
                        </td>
                        <td class="ui-table__actions">
                            <div class="ui-table__actions-row">
                                <x-table-action
                                    :href="route('admin.uniform-recommendations.edit', $row)"
                                    icon="pencil"
                                    :label="__('common.edit')"
                                />
                                <x-table-action
                                    :href="route('admin.uniform-recommendations.destroy', $row)"
                                    method="DELETE"
                                    icon="trash-2"
                                    tone="danger"
                                    :label="__('common.delete')"
                                    confirm="{{ __('pages.confirm_delete_generic') }}"
                                />
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-8 text-center text-slate-500">{{ __('pages.no_registrations') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="p-4">{{ $recommendations->links() }}</div>
</div>
@endsection
