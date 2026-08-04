@extends('layouts.app')

@section('title', __('pages.employee_list') . ' | ' . __('auth.system_title'))
@section('header_category', __('menu.employees'))
@section('page_title', __('pages.employee_list'))

@section('content')
    <div class="mb-6">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-3">
            <p class="text-[14px] text-slate-600 min-w-0">
                {{ __('pages.employee_list_description') }}
            </p>
            @if($customer && in_array($customer->role, ['institution_admin', 'department_admin'], true))
                <div class="ui-page-actions">
                    <form method="POST" action="{{ route('employees.export_csv') }}" class="inline-flex shrink-0">
                        @csrf
                        <input type="hidden" name="q" value="{{ request('q') }}">
                        <input type="hidden" name="department_id" value="{{ request('department_id') }}">
                        <button type="submit" class="ui-page-action-btn">
                            <i data-lucide="download" class="w-4 h-4"></i>
                            <span>{{ __('pages.csv_export') }}</span>
                        </button>
                    </form>
                    <form method="POST" action="{{ route('employees.import_csv') }}" enctype="multipart/form-data" class="inline-flex shrink-0" id="csv-import-form">
                        @csrf
                        <label class="ui-page-action-btn cursor-pointer">
                            <i data-lucide="upload" class="w-4 h-4"></i>
                            <span>{{ __('pages.csv_import') }}</span>
                            <input type="file" name="csv_file" accept=".csv,.txt" class="hidden" onchange="document.getElementById('csv-import-form').submit()">
                        </label>
                    </form>
                    <a href="{{ route('employees.create') }}" class="ui-page-action-btn ui-page-action-btn--primary">
                        <i data-lucide="plus" class="w-4 h-4"></i>
                        <span>{{ __('pages.add_employee') }}</span>
                    </a>
                </div>
            @endif
        </div>
    </div>

    {{-- Statistics Cards --}}
    <x-stat-grid :cols="5">
        <x-stat-card tone="blue" icon="users" :value="$stats['total_employees'] ?? 0" :label="__('pages.total_employees')" :badge="__('pages.total')" />
        <x-stat-card
            tone="emerald"
            icon="check-circle"
            :value="$stats['employed_employees'] ?? 0"
            :label="__('pages.employed')"
            :badge="__('pages.employed')"
            :hint="(($stats['total_employees'] ?? 0) > 0 ? round(($stats['employed_employees'] / $stats['total_employees']) * 100, 1) : 0).'% '.__('pages.percentage')"
        />
        <x-stat-card tone="amber" icon="pause-circle" :value="$stats['on_leave_employees'] ?? 0" :label="__('pages.on_leave')" :badge="__('pages.on_leave')" />
        <x-stat-card tone="slate" icon="x-circle" :value="$stats['retired_employees'] ?? 0" :label="__('pages.retired')" :badge="__('pages.retired')" />
        <x-stat-card
            tone="purple"
            icon="plus-circle"
            :value="$stats['new_employees_this_month'] ?? 0"
            :label="__('pages.new_registration')"
            :badge="__('pages.this_month')"
            :hint="__('pages.new_this_month')"
        />
    </x-stat-grid>

    <div class="space-y-6">
        @php
            $empFiltersActive = filled($selectedDepartmentId ?? null) || filled(request('status')) || filled($searchQuery);
        @endphp
        <x-filter-bar :action="route('employees.index')" form-id="employee-filter-form" :active="$empFiltersActive">
            @if($customer && $customer->role === 'institution_admin')
                <div class="ui-filter-field">
                    <label class="ui-filter-field__label">{{ __('pages.department') }}</label>
                    <select name="department_id" id="department-filter" class="ui-select">
                        <option value="">{{ __('pages.all_departments') }}</option>
                        @foreach($departments as $dept)
                            <option value="{{ $dept->id }}" @selected($selectedDepartmentId == $dept->id)>{{ $dept->name }}</option>
                        @endforeach
                    </select>
                </div>
            @endif
            <div class="ui-filter-field ui-filter-field--sm">
                <label class="ui-filter-field__label">{{ __('pages.status') }}</label>
                <select name="status" class="ui-select">
                    <option value="">{{ __('pages.all_statuses') }}</option>
                    <option value="employed" @selected(request('status') == 'employed')>{{ __('pages.employed') }}</option>
                    <option value="on_leave" @selected(request('status') == 'on_leave')>{{ __('pages.on_leave') }}</option>
                    <option value="retired" @selected(request('status') == 'retired')>{{ __('pages.retired') }}</option>
                </select>
            </div>
            <div class="ui-filter-field ui-filter-field--grow">
                <label class="ui-filter-field__label">{{ __('common.search') }}</label>
                <input type="text" name="q" id="search-input" value="{{ $searchQuery }}"
                       placeholder="{{ __('pages.search_employee_placeholder') }}"
                       class="ui-input">
            </div>
            <x-slot:actions>
                <button type="submit" class="ui-btn ui-btn-primary">
                    <i data-lucide="search" class="w-4 h-4"></i>
                    {{ __('common.search') }}
                </button>
                <a href="{{ route('employees.index') }}" class="ui-btn ui-btn-secondary" title="{{ __('common.reset') }}">
                    <i data-lucide="x" class="w-4 h-4"></i>
                </a>
            </x-slot:actions>
        </x-filter-bar>

        {{-- Employee List --}}
        <div class="bg-white border border-slate-200 rounded-xl shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full text-[14px]">
                        <thead class="bg-slate-50 border-b border-slate-200">
                            <tr>
                                <th class="px-5 py-3.5 text-left font-semibold text-slate-700">{{ __('pages.staff_number') }}</th>
                                <th class="px-5 py-3.5 text-left font-semibold text-slate-700">{{ __('pages.employee_name') }}</th>
                                <th class="px-5 py-3.5 text-left font-semibold text-slate-700">{{ __('pages.department') }}</th>
                                <th class="px-5 py-3.5 text-left font-semibold text-slate-700">{{ __('pages.status') }}</th>
                                <th class="px-5 py-3.5 text-left font-semibold text-slate-700">{{ __('pages.sizes') }}</th>
                                <th class="px-5 py-3.5 text-center font-semibold text-slate-700">{{ __('pages.actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200">
                            @forelse($employees as $employee)
                                <tr class="hover:bg-slate-50 transition-colors">
                                    <td class="px-5 py-4 text-slate-900 font-mono text-[13px]">
                                        <span class="inline-flex items-center gap-1.5 px-2 py-1 rounded-md bg-slate-100 text-slate-700">
                                            <i data-lucide="hash" class="w-3.5 h-3.5"></i>
                                            {{ $employee->staff_no }}
                                        </span>
                                    </td>
                                    <td class="px-5 py-4 text-slate-900 font-medium">
                                        <span class="truncate">{{ $employee->name }}</span>
                                    </td>
                                    <td class="px-5 py-4 text-slate-700">
                                        <div class="flex items-start gap-2">
                                            <i data-lucide="building-2" class="w-4 h-4 text-slate-400 mt-0.5 shrink-0"></i>
                                            <span class="text-[13px]">
                                                @php
                                                    $primary = $employee->primaryDepartment?->name;
                                                    $others = collect($employee->affiliationDepartmentNames())
                                                        ->reject(fn ($n) => $n === $primary)
                                                        ->values();
                                                @endphp
                                                @if($primary)
                                                    <span class="font-medium">{{ __('pages.primary_department') }}: {{ $primary }}</span>
                                                    @if($others->isNotEmpty())
                                                        <span class="text-slate-500"> · {{ __('pages.concurrent_affiliation') }}: {{ $others->implode(app()->getLocale() === 'ja' ? '、' : ', ') }}</span>
                                                    @endif
                                                @else
                                                    {{ $others->isNotEmpty() ? $others->implode(' / ') : '-' }}
                                                @endif
                                            </span>
                                        </div>
                                    </td>
                                    <td class="px-5 py-4">
                                        @if($employee->status === 'employed')
                                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[12px] font-medium bg-emerald-100 text-emerald-800 border border-emerald-200">
                                                <i data-lucide="check-circle" class="w-3.5 h-3.5"></i>
                                                {{ __('pages.employed') }}
                                            </span>
                                        @elseif($employee->status === 'on_leave')
                                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[12px] font-medium bg-amber-100 text-amber-800 border border-amber-200">
                                                <i data-lucide="pause-circle" class="w-3.5 h-3.5"></i>
                                                {{ __('pages.on_leave') }}
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[12px] font-medium bg-slate-100 text-slate-800 border border-slate-200">
                                                <i data-lucide="x-circle" class="w-3.5 h-3.5"></i>
                                                {{ __('pages.retired') }}
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-5 py-4 text-slate-600 text-[13px]">
                                        <div class="flex items-center gap-2 flex-wrap">
                                            @if($employee->top_size)
                                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded bg-blue-50 text-blue-700 border border-blue-200">
                                                    <i data-lucide="shirt" class="w-3 h-3"></i>
                                                    {{ $employee->top_size }}
                                                </span>
                                            @endif
                                            @if($employee->bottom_size)
                                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded bg-purple-50 text-purple-700 border border-purple-200">
                                                    <i data-lucide="package" class="w-3 h-3"></i>
                                                    {{ $employee->bottom_size }}
                                                </span>
                                            @endif
                                            @if($employee->shoe_size)
                                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded bg-amber-50 text-amber-700 border border-amber-200">
                                                    <i data-lucide="footprints" class="w-3 h-3"></i>
                                                    {{ $employee->shoe_size }}
                                                </span>
                                            @endif
                                            @if(!$employee->top_size && !$employee->bottom_size && !$employee->shoe_size)
                                                <span class="text-slate-400">-</span>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="px-5 py-4 text-center ui-table__actions">
                                        <div class="ui-table__actions-row justify-center">
                                            <x-table-action
                                                :href="route('employees.show', $employee)"
                                                icon="eye"
                                                :label="__('common.view')"
                                            />
                                            @if($customer && in_array($customer->role, ['institution_admin', 'department_admin'], true))
                                                <x-table-action
                                                    :href="route('employees.edit', $employee)"
                                                    icon="pencil"
                                                    tone="muted"
                                                    :label="__('common.edit')"
                                                />
                                                <x-table-action
                                                    :href="route('employees.destroy', $employee)"
                                                    icon="trash-2"
                                                    tone="danger"
                                                    method="DELETE"
                                                    :label="__('common.delete')"
                                                    :confirm="__('pages.confirm_delete_employee')"
                                                />
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-5 py-12 text-center text-slate-500">
                                        <div class="flex flex-col items-center gap-3">
                                            <div class="w-16 h-16 rounded-full bg-slate-100 flex items-center justify-center">
                                                <i data-lucide="users" class="w-8 h-8 text-slate-300"></i>
                                            </div>
                                            <p class="text-[14px] font-medium">{{ __('pages.no_employees_found') }}</p>
                                            <p class="text-[12px] text-slate-400">{{ __('pages.change_search_conditions') }}</p>
                                            @if($customer && in_array($customer->role, ['institution_admin', 'department_admin'], true))
                                                <a href="{{ route('employees.create') }}" class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-primary-600 hover:bg-primary-700 text-white text-[13px] font-medium shadow-md transition-all">
                                                    <i data-lucide="plus" class="w-4 h-4"></i>
                                                    {{ __('pages.add_employee') }}
                                                </a>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

            <x-table-pagination :paginator="$employees" />

                

            </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('employee-filter-form');
            const departmentFilter = document.getElementById('department-filter');
            const searchInput = document.getElementById('search-input');
            
            // Auto-submit when department changes
            if (departmentFilter) {
                departmentFilter.addEventListener('change', function() {
                    form.submit();
                });
            }
            
            // Handle search input - submit on Enter key
            if (searchInput) {
                searchInput.addEventListener('keydown', function(e) {
                    if (e.key === 'Enter' || e.keyCode === 13) {
                        e.preventDefault();
                        form.submit();
                    }
                });
            }
            
            // Initialize Lucide icons
            if (typeof lucide !== 'undefined') {
                lucide.createIcons();
                setTimeout(() => { if (typeof lucide !== 'undefined') lucide.createIcons(); }, 100);
                setTimeout(() => { if (typeof lucide !== 'undefined') lucide.createIcons(); }, 500);
            }
        });
    </script>
@endsection
