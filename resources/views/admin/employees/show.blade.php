@extends('layouts.app')

@section('title', __('pages.employee_details') . ' | ' . config('app.name'))
@section('page_title', __('pages.employee_details'))

@section('content')
    <div class="mb-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-slate-900 tracking-tight">{{ __('pages.employee_details') }}</h1>
                <p class="text-[14px] text-slate-600 mt-1.5">{{ __('pages.employee_details_description') }}</p>
            </div>
            <a href="{{ route('admin.employees.index') }}" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-lg border border-slate-300 text-slate-700 hover:bg-slate-50 text-[14px] font-medium transition-all">
                <i data-lucide="arrow-left" class="w-4 h-4"></i>
                {{ __('common.back_to_list') }}
            </a>
        </div>
    </div>

    <div class="max-w-4xl mx-auto">
        {{-- Employee Header Card --}}
        <div class="bg-white border border-slate-200 rounded-xl shadow-sm overflow-hidden mb-6">
            <div class="px-6 py-5 border-b border-slate-200 bg-gradient-to-r from-primary-50 via-blue-50 to-indigo-50">
                <div class="flex items-center gap-4">
                    <div class="h-16 w-16 rounded-full bg-gradient-to-br from-primary-400 to-primary-600 flex items-center justify-center text-white text-2xl font-bold shadow-lg">
                        {{ mb_substr($employee->name, 0, 1) }}
                    </div>
                    <div class="flex-1">
                        <h2 class="text-xl font-bold text-slate-900 mb-1">{{ $employee->name }}</h2>
                        <div class="flex items-center gap-3 flex-wrap">
                            <div class="flex items-center gap-1.5">
                                <i data-lucide="hash" class="w-4 h-4 text-slate-500"></i>
                                <span class="text-[13px] text-slate-600 font-mono">{{ $employee->staff_no }}</span>
                            </div>
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
                        </div>
                    </div>
                </div>
            </div>

            {{-- Basic Information --}}
            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    {{-- Medical Institution --}}
                    <div>
                        <label class="block text-[12px] font-semibold text-slate-500 mb-2 uppercase tracking-wide flex items-center gap-1.5">
                            <i data-lucide="building-2" class="w-3.5 h-3.5"></i>
                            {{ __('pages.medical_institution') }}
                        </label>
                        <p class="text-[15px] font-medium text-slate-900">{{ $employee->primaryDepartment->medicalInstitution->name ?? '-' }}</p>
                    </div>

                    {{-- Department --}}
                    <div>
                        <label class="block text-[12px] font-semibold text-slate-500 mb-2 uppercase tracking-wide flex items-center gap-1.5">
                            <i data-lucide="briefcase" class="w-3.5 h-3.5"></i>
                            {{ __('pages.department') }}
                        </label>
                        <p class="text-[15px] font-medium text-slate-900">{{ $employee->primaryDepartment->name ?? '-' }}</p>
                    </div>

                    {{-- Gender --}}
                    <div>
                        <label class="block text-[12px] font-semibold text-slate-500 mb-2 uppercase tracking-wide flex items-center gap-1.5">
                            <i data-lucide="user-circle" class="w-3.5 h-3.5"></i>
                            {{ __('pages.gender') }}
                        </label>
                        <p class="text-[15px] text-slate-900">
                            @if($employee->gender === 'male')
                                {{ __('pages.male') }}
                            @elseif($employee->gender === 'female')
                                {{ __('pages.female') }}
                            @elseif($employee->gender === 'other')
                                {{ __('pages.other') }}
                            @else
                                -
                            @endif
                        </p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Uniform Sizes Card --}}
        <div class="bg-white border border-slate-200 rounded-xl shadow-sm overflow-hidden mb-6">
            <div class="px-6 py-4 border-b border-slate-200 bg-gradient-to-r from-slate-50 to-slate-100">
                <h3 class="text-[15px] font-semibold text-slate-900 flex items-center gap-2">
                    <i data-lucide="ruler" class="w-4 h-4 text-primary-600"></i>
                    {{ __('pages.uniform_sizes') }}
                </h3>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    {{-- Top Size --}}
                    <div>
                        <label class="block text-[12px] font-semibold text-slate-500 mb-2 uppercase tracking-wide flex items-center gap-1.5">
                            <i data-lucide="shirt" class="w-3.5 h-3.5"></i>
                            {{ __('pages.top_size') }}
                        </label>
                        <p class="text-[16px] font-semibold text-slate-900">{{ $employee->top_size ?? '-' }}</p>
                    </div>

                    {{-- Bottom Size --}}
                    <div>
                        <label class="block text-[12px] font-semibold text-slate-500 mb-2 uppercase tracking-wide flex items-center gap-1.5">
                            <i data-lucide="package" class="w-3.5 h-3.5"></i>
                            {{ __('pages.bottom_size') }}
                        </label>
                        <p class="text-[16px] font-semibold text-slate-900">{{ $employee->bottom_size ?? '-' }}</p>
                    </div>

                    {{-- Shoe Size --}}
                    <div>
                        <label class="block text-[12px] font-semibold text-slate-500 mb-2 uppercase tracking-wide flex items-center gap-1.5">
                            <i data-lucide="footprints" class="w-3.5 h-3.5"></i>
                            {{ __('pages.shoe_size') }}
                        </label>
                        <p class="text-[16px] font-semibold text-slate-900">{{ $employee->shoe_size ?? '-' }}</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Multiple Positions Card --}}
        @if($employee->positions->count() > 0)
            <div class="bg-white border border-slate-200 rounded-xl shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-200 bg-gradient-to-r from-slate-50 to-slate-100">
                    <h3 class="text-[15px] font-semibold text-slate-900 flex items-center gap-2">
                        <i data-lucide="layers" class="w-4 h-4 text-primary-600"></i>
                        {{ __('pages.multiple_positions') }}
                    </h3>
                </div>
                <div class="p-6">
                    <div class="space-y-3">
                        @foreach($employee->positions as $pos)
                            <div class="flex items-start gap-4 p-4 bg-gradient-to-r from-slate-50 to-white rounded-lg border border-slate-200 hover:shadow-md transition-all">
                                <div class="h-12 w-12 rounded-xl bg-gradient-to-br from-primary-100 to-primary-200 flex items-center justify-center flex-shrink-0">
                                    <i data-lucide="briefcase" class="w-6 h-6 text-primary-600"></i>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-2 mb-2">
                                        <i data-lucide="building" class="w-4 h-4 text-slate-400 flex-shrink-0"></i>
                                        <span class="text-[15px] font-semibold text-slate-900">{{ $pos->department?->name ?? '-' }}</span>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <i data-lucide="user-check" class="w-4 h-4 text-slate-400 flex-shrink-0"></i>
                                        <span class="text-[14px] text-slate-600">{{ $pos->jobType?->name ?? '-' }}</span>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @endif

        {{-- Active loans --}}
        <div class="bg-white border border-slate-200 rounded-xl shadow-sm overflow-hidden mb-6">
            <div class="px-6 py-4 border-b border-slate-200 bg-gradient-to-r from-slate-50 to-slate-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                <div>
                    <h3 class="text-[15px] font-semibold text-slate-900 flex items-center gap-2">
                        <i data-lucide="package-check" class="w-4 h-4 text-primary-600"></i>
                        {{ __('pages.active_loans_heading') }}
                    </h3>
                    <p class="text-[12px] text-slate-500 mt-0.5">{{ __('pages.active_loans_heading_desc') }}</p>
                </div>
                <div class="flex flex-wrap items-center gap-3 text-[13px] text-slate-600">
                    <span>
                        {{ __('pages.active_loan_pieces') }}:
                        <strong class="text-slate-900">{{ $activeLoanQuantity ?? 0 }}</strong>
                        @if(($rentableLimit ?? null) !== null)
                            <span class="text-slate-400">/</span>
                            {{ __('pages.plan_limit_short') }}:
                            <strong class="text-slate-900">{{ $rentableLimit }}</strong>
                        @endif
                    </span>
                    @if($employee->primaryDepartment)
                        <a href="{{ route('admin.departments.loans', $employee->primaryDepartment) }}"
                           class="inline-flex items-center gap-1.5 text-primary-700 hover:underline font-medium">
                            <i data-lucide="building" class="w-3.5 h-3.5"></i>
                            {{ __('pages.view_department_loans') }}
                        </a>
                    @endif
                </div>
            </div>
            <div class="p-2 sm:p-4">
                <x-active-loans-table :loans="$activeLoans ?? collect()" />
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
