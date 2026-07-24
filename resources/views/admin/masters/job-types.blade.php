@extends('layouts.app')

@section('title', __('pages.job_types_management') . ' | ' . config('app.name'))
@section('page_title', __('pages.job_types_management'))

@section('content')
    <div class="mb-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-slate-900 tracking-tight">{{ __('pages.job_types_management') }}</h1>
                <p class="text-[14px] text-slate-600 mt-1.5">{{ __('pages.job_types_management_description') }}</p>
            </div>
            <a href="{{ route('admin.masters.job-types.create') }}" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-lg bg-primary-600 hover:bg-primary-700 text-white text-[14px] font-medium shadow-md transition-all">
                <i data-lucide="plus" class="w-4 h-4"></i>
                {{ __('pages.new_registration') }}
            </a>
        </div>
    </div>

    @if(session('status'))
        <div class="mb-6 p-4 bg-emerald-50 border-l-4 border-emerald-500 rounded-lg">
            <div class="flex items-center gap-2">
                <i class="ph ph-check-circle text-lg text-emerald-600"></i>
                <span class="text-[14px] text-emerald-800 font-medium">{{ session('status') }}</span>
            </div>
        </div>
    @endif

    <div class="bg-white border border-slate-200 rounded-xl shadow-sm overflow-hidden">
        <div class="p-4 border-b border-slate-200">
            <form method="GET" action="{{ route('admin.masters.job-types') }}" class="flex items-center gap-3">
                <div class="relative">
                    <i data-lucide="building-2" class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400 pointer-events-none"></i>
                    <select name="institution_id" class="pl-9 pr-4 py-2.5 rounded-lg border border-slate-300 text-[14px] focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 bg-white">
                        <option value="">{{ __('pages.all_medical_institutions') }}</option>
                        @foreach($institutions as $inst)
                            <option value="{{ $inst->id }}" @selected(request('institution_id')==$inst->id)>{{ $inst->name }}</option>
                        @endforeach
                    </select>
                </div>
                <button class="px-5 py-2.5 rounded-lg bg-primary-600 hover:bg-primary-700 text-white text-[14px] font-medium shadow-md transition-all flex items-center gap-2" type="submit">
                    <i data-lucide="search" class="w-4 h-4"></i>
                    {{ __('common.search') }}
                </button>
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full text-[14px]">
                <thead class="bg-slate-50 border-b border-slate-200">
                    <tr>
                        <th class="px-5 py-3.5 text-left font-semibold text-slate-700">{{ __('pages.medical_institution') }}</th>
                        <th class="px-5 py-3.5 text-left font-semibold text-slate-700">{{ __('pages.job_type_code') }}</th>
                        <th class="px-5 py-3.5 text-left font-semibold text-slate-700">{{ __('pages.job_type_name') }}</th>
                        <th class="px-5 py-3.5 text-right font-semibold text-slate-700">{{ __('pages.actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($jobTypes as $jobType)
                        <tr class="hover:bg-slate-50/50 transition-colors">
                            <td class="px-5 py-4 text-slate-800">{{ $jobType->medicalInstitution?->name ?? '-' }}</td>
                            <td class="px-5 py-4 font-semibold text-slate-900">{{ $jobType->code }}</td>
                            <td class="px-5 py-4 text-slate-800">{{ $jobType->name }}</td>
                            <td class="px-5 py-4 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('admin.masters.job-types.edit', $jobType) }}"
                                       class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-lg border border-slate-300 text-slate-700 hover:bg-slate-50 hover:border-slate-400 text-[13px] font-medium transition-all">
                                        <i data-lucide="edit" class="w-4 h-4"></i>
                                        {{ __('common.edit') }}
                                    </a>
                                    <form method="POST" action="{{ route('admin.masters.job-types.destroy', $jobType) }}" class="inline" onsubmit="return confirm('{{ __('pages.confirm_delete_job_type') }}')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-lg border border-red-300 text-red-700 hover:bg-red-50 hover:border-red-400 text-[13px] font-medium transition-all">
                                            <i data-lucide="trash-2" class="w-4 h-4"></i>
                                            {{ __('common.delete') }}
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-5 py-12 text-center">
                                <div class="flex flex-col items-center gap-3">
                                    <i class="ph ph-briefcase text-4xl text-slate-300"></i>
                                    <p class="text-slate-500 text-[14px]">{{ __('pages.no_job_types_registered') }}</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($jobTypes->hasPages())
            <div class="px-5 py-4 border-t border-slate-200 flex items-center justify-between text-[13px] text-slate-600">
                <span>{{ __('pages.showing_results', ['firstItem' => $jobTypes->firstItem() ?? 0, 'lastItem' => $jobTypes->lastItem() ?? 0, 'total' => $jobTypes->total()]) }}</span>
                <div class="flex items-center gap-2">
                    {{ $jobTypes->onEachSide(1)->links('pagination::tailwind') }}
                </div>
            </div>
        @endif
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





