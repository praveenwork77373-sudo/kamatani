@extends('layouts.app')

@section('title', __('menu.uniform_categories'))
@section('page_title', __('menu.uniform_categories'))
@section('header_category', __('menu.uniform_categories'))

@section('content')
<div class="ui-page-header">
    <div>
        <h1 class="text-xl sm:text-2xl font-bold text-slate-900 tracking-tight">{{ __('menu.uniform_categories') }}</h1>
        <p class="text-[13px] text-slate-600 mt-1">{{ __('pages.uniform_categories_description') }}</p>
    </div>
    <a href="{{ route('admin.uniform-categories.create') }}" class="ui-btn ui-btn-primary">{{ __('pages.new_registration') }}</a>
</div>

<div class="ui-card overflow-hidden">
    <div class="ui-table-wrap">
        <table class="ui-table">
            <thead>
                <tr>
                    <th>{{ __('pages.code') }}</th>
                    <th>{{ __('pages.name') }}</th>
                    <th class="hidden sm:table-cell">{{ __('pages.size_scheme') }}</th>
                    <th class="hidden md:table-cell text-right">{{ __('pages.sort_order') }}</th>
                    <th>{{ __('pages.status') }}</th>
                    <th class="ui-table__actions">{{ __('common.actions') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($categories as $category)
                    <tr>
                        <td class="font-mono">{{ $category->code }}</td>
                        <td>{{ $category->name }}</td>
                        <td class="hidden sm:table-cell">{{ __('pages.size_scheme_'.$category->size_scheme) }}</td>
                        <td class="hidden md:table-cell text-right">{{ $category->sort_order }}</td>
                        <td>
                            @if($category->is_active)
                                <span class="ui-status-pill ui-status-pill--success">{{ __('pages.active') }}</span>
                            @else
                                <span class="ui-status-pill ui-status-pill--muted">{{ __('pages.inactive') }}</span>
                            @endif
                        </td>
                        <td class="ui-table__actions">
                            <div class="ui-table__actions-row">
                                <x-table-action
                                    :href="route('admin.uniform-categories.edit', $category)"
                                    icon="pencil"
                                    :label="__('common.edit')"
                                />
                                <x-table-action
                                    :href="route('admin.uniform-categories.destroy', $category)"
                                    method="DELETE"
                                    icon="trash-2"
                                    tone="danger"
                                    :label="__('common.delete')"
                                    confirm="{{ __('pages.confirm_deactivate_or_delete') }}"
                                />
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-8 text-center text-slate-500">{{ __('pages.no_categories') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <x-table-pagination :paginator="$categories" />
</div>
@endsection
