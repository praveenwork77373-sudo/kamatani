@extends('layouts.app')

@section('title', __('pages.employee_details') . ' | ' . config('app.name'))
@section('header_category', __('menu.employees'))
@section('page_title', __('pages.employee_details'))

@section('content')
    <div class="mb-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-slate-900 tracking-tight">{{ __('pages.employee_details') }}</h1>
                <p class="text-[14px] text-slate-600 mt-1.5">{{ __('pages.employee_details_description') }}</p>
            </div>
            <div class="flex items-center gap-3">
                <a href="{{ route('employees.index') }}" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-lg border border-slate-300 text-slate-700 hover:bg-slate-50 text-[14px] font-medium transition-all">
                    <i data-lucide="arrow-left" class="w-4 h-4"></i>
                    {{ __('common.back_to_list') }}
                </a>
                @if($customer && in_array($customer->role, ['institution_admin', 'department_admin'], true))
                    <a href="{{ route('employees.edit', $employee) }}" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-lg bg-primary-600 hover:bg-primary-700 text-white text-[14px] font-medium shadow-md transition-all">
                        <i data-lucide="edit" class="w-4 h-4"></i>
                        {{ __('common.edit') }}
                    </a>
                @endif
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Main Information Card --}}
        <div class="lg:col-span-2">
            <div class="bg-white border border-slate-200 rounded-xl shadow-sm overflow-hidden">
                {{-- Header --}}
                <div class="px-6 py-4 border-b border-slate-200 bg-gradient-to-r from-primary-50 to-blue-50">
                    <div class="flex items-center gap-3">
                        <div>
                            <h2 class="text-lg font-semibold text-slate-900">{{ $employee->name }}</h2>
                            <p class="text-[13px] text-slate-600">{{ __('pages.staff_number') }}: {{ $employee->staff_no }}</p>
                        </div>
                    </div>
                </div>

                {{-- Body --}}
                <div class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        {{-- Staff Number --}}
                        <div>
                            <label class="block text-[12px] font-semibold text-slate-500 mb-2 uppercase tracking-wide">
                                {{ __('pages.staff_no') }}
                            </label>
                            <div class="flex items-center gap-2">
                                <i data-lucide="hash" class="w-4 h-4 text-slate-400"></i>
                                <span class="text-[15px] font-semibold text-slate-900">{{ $employee->staff_no }}</span>
                            </div>
                        </div>

                        {{-- Status --}}
                        <div>
                            <label class="block text-[12px] font-semibold text-slate-500 mb-2 uppercase tracking-wide">
                                {{ __('pages.status') }}
                            </label>
                            <div>
                                @if($employee->status === 'employed')
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-[13px] font-medium bg-emerald-100 text-emerald-800 border border-emerald-200">
                                        <i data-lucide="check-circle" class="w-3.5 h-3.5"></i>
                                        {{ __('pages.employed') }}
                                    </span>
                                @elseif($employee->status === 'on_leave')
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-[13px] font-medium bg-amber-100 text-amber-800 border border-amber-200">
                                        <i data-lucide="pause-circle" class="w-3.5 h-3.5"></i>
                                        {{ __('pages.on_leave') }}
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-[13px] font-medium bg-slate-100 text-slate-800 border border-slate-200">
                                        <i data-lucide="x-circle" class="w-3.5 h-3.5"></i>
                                        {{ __('pages.retired') }}
                                    </span>
                                @endif
                            </div>
                        </div>

                        {{-- Department --}}
                        <div>
                            <label class="block text-[12px] font-semibold text-slate-500 mb-2 uppercase tracking-wide">
                                {{ __('pages.department') }}
                            </label>
                            <div class="flex items-center gap-2">
                                <i data-lucide="building-2" class="w-4 h-4 text-slate-400"></i>
                                <span class="text-[15px] text-slate-900">{{ $employee->primaryDepartment?->name ?? '-' }}</span>
                            </div>
                        </div>

                        {{-- Gender --}}
                        <div>
                            <label class="block text-[12px] font-semibold text-slate-500 mb-2 uppercase tracking-wide">
                                {{ __('pages.gender') }}
                            </label>
                            <div class="flex items-center gap-2">
                                <i data-lucide="user" class="w-4 h-4 text-slate-400"></i>
                                <span class="text-[15px] text-slate-900">
                                    @if($employee->gender === 'male')
                                        {{ __('pages.male') }}
                                    @elseif($employee->gender === 'female')
                                        {{ __('pages.female') }}
                                    @elseif($employee->gender === 'other')
                                        {{ __('pages.other') }}
                                    @else
                                        -
                                    @endif
                                </span>
                            </div>
                        </div>

                        {{-- Top Size --}}
                        <div>
                            <label class="block text-[12px] font-semibold text-slate-500 mb-2 uppercase tracking-wide">
                                {{ __('pages.top_size') }}
                            </label>
                            <div class="flex items-center gap-2">
                                <i data-lucide="shirt" class="w-4 h-4 text-slate-400"></i>
                                <span class="text-[15px] text-slate-900">{{ $employee->top_size ?? '-' }}</span>
                            </div>
                        </div>

                        {{-- Bottom Size --}}
                        <div>
                            <label class="block text-[12px] font-semibold text-slate-500 mb-2 uppercase tracking-wide">
                                {{ __('pages.bottom_size') }}
                            </label>
                            <div class="flex items-center gap-2">
                                <i data-lucide="package" class="w-4 h-4 text-slate-400"></i>
                                <span class="text-[15px] text-slate-900">{{ $employee->bottom_size ?? '-' }}</span>
                            </div>
                        </div>

                        {{-- Shoe Size --}}
                        <div>
                            <label class="block text-[12px] font-semibold text-slate-500 mb-2 uppercase tracking-wide">
                                {{ __('pages.shoe_size') }}
                            </label>
                            <div class="flex items-center gap-2">
                                <i data-lucide="footprints" class="w-4 h-4 text-slate-400"></i>
                                <span class="text-[15px] text-slate-900">{{ $employee->shoe_size ?? '-' }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Multiple Positions Card --}}
            <div class="mt-6 bg-white border border-slate-200 rounded-xl shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-200 bg-gradient-to-r from-slate-50 to-slate-100">
                    <h3 class="text-[15px] font-semibold text-slate-900 flex items-center gap-2">
                        <i data-lucide="briefcase" class="w-4 h-4 text-primary-600"></i>
                        {{ __('pages.multiple_positions') }}
                    </h3>
                </div>
                <div class="p-6">
                    @forelse($employee->positions as $pos)
                        <div class="flex items-center gap-3 py-3 border-b border-slate-100 last:border-0">
                            <div class="h-8 w-8 rounded-lg bg-primary-100 flex items-center justify-center">
                                <i data-lucide="briefcase" class="w-4 h-4 text-primary-600"></i>
                            </div>
                            <div class="flex-1">
                                <div class="flex items-center gap-2">
                                    <i data-lucide="building" class="w-4 h-4 text-slate-400"></i>
                                    <span class="text-[14px] font-medium text-slate-900">{{ $pos->department?->name ?? '-' }}</span>
                                </div>
                                <div class="flex items-center gap-2 mt-1">
                                    <i data-lucide="user-check" class="w-4 h-4 text-slate-400"></i>
                                    <span class="text-[13px] text-slate-600">{{ $pos->jobType?->name ?? '-' }}</span>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-8">
                            <div class="w-16 h-16 rounded-full bg-slate-100 flex items-center justify-center mx-auto mb-3">
                                <i data-lucide="briefcase" class="w-8 h-8 text-slate-300"></i>
                            </div>
                            <p class="text-[14px] text-slate-500">{{ __('pages.no_multiple_positions') }}</p>
                        </div>
                    @endforelse
                </div>
            </div>

            {{-- Active loans --}}
            <div class="mt-6 bg-white border border-slate-200 rounded-xl shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-200 bg-gradient-to-r from-slate-50 to-slate-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                    <div>
                        <h3 class="text-[15px] font-semibold text-slate-900 flex items-center gap-2">
                            <i data-lucide="package-check" class="w-4 h-4 text-primary-600"></i>
                            {{ __('pages.active_loans_heading') }}
                        </h3>
                        <p class="text-[12px] text-slate-500 mt-0.5">{{ __('pages.active_loans_heading_desc') }}</p>
                    </div>
                    <div class="text-[13px] text-slate-600">
                        {{ __('pages.active_loan_pieces') }}:
                        <strong class="text-slate-900">{{ $activeLoanQuantity ?? 0 }}</strong>
                        @if(($rentableLimit ?? null) !== null)
                            <span class="text-slate-400">/</span>
                            {{ __('pages.plan_limit_short') }}:
                            <strong class="text-slate-900">{{ $rentableLimit }}</strong>
                        @endif
                    </div>
                </div>
                <div class="p-2 sm:p-4">
                    <x-active-loans-table :loans="$activeLoans ?? collect()" />
                </div>
            </div>
        </div>

        {{-- Sidebar Actions --}}
        <div class="lg:col-span-1">
            <div class="bg-white border border-slate-200 rounded-xl shadow-sm overflow-hidden sticky top-6">
                <div class="px-6 py-4 border-b border-slate-200 bg-gradient-to-r from-slate-50 to-slate-100">
                    <h3 class="text-[15px] font-semibold text-slate-900 flex items-center gap-2">
                        <i data-lucide="settings" class="w-4 h-4 text-primary-600"></i>
                        {{ __('pages.actions') }}
                    </h3>
                </div>
                <div class="p-4 space-y-3">
                    @if($employee->primaryDepartment)
                        <a href="{{ route('departments.loans', $employee->primaryDepartment) }}"
                           class="w-full inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-lg border border-primary-300 text-primary-700 hover:bg-primary-50 text-[14px] font-medium transition-all">
                            <i data-lucide="building" class="w-4 h-4"></i>
                            {{ __('pages.view_department_loans') }}
                        </a>
                    @endif
                    @if($customer && in_array($customer->role, ['institution_admin', 'department_admin'], true))
                        <a href="{{ route('employees.edit', $employee) }}" class="w-full inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-lg bg-primary-600 hover:bg-primary-700 text-white text-[14px] font-medium shadow-md transition-all">
                            <i data-lucide="edit" class="w-4 h-4"></i>
                            {{ __('common.edit') }}
                        </a>
                        <form method="POST" action="{{ route('employees.destroy', $employee) }}"
                              data-ui-confirm="{{ __('pages.confirm_delete_employee') }}"
                              data-ui-confirm-title="{{ __('pages.confirm_action_title_delete') }}"
                              data-ui-confirm-tone="danger"
                              data-ui-confirm-ok="{{ __('common.delete') }}">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="w-full inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-lg bg-red-600 hover:bg-red-700 text-white text-[14px] font-medium shadow-md transition-all">
                                <i data-lucide="trash-2" class="w-4 h-4"></i>
                                {{ __('common.delete') }}
                            </button>
                        </form>
                    @endif
                    <a href="{{ route('employees.index') }}" class="w-full inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-lg border border-slate-300 text-slate-700 hover:bg-slate-50 text-[14px] font-medium transition-all">
                        <i data-lucide="arrow-left" class="w-4 h-4"></i>
                        {{ __('common.back_to_list') }}
                    </a>
                </div>
            </div>
        </div>
    </div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
        setTimeout(() => { if (typeof lucide !== 'undefined') lucide.createIcons(); }, 100);
        setTimeout(() => { if (typeof lucide !== 'undefined') lucide.createIcons(); }, 500);
    }
});
</script>
@endpush
@endsection
