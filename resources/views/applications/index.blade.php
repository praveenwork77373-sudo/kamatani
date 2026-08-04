@extends('layouts.app')

@section('title', __('pages.ui_8123ac616d'))
@section('page_title', __('pages.applications_history_title'))

@section('content')
    <div class="mb-6">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-slate-900 tracking-tight">{{ __('pages.applications_history_title') }}</h1>
                <p class="text-[14px] text-slate-600 mt-1.5">{{ __('pages.ui_3f94b4803b') }}</p>
            </div>
            <a href="{{ route('applications.create') }}"
               class="inline-flex items-center gap-2 px-5 py-2.5 rounded-lg bg-primary-600 hover:bg-primary-700 text-white text-[14px] font-medium shadow-md transition-all">
                <i class="ph ph-plus text-base"></i>
                {{ __('pages.new_application_short') }}
            </a>
        </div>
    </div>

    <div class="ui-card overflow-hidden">
        <div class="ui-table-wrap">
            <table class="ui-table">
                <thead>
                    <tr>
                        <th>{{ __('pages.application_id') }}</th>
                        <th>{{ __('common.employees') }}</th>
                        <th class="hidden md:table-cell">{{ __('pages.department') }}</th>
                        <th>{{ __('pages.type') }}</th>
                        <th>{{ __('pages.status') }}</th>
                        <th class="hidden sm:table-cell">{{ __('pages.application_date') }}</th>
                        <th class="ui-table__actions">{{ __('common.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($applications as $app)
                        <tr>
                            <td class="font-semibold">
                                #APP-{{ str_pad($app->id, 4, '0', STR_PAD_LEFT) }}
                            </td>
                            <td>
                                <div class="font-medium">{{ $app->employee?->name ?? '-' }}</div>
                                <div class="text-[11px] text-slate-500">{{ $app->employee?->staff_no ?? '-' }}</div>
                            </td>
                            <td class="hidden md:table-cell text-slate-600">{{ $app->department?->name ?? '-' }}</td>
                            <td>
                                <span class="ui-status-pill ui-status-pill--{{ $app->typePillTone() }}">{{ $app->typeLabel() }}</span>
                            </td>
                            <td>
                                <span class="ui-status-pill ui-status-pill--{{ $app->statusPillTone() }}">{{ $app->statusLabel() }}</span>
                            </td>
                            <td class="hidden sm:table-cell text-slate-600 whitespace-nowrap">{{ $app->created_at->format('Y-m-d') }}</td>
                            <td class="ui-table__actions">
                                <div class="ui-table__actions-row">
                                    <x-table-action
                                        :href="route('applications.show', $app)"
                                        icon="eye"
                                        :label="__('common.view')"
                                    />
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-5 py-12 text-center">
                                <div class="flex flex-col items-center gap-3">
                                    <i data-lucide="clipboard-list" class="w-12 h-12 text-slate-300"></i>
                                    <p class="text-slate-500 text-[14px]">{{ __('pages.no_applications') }}</p>
                                    <a href="{{ route('applications.create') }}" class="ui-btn ui-btn-primary">
                                        {{ __('pages.new_application_short') }}{{ __('pages.create_one') }}
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

            <x-table-pagination :paginator="$applications" />

    </div>


@endsection
