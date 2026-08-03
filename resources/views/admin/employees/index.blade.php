@extends('layouts.app')

@section('title', __('menu.all_employees') . ' | ' . config('app.name'))
@section('page_title', __('menu.all_employees'))

@section('content')
    <div class="mb-6">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <p class="text-[14px] text-slate-600">{{ __('pages.all_employees_description') }}</p>
            <div class="flex items-center gap-3 flex-wrap">
                <form method="POST" action="{{ route('admin.employees.export_csv') }}" class="inline">
                    @csrf
                    <input type="hidden" name="medical_institution_id" value="{{ $selectedInstitutionId }}">
                    <input type="hidden" name="department_id" value="{{ $selectedDepartmentId }}">
                    <input type="hidden" name="status" value="{{ $selectedStatus }}">
                    <input type="hidden" name="q" value="{{ $searchQuery }}">
                    <button type="submit"
                            class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-white border border-slate-300 text-slate-700 hover:bg-slate-50 text-[14px] font-medium shadow-sm transition-all {{ $selectedInstitutionId ? '' : 'opacity-50 cursor-not-allowed' }}"
                            @if(!$selectedInstitutionId) disabled title="医療機関を選択してから出力してください" @endif>
                        <i data-lucide="download" class="w-4 h-4"></i>
                        CSV出力
                    </button>
                </form>
                <form method="POST" action="{{ route('admin.employees.import_csv') }}" enctype="multipart/form-data" class="inline" id="admin-csv-import-form">
                    @csrf
                    <input type="hidden" name="medical_institution_id" value="{{ $selectedInstitutionId }}">
                    <label class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-white border border-slate-300 text-slate-700 hover:bg-slate-50 text-[14px] font-medium shadow-sm transition-all {{ $selectedInstitutionId ? 'cursor-pointer' : 'opacity-50 cursor-not-allowed' }}">
                        <i data-lucide="upload" class="w-4 h-4"></i>
                        CSV取込
                        <input type="file" name="csv_file" accept=".csv,.txt" class="hidden"
                               @if(!$selectedInstitutionId) disabled @endif
                               onchange="if(this.files.length){ document.getElementById('admin-csv-import-form').submit(); }">
                    </label>
                </form>
            </div>
        </div>
        @if(!$selectedInstitutionId)
            <p class="text-[13px] text-amber-700 mt-2">CSV出力・取込にはフィルタで医療機関を選択してください。</p>
        @endif
    </div>

    @if(session('status'))
        <div class="mb-6 p-4 bg-emerald-50 border-l-4 border-emerald-500 rounded-lg">
            <div class="flex items-center gap-2">
                <i data-lucide="check-circle" class="w-5 h-5 text-emerald-600"></i>
                <span class="text-[14px] text-emerald-800 font-medium">{{ session('status') }}</span>
            </div>
        </div>
    @endif

    @if(session('error'))
        <div class="mb-6 p-4 bg-red-50 border-l-4 border-red-500 rounded-lg">
            <div class="flex items-center gap-2">
                <i data-lucide="alert-circle" class="w-5 h-5 text-red-600"></i>
                <span class="text-[14px] text-red-800 font-medium">{{ session('error') }}</span>
            </div>
        </div>
    @endif

    @if($errors->any())
        <div class="mb-6 p-4 bg-red-50 border-l-4 border-red-500 rounded-lg">
            <ul class="text-[14px] text-red-800 list-disc pl-5 space-y-1">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
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
            <form method="GET" action="{{ route('admin.employees.index') }}" class="p-4">
                <x-per-page-field />
                <div class="flex flex-col lg:flex-row lg:items-end gap-3">
                    <div class="flex-1 min-w-0">
                        <label class="block text-[12px] font-semibold text-slate-600 mb-1.5">{{ __('pages.medical_institution') }}</label>
                        <select name="medical_institution_id" id="institution-filter" class="ui-select">
                            <option value="">{{ __('pages.all_medical_institutions') }}</option>
                            @foreach($institutions as $inst)
                                <option value="{{ $inst->id }}" @selected($selectedInstitutionId == $inst->id)>{{ $inst->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="flex-1 min-w-0">
                        <label class="block text-[12px] font-semibold text-slate-600 mb-1.5">{{ __('pages.department') }}</label>
                        <select name="department_id" id="department-filter" class="ui-select"
                                @if(!$selectedInstitutionId) disabled @endif>
                            <option value="">{{ __('pages.all_departments') }}</option>
                            @foreach($departments as $dept)
                                <option value="{{ $dept->id }}" @selected($selectedDepartmentId == $dept->id)>{{ $dept->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="flex-1 min-w-0">
                        <label class="block text-[12px] font-semibold text-slate-600 mb-1.5">{{ __('pages.status') }}</label>
                        <select name="status" class="ui-select">
                            <option value="">{{ __('pages.all_statuses') }}</option>
                            <option value="employed" @selected($selectedStatus == 'employed')>{{ __('pages.employed') }}</option>
                            <option value="on_leave" @selected($selectedStatus == 'on_leave')>{{ __('pages.on_leave') }}</option>
                            <option value="retired" @selected($selectedStatus == 'retired')>{{ __('pages.retired') }}</option>
                        </select>
                    </div>
                    <div class="flex-[1.5] min-w-0">
                        <label class="block text-[12px] font-semibold text-slate-600 mb-1.5">{{ __('common.search') }}</label>
                        <input type="text" name="q" value="{{ $searchQuery }}"
                               placeholder="{{ __('pages.search_employee_placeholder') }}"
                               class="ui-input">
                    </div>
                    <div class="flex gap-2 shrink-0">
                        <button type="submit" class="ui-btn ui-btn-primary">
                            <i data-lucide="search" class="w-4 h-4"></i>
                            {{ __('common.search') }}
                        </button>
                        <a href="{{ route('admin.employees.index') }}" class="ui-btn ui-btn-secondary" title="{{ __('common.reset') }}">
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
                            <th class="px-5 py-3.5 text-left font-semibold text-slate-700">{{ __('pages.medical_institution') }}</th>
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
                                <td class="px-5 py-4 text-slate-900">
                                    <div class="flex items-center gap-2">
                                        <i data-lucide="building" class="w-4 h-4 text-slate-400"></i>
                                        <span>{{ $employee->primaryDepartment->medicalInstitution->name ?? '-' }}</span>
                                    </div>
                                </td>
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
                                    <a href="{{ route('admin.employees.show', $employee) }}"
                                       class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-primary-50 hover:bg-primary-100 text-primary-700 text-[13px] font-medium transition-all border border-primary-200 hover:border-primary-300">
                                        <i data-lucide="eye" class="w-4 h-4"></i>
                                        {{ __('common.view') }}
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-5 py-12 text-center text-slate-500">
                                    <div class="flex flex-col items-center gap-3">
                                        <div class="w-16 h-16 rounded-full bg-slate-100 flex items-center justify-center">
                                            <i data-lucide="users" class="w-8 h-8 text-slate-300"></i>
                                        </div>
                                        <p class="text-[14px] font-medium">{{ __('pages.no_employees_found') }}</p>
                                        <p class="text-[12px] text-slate-400">{{ __('pages.change_search_conditions') }}</p>
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
        // Auto-update departments when institution changes
        document.getElementById('institution-filter')?.addEventListener('change', function() {
            const institutionId = this.value;
            const departmentSelect = document.getElementById('department-filter');

            if (!institutionId) {
                departmentSelect.innerHTML = '<option value="">{{ __('pages.all_departments') }}</option>';
                departmentSelect.disabled = true;
                return;
            }

            departmentSelect.disabled = true;
            departmentSelect.innerHTML = '<option value="">{{ __('pages.loading') }}</option>';

            // Fetch departments for selected institution
            fetch(`{{ route('api.departments') }}?medical_institution_id=${institutionId}`)
                .then(response => response.json())
                .then(data => {
                    departmentSelect.innerHTML = '<option value="">{{ __('pages.all_departments') }}</option>';
                    if (data && Array.isArray(data)) {
                        data.forEach(dept => {
                            const option = document.createElement('option');
                            option.value = dept.id;
                            option.textContent = dept.name;
                            departmentSelect.appendChild(option);
                        });
                    }
                    departmentSelect.disabled = false;
                })
                .catch(error => {
                    console.error('Error loading departments:', error);
                    departmentSelect.innerHTML = '<option value="">{{ __('pages.all_departments') }}</option>';
                    departmentSelect.disabled = false;
                });
        });

        // Initialize Lucide icons
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
    </script>
@endsection
