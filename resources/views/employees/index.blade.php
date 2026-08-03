@extends('layouts.app')

@section('title', __('pages.employee_list') . ' | ' . __('auth.system_title'))
@section('header_category', __('menu.employees'))
@section('page_title', __('pages.employee_list'))

@section('content')
    <div class="mb-6">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <p class="text-[14px] text-slate-600">
                {{ __('pages.employee_list_description') }}
            </p>
            <div class="flex items-center gap-3 flex-wrap">
                @if($customer && in_array($customer->role, ['institution_admin', 'department_admin'], true))
                    <form method="POST" action="{{ route('employees.export_csv') }}" class="inline">
                        @csrf
                        <input type="hidden" name="q" value="{{ request('q') }}">
                        <input type="hidden" name="department_id" value="{{ request('department_id') }}">
                        <button type="submit" class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-white border border-slate-300 text-slate-700 hover:bg-slate-50 text-[14px] font-medium shadow-sm transition-all">
                            <i data-lucide="download" class="w-4 h-4"></i>
                            {{ __('pages.csv_export') }}
                        </button>
                    </form>
                    <form method="POST" action="{{ route('employees.import_csv') }}" enctype="multipart/form-data" class="inline" id="csv-import-form">
                        @csrf
                        <label class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-white border border-slate-300 text-slate-700 hover:bg-slate-50 text-[14px] font-medium shadow-sm transition-all cursor-pointer">
                            <i data-lucide="upload" class="w-4 h-4"></i>
                            {{ __('pages.csv_import') }}
                            <input type="file" name="csv_file" accept=".csv,.txt" class="hidden" onchange="document.getElementById('csv-import-form').submit()">
                        </label>
                    </form>
                    <a href="{{ route('employees.create') }}" class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-primary-600 hover:bg-primary-700 text-white text-[14px] font-medium shadow-md transition-all">
                        <i data-lucide="plus" class="w-4 h-4"></i>
                        {{ __('pages.add_employee') }}
                    </a>
                @endif
            </div>
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
        {{-- Horizontal filters above table --}}
        <div class="bg-white border border-slate-200 rounded-xl shadow-sm overflow-hidden">
            <form method="GET" action="{{ route('employees.index') }}" id="employee-filter-form" class="p-4">
                <x-per-page-field />
                <div class="flex flex-col lg:flex-row lg:items-end gap-3">
                    @if($customer && $customer->role === 'institution_admin')
                        <div class="flex-1 min-w-0">
                            <label class="block text-[12px] font-semibold text-slate-600 mb-1.5">{{ __('pages.department') }}</label>
                            <select name="department_id" id="department-filter" class="ui-select">
                                <option value="">{{ __('pages.all_departments') }}</option>
                                @foreach($departments as $dept)
                                    <option value="{{ $dept->id }}" @selected($selectedDepartmentId == $dept->id)>{{ $dept->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif
                    <div class="flex-1 min-w-0">
                        <label class="block text-[12px] font-semibold text-slate-600 mb-1.5">{{ __('pages.status') }}</label>
                        <select name="status" class="ui-select">
                            <option value="">{{ __('pages.all_statuses') }}</option>
                            <option value="employed" @selected(request('status') == 'employed')>{{ __('pages.employed') }}</option>
                            <option value="on_leave" @selected(request('status') == 'on_leave')>{{ __('pages.on_leave') }}</option>
                            <option value="retired" @selected(request('status') == 'retired')>{{ __('pages.retired') }}</option>
                        </select>
                    </div>
                    <div class="flex-[1.5] min-w-0">
                        <label class="block text-[12px] font-semibold text-slate-600 mb-1.5">{{ __('common.search') }}</label>
                        <input type="text" name="q" id="search-input" value="{{ $searchQuery }}"
                               placeholder="{{ __('pages.search_employee_placeholder') }}"
                               class="ui-input">
                    </div>
                    <div class="flex gap-2 shrink-0">
                        <button type="submit" class="ui-btn ui-btn-primary">
                            <i data-lucide="search" class="w-4 h-4"></i>
                            {{ __('common.search') }}
                        </button>
                        <a href="{{ route('employees.index') }}" class="ui-btn ui-btn-secondary" title="{{ __('common.reset') }}">
                            <i data-lucide="x" class="w-4 h-4"></i>
                        </a>
                    </div>
                </div>
            </form>
        </div>

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
                                        <div class="flex items-center gap-2">
                                            <div class="w-8 h-8 rounded-full bg-gradient-to-br from-primary-400 to-primary-600 flex items-center justify-center text-white text-[12px] font-semibold">
                                                {{ mb_substr($employee->name, 0, 1) }}
                                            </div>
                                            <span>{{ $employee->name }}</span>
                                        </div>
                                    </td>
                                    <td class="px-5 py-4 text-slate-700">
                                        <div class="flex items-center gap-2">
                                            <i data-lucide="briefcase" class="w-4 h-4 text-slate-400"></i>
                                            <span>{{ $employee->primaryDepartment->name ?? '-' }}</span>
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
                                    <td class="px-5 py-4 text-center">
                                        <div class="flex items-center justify-center gap-2">
                                            <a href="{{ route('employees.show', $employee) }}" 
                                               class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-primary-50 hover:bg-primary-100 text-primary-700 text-[13px] font-medium transition-all border border-primary-200 hover:border-primary-300">
                                                <i data-lucide="eye" class="w-4 h-4"></i>
                                                {{ __('common.view') }}
                                            </a>
                                            @if($customer && in_array($customer->role, ['institution_admin', 'department_admin'], true))
                                                <a href="{{ route('employees.edit', $employee) }}" 
                                                   class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-slate-50 hover:bg-slate-100 text-slate-700 text-[13px] font-medium transition-all border border-slate-200 hover:border-slate-300">
                                                    <i data-lucide="edit" class="w-4 h-4"></i>
                                                    {{ __('common.edit') }}
                                                </a>
                                                <form method="POST" action="{{ route('employees.destroy', $employee) }}" class="inline" onsubmit="return confirm('{{ __('pages.confirm_delete_employee') }}');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-red-50 hover:bg-red-100 text-red-700 text-[13px] font-medium transition-all border border-red-200 hover:border-red-300">
                                                        <i data-lucide="trash-2" class="w-4 h-4"></i>
                                                        {{ __('common.delete') }}
                                                    </button>
                                                </form>
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
