@extends('layouts.app')

@section('title', __('pages.department_admins_management') . ' | ' . config('app.name'))
@section('page_title', __('pages.department_admins_management'))

@section('content')
    <div class="mb-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-slate-900 tracking-tight">{{ __('pages.department_admins_management') }}</h1>
                <p class="text-[14px] text-slate-600 mt-1.5">{{ __('pages.department_admins_management_description') }}</p>
            </div>
            <a href="{{ route('customer.masters.department-admins.create') }}" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-lg bg-primary-600 hover:bg-primary-700 text-white text-[14px] font-medium shadow-md transition-all">
                <i data-lucide="plus" class="w-4 h-4"></i>
                {{ __('pages.new_registration') }}
            </a>
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
                
                <form method="GET" action="{{ route('customer.masters.department-admins') }}" class="p-4 space-y-4">
                    {{-- Department Filter --}}
                    <div>
                        <label class="block text-[13px] font-semibold text-slate-700 mb-2 flex items-center gap-1.5">
                            <i data-lucide="briefcase" class="w-3.5 h-3.5 text-slate-500"></i>
                            {{ __('pages.department') }}
                        </label>
                        <div class="relative">
                            <i data-lucide="briefcase" class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400 pointer-events-none"></i>
                            <select name="department_id" id="department-filter" 
                                    class="w-full pl-9 pr-3 py-2.5 rounded-lg border border-slate-300 text-[14px] focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 bg-white transition-all">
                                <option value="">{{ __('pages.all_departments') }}</option>
                                @foreach($departments as $dept)
                                    <option value="{{ $dept->id }}" @selected($selectedDepartmentId == $dept->id)>{{ $dept->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    
                    {{-- Search --}}
                    <div>
                        <label class="block text-[13px] font-semibold text-slate-700 mb-2 flex items-center gap-1.5">
                            <i data-lucide="search" class="w-3.5 h-3.5 text-slate-500"></i>
                            {{ __('common.search') }}
                        </label>
                        <div class="relative">
                            <i data-lucide="search" class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400 pointer-events-none"></i>
                            <input type="text" name="q" id="search-input" value="{{ $searchQuery }}" 
                                   placeholder="{{ __('pages.search_department_admin_placeholder') }}"
                                   class="w-full pl-9 pr-3 py-2.5 rounded-lg border border-slate-300 text-[14px] focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 bg-white placeholder-slate-400 transition-all">
                        </div>
                    </div>
                    
                    {{-- Action Buttons --}}
                    <div class="flex gap-2 pt-2">
                        <button type="submit" class="flex-1 inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-lg bg-primary-600 hover:bg-primary-700 text-white text-[13px] font-medium shadow-md transition-all">
                            <i data-lucide="search" class="w-4 h-4"></i>
                            {{ __('common.search') }}
                        </button>
                        <a href="{{ route('customer.masters.department-admins') }}" 
                           class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-lg border border-slate-300 text-slate-700 hover:bg-slate-50 text-[13px] font-medium transition-all">
                            <i data-lucide="x" class="w-4 h-4"></i>
                            {{ __('common.reset') }}
                        </a>
                    </div>
                </form>
            </div>
        </div>
        
        {{-- Main Content --}}
        <div class="lg:col-span-3">

            <div class="bg-white border border-slate-200 rounded-xl shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full text-[14px]">
                        <thead class="bg-slate-50 border-b border-slate-200">
                            <tr>
                                <th class="px-5 py-3.5 text-left font-semibold text-slate-700">{{ __('pages.name') }}</th>
                                <th class="px-5 py-3.5 text-left font-semibold text-slate-700">{{ __('pages.email') }}</th>
                                <th class="px-5 py-3.5 text-left font-semibold text-slate-700">{{ __('pages.department') }}</th>
                                <th class="px-5 py-3.5 text-left font-semibold text-slate-700">{{ __('pages.created_at') }}</th>
                                <th class="px-5 py-3.5 text-right font-semibold text-slate-700">{{ __('pages.actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse($departmentAdmins as $admin)
                                <tr class="hover:bg-slate-50/50 transition-colors">
                                    <td class="px-5 py-4 font-semibold text-slate-900">{{ $admin->user->name }}</td>
                                    <td class="px-5 py-4 text-slate-800">{{ $admin->user->email }}</td>
                                    <td class="px-5 py-4 text-slate-600">{{ $admin->department?->name ?? '-' }}</td>
                                    <td class="px-5 py-4 text-slate-600">{{ $admin->created_at->format('Y/m/d') }}</td>
                                    <td class="px-5 py-4 text-right">
                                        <div class="flex items-center justify-end gap-2">
                                            <a href="{{ route('customer.masters.department-admins.edit', $admin) }}"
                                               class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-lg border border-slate-300 text-slate-700 hover:bg-slate-50 hover:border-slate-400 text-[13px] font-medium transition-all">
                                                <i data-lucide="pencil" class="w-4 h-4"></i>
                                                {{ __('common.edit') }}
                                            </a>
                                            <form method="POST" action="{{ route('customer.masters.department-admins.destroy', $admin) }}" class="inline" onsubmit="return confirm('{{ __('pages.confirm_delete_department_admin') }}')">
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
                                    <td colspan="5" class="px-5 py-12 text-center">
                                        <div class="flex flex-col items-center gap-3">
                                            <i data-lucide="user-x" class="w-12 h-12 text-slate-300"></i>
                                            <p class="text-slate-500 text-[14px]">{{ __('pages.no_department_admins_registered') }}</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($departmentAdmins->hasPages())
                    <div class="px-5 py-4 border-t border-slate-200 flex items-center justify-between text-[13px] text-slate-600">
                        <span>{{ __('pages.showing_results', ['firstItem' => $departmentAdmins->firstItem() ?? 0, 'lastItem' => $departmentAdmins->lastItem() ?? 0, 'total' => $departmentAdmins->total()]) }}</span>
                        <div class="flex items-center gap-2">
                            {{ $departmentAdmins->onEachSide(1)->links('pagination::tailwind') }}
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize Lucide icons
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
        setTimeout(() => {
            if (typeof lucide !== 'undefined') lucide.createIcons();
        }, 100);
        setTimeout(() => {
            if (typeof lucide !== 'undefined') lucide.createIcons();
        }, 500);
    }
    
    const form = document.querySelector('form');
    const departmentFilter = document.getElementById('department-filter');
    const searchInput = document.getElementById('search-input');
    let searchTimeout;
    
    // Auto-submit on department filter change
    if (departmentFilter) {
        departmentFilter.addEventListener('change', function() {
            form.submit();
        });
    }
    
    // Auto-submit on search input (with debounce)
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                form.submit();
            }, 500);
        });
        
        searchInput.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' || e.keyCode === 13) {
                e.preventDefault();
                clearTimeout(searchTimeout);
                form.submit();
            }
        });
    }
});
</script>
@endpush
@endsection



