@extends('layouts.app')

@section('title', __('pages.employee_list') . ' | ' . __('auth.system_title'))
@section('page_title', __('pages.employee_list'))

@section('content')
    <div class="mb-6">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
            <div>
                <h1 class="text-2xl font-bold text-slate-900 tracking-tight">{{ __('pages.employee_list') }}</h1>
                <p class="text-[14px] text-slate-600 mt-1.5">
                    {{ __('pages.employee_list_description') }}
                </p>
            </div>
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

    <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
        {{-- Filter Sidebar --}}
        <div class="lg:col-span-1">
            <div class="bg-white border border-slate-200 rounded-xl shadow-sm overflow-hidden sticky top-6">
                <div class="p-4 border-b border-slate-200 bg-gradient-to-r from-primary-50 to-blue-50">
                    <h2 class="text-[15px] font-semibold text-slate-900 flex items-center gap-2">
                        <i data-lucide="filter" class="w-4 h-4 text-primary-600"></i>
                        {{ __('common.filter') }}
                    </h2>
                </div>
                
                <form method="GET" action="{{ route('employees.index') }}" id="employee-filter-form" class="p-4 space-y-4">
                    @if($customer && $customer->role === 'institution_admin')
                        {{-- Department Filter --}}
                        <div>
                            <label class="block text-[13px] font-semibold text-slate-700 mb-2">
                                {{ __('pages.department') }}
                            </label>
                            <div class="relative">
                                <i data-lucide="briefcase" class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400"></i>
                                <select name="department_id" id="department-filter" 
                                        class="w-full pl-9 pr-3 py-2.5 rounded-lg border border-slate-300 text-[14px] focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 bg-white">
                                    <option value="">{{ __('pages.all_departments') }}</option>
                                    @foreach($departments as $dept)
                                        <option value="{{ $dept->id }}" @selected($selectedDepartmentId == $dept->id)>{{ $dept->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    @endif
                    
                    {{-- Status Filter --}}
                    <div>
                        <label class="block text-[13px] font-semibold text-slate-700 mb-2">
                            {{ __('pages.status') }}
                        </label>
                        <div class="relative">
                            <i data-lucide="check-circle" class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400"></i>
                            <select name="status" 
                                    class="w-full pl-9 pr-3 py-2.5 rounded-lg border border-slate-300 text-[14px] focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 bg-white">
                                <option value="">{{ __('pages.all_statuses') }}</option>
                                <option value="employed" @selected(request('status') == 'employed')>{{ __('pages.employed') }}</option>
                                <option value="on_leave" @selected(request('status') == 'on_leave')>{{ __('pages.on_leave') }}</option>
                                <option value="retired" @selected(request('status') == 'retired')>{{ __('pages.retired') }}</option>
                            </select>
                        </div>
                    </div>
                    
                    {{-- Search --}}
                    <div>
                        <label class="block text-[13px] font-semibold text-slate-700 mb-2">
                            {{ __('common.search') }}
                        </label>
                        <div class="relative">
                            <i data-lucide="search" class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400"></i>
                            <input type="text" name="q" id="search-input" value="{{ $searchQuery }}" 
                                   placeholder="{{ __('pages.search_employee_placeholder') }}"
                                   class="w-full pl-9 pr-3 py-2.5 rounded-lg border border-slate-300 text-[14px] focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20">
                        </div>
                    </div>
                    
                    <div class="flex gap-2 pt-2">
                        <button type="submit" class="flex-1 px-4 py-2.5 rounded-lg bg-primary-600 hover:bg-primary-700 text-white text-[14px] font-medium shadow-md transition-all flex items-center justify-center gap-2">
                            <i data-lucide="search" class="w-4 h-4"></i>
                            {{ __('common.search') }}
                        </button>
                        <a href="{{ route('employees.index') }}" class="px-4 py-2.5 rounded-lg border border-slate-300 hover:bg-slate-50 text-slate-700 text-[14px] font-medium transition-all flex items-center justify-center">
                            <i data-lucide="x" class="w-4 h-4"></i>
                        </a>
                    </div>
                </form>
            </div>
        </div>
        
        {{-- Employee List --}}
        <div class="lg:col-span-3">
            <div class="bg-white border border-slate-200 rounded-xl shadow-sm overflow-hidden">
                <div class="p-4 border-b border-slate-200 bg-gradient-to-r from-slate-50 to-slate-100">
                    <div class="flex items-center justify-between">
                        <h2 class="text-[15px] font-semibold text-slate-900 flex items-center gap-2">
                            <i data-lucide="users" class="w-4 h-4 text-primary-600"></i>
                            {{ __('pages.employee_list_title') }}
                        </h2>
                        <span class="text-[13px] text-slate-600">
                            {{ __('pages.all_items_showing') }} {{ $employees->total() }}{{ __('pages.items_showing') }} {{ $employees->firstItem() ?? 0 }}-{{ $employees->lastItem() ?? 0 }}{{ __('pages.items_displayed') }}
                        </span>
                    </div>
                </div>
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
                
                @if($employees->hasPages())
                    <div class="px-5 py-4 border-t border-slate-200 bg-slate-50">
                        <div class="flex items-center justify-between">
                            <span class="text-[13px] text-slate-600">
                                {{ __('pages.all_items_showing') }} {{ $employees->total() }}{{ __('pages.items_showing') }} {{ $employees->firstItem() ?? 0 }}-{{ $employees->lastItem() ?? 0 }}{{ __('pages.items_displayed') }}
                            </span>
                            <div class="flex items-center gap-2">
                                {{ $employees->onEachSide(1)->links('pagination::tailwind') }}
                            </div>
                        </div>
                    </div>
                @endif
            </div>
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
