@extends('layouts.app')

@section('title', __('menu.admin_users_management') . ' | ' . config('app.name'))
@section('page_title', __('menu.admin_users_management'))

@section('content')
    <div class="mb-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-slate-900 tracking-tight">{{ __('menu.admin_users_management') }}</h1>
                <p class="text-[14px] text-slate-600 mt-1.5">{{ __('pages.admin_users_management_description') }}</p>
            </div>
            <a href="{{ route('admin.admin-users.create') }}" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-lg bg-primary-600 hover:bg-primary-700 text-white text-[14px] font-medium shadow-md transition-all">
                <i data-lucide="user-plus" class="w-4 h-4"></i>
                {{ __('pages.new_admin_user') }}
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

    @if(session('error'))
        <div class="mb-6 p-4 bg-red-50 border-l-4 border-red-500 rounded-lg">
            <div class="flex items-center gap-2">
                <i data-lucide="alert-circle" class="w-5 h-5 text-red-600"></i>
                <span class="text-[14px] text-red-800 font-medium">{{ session('error') }}</span>
            </div>
        </div>
    @endif

    {{-- Statistics Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
        {{-- Total Admins --}}
        <div class="relative overflow-hidden rounded-xl bg-gradient-to-br from-blue-50 via-blue-100 to-indigo-100 border border-blue-200/50 p-4 shadow-sm hover:shadow-md transition-all duration-300">
            <div class="relative z-10">
                <div class="flex items-center justify-between mb-3">
                    <div class="h-10 w-10 rounded-lg bg-blue-500/10 flex items-center justify-center">
                        <i data-lucide="users" class="w-5 h-5 text-blue-600"></i>
                    </div>
                    <span class="px-2 py-0.5 rounded-full bg-blue-500/10 text-blue-700 text-[10px] font-semibold">{{ __('pages.total') }}</span>
                </div>
                <p class="text-3xl font-bold text-slate-900 mb-1">{{ number_format($stats['total_admins'] ?? 0) }}</p>
                <p class="text-slate-600 text-[13px] font-medium">{{ __('pages.total_admins') }}</p>
            </div>
        </div>

        {{-- Active Admins --}}
        <div class="relative overflow-hidden rounded-xl bg-gradient-to-br from-emerald-50 via-teal-50 to-cyan-50 border border-emerald-200/50 p-4 shadow-sm hover:shadow-md transition-all duration-300">
            <div class="relative z-10">
                <div class="flex items-center justify-between mb-3">
                    <div class="h-10 w-10 rounded-lg bg-emerald-500/10 flex items-center justify-center">
                        <i data-lucide="user-check" class="w-5 h-5 text-emerald-600"></i>
                    </div>
                    <span class="px-2 py-0.5 rounded-full bg-emerald-500/10 text-emerald-700 text-[10px] font-semibold">{{ __('pages.active') }}</span>
                </div>
                <p class="text-3xl font-bold text-slate-900 mb-1">{{ number_format($stats['active_admins'] ?? 0) }}</p>
                <p class="text-slate-600 text-[13px] font-medium">{{ __('pages.active') }}</p>
            </div>
        </div>

        {{-- Blocked Admins --}}
        <div class="relative overflow-hidden rounded-xl bg-gradient-to-br from-amber-50 via-orange-50 to-red-50 border border-amber-200/50 p-4 shadow-sm hover:shadow-md transition-all duration-300">
            <div class="relative z-10">
                <div class="flex items-center justify-between mb-3">
                    <div class="h-10 w-10 rounded-lg bg-amber-500/10 flex items-center justify-center">
                        <i data-lucide="user-x" class="w-5 h-5 text-amber-600"></i>
                    </div>
                    <span class="px-2 py-0.5 rounded-full bg-amber-500/10 text-amber-700 text-[10px] font-semibold">{{ __('pages.blocked') }}</span>
                </div>
                <p class="text-3xl font-bold text-slate-900 mb-1">{{ number_format($stats['blocked_admins'] ?? 0) }}</p>
                <p class="text-slate-600 text-[13px] font-medium">{{ __('pages.blocked_admins') }}</p>
            </div>
        </div>

        {{-- System Admins --}}
        <div class="relative overflow-hidden rounded-xl bg-gradient-to-br from-purple-50 via-pink-50 to-rose-50 border border-purple-200/50 p-4 shadow-sm hover:shadow-md transition-all duration-300">
            <div class="relative z-10">
                <div class="flex items-center justify-between mb-3">
                    <div class="h-10 w-10 rounded-lg bg-purple-500/10 flex items-center justify-center">
                        <i data-lucide="shield" class="w-5 h-5 text-purple-600"></i>
                    </div>
                    <span class="px-2 py-0.5 rounded-full bg-purple-500/10 text-purple-700 text-[10px] font-semibold">{{ __('roles.system_admin') }}</span>
                </div>
                <p class="text-3xl font-bold text-slate-900 mb-1">{{ number_format($stats['system_admins'] ?? 0) }}</p>
                <p class="text-slate-600 text-[13px] font-medium">{{ __('pages.system_admins') }}</p>
            </div>
        </div>
    </div>

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
                <div class="p-4">
                
                <form method="GET" action="{{ route('admin.admin-users.index') }}" class="space-y-4">
                    {{-- Role Filter --}}
                    <div>
                        <label class="block text-[13px] font-semibold text-slate-700 mb-2">
                            {{ __('pages.role') }}
                        </label>
                        <div class="relative">
                            <i data-lucide="shield" class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400"></i>
                            <select name="role" 
                                    class="w-full pl-9 pr-3 py-2.5 rounded-lg border border-slate-300 text-[14px] focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 bg-white">
                                <option value="">{{ __('pages.all_roles') }}</option>
                                <option value="system_admin" @selected($selectedRole == 'system_admin')>{{ __('roles.system_admin') }}</option>
                                <option value="operation" @selected($selectedRole == 'operation')>{{ __('roles.operation') }}</option>
                                <option value="sales" @selected($selectedRole == 'sales')>{{ __('roles.sales') }}</option>
                                <option value="accounting" @selected($selectedRole == 'accounting')>{{ __('roles.accounting') }}</option>
                            </select>
                        </div>
                    </div>
                    
                    {{-- Status Filter --}}
                    <div>
                        <label class="block text-[13px] font-semibold text-slate-700 mb-2">
                            {{ __('pages.status') }}
                        </label>
                        <div class="relative">
                            <i data-lucide="user-check" class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400"></i>
                            <select name="status" 
                                    class="w-full pl-9 pr-3 py-2.5 rounded-lg border border-slate-300 text-[14px] focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 bg-white">
                                <option value="">{{ __('pages.all_statuses') }}</option>
                                <option value="active" @selected($selectedStatus == 'active')>{{ __('pages.active') }}</option>
                                <option value="blocked" @selected($selectedStatus == 'blocked')>{{ __('pages.blocked') }}</option>
                                <option value="deleted" @selected($selectedStatus == 'deleted')>{{ __('pages.deleted') }}</option>
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
                            <input type="text" name="search" value="{{ $searchQuery }}" 
                                   placeholder="{{ __('pages.search_admin_user_placeholder') }}"
                                   class="w-full pl-9 pr-3 py-2.5 rounded-lg border border-slate-300 text-[14px] focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20">
                        </div>
                    </div>
                    
                    <div class="flex gap-2 pt-2">
                        <button type="submit" class="flex-1 px-4 py-2.5 rounded-lg bg-primary-600 hover:bg-primary-700 text-white text-[14px] font-medium shadow-md transition-all flex items-center justify-center gap-2">
                            <i data-lucide="search" class="w-4 h-4"></i>
                            {{ __('common.search') }}
                        </button>
                        <a href="{{ route('admin.admin-users.index') }}" class="px-4 py-2.5 rounded-lg border border-slate-300 hover:bg-slate-50 text-slate-700 text-[14px] font-medium transition-all flex items-center justify-center">
                            <i data-lucide="x" class="w-4 h-4"></i>
                        </a>
                    </div>
                </form>
                </div>
            </div>
        </div>
        
        {{-- Admin Users List --}}
        <div class="lg:col-span-3">
            <div class="bg-white border border-slate-200 rounded-xl shadow-sm overflow-hidden">
                <div class="p-4 border-b border-slate-200 bg-gradient-to-r from-slate-50 to-slate-100">
                    <div class="flex items-center justify-between">
                        <h2 class="text-[15px] font-semibold text-slate-900 flex items-center gap-2">
                            <i data-lucide="users" class="w-4 h-4 text-primary-600"></i>
                            {{ __('pages.admin_list') }}
                        </h2>
                        <span class="text-[13px] text-slate-600">
                            {{ __('pages.all_items_showing') }} {{ $adminUsers->total() }}{{ __('pages.items_showing') }} {{ $adminUsers->firstItem() ?? 0 }}-{{ $adminUsers->lastItem() ?? 0 }}{{ __('pages.items_displayed') }}
                        </span>
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-[14px]">
                        <thead class="bg-slate-50 border-b border-slate-200">
                            <tr>
                                <th class="px-5 py-3.5 text-left font-semibold text-slate-700">
                                        {{ __('pages.name') }}
                                </th>
                                <th class="px-5 py-3.5 text-left font-semibold text-slate-700">
                                        {{ __('pages.email') }}
                                </th>
                                <th class="px-5 py-3.5 text-left font-semibold text-slate-700">
                                        {{ __('pages.role') }}
                                </th>
                                <th class="px-5 py-3.5 text-left font-semibold text-slate-700">
                                        {{ __('pages.status') }}
                                </th>
                                <th class="px-5 py-3.5 text-left font-semibold text-slate-700">
                                        {{ __('pages.created_at') }}
                                </th>
                                <th class="px-5 py-3.5 text-center font-semibold text-slate-700">
                                        {{ __('pages.actions') }}
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200">
                            @forelse($adminUsers as $adminUser)
                                <tr class="hover:bg-slate-50 transition-colors">
                                    <td class="px-5 py-4 text-slate-900 font-medium">
                                        <div class="flex items-center gap-2">
                                            <div class="w-8 h-8 rounded-full bg-gradient-to-br from-primary-400 to-primary-600 flex items-center justify-center text-white text-[12px] font-semibold">
                                                {{ mb_substr($adminUser->user->name, 0, 1) }}
                                            </div>
                                            <span>{{ $adminUser->user->name }}</span>
                                        </div>
                                    </td>
                                    <td class="px-5 py-4 text-slate-700">
                                        <div class="flex items-center gap-2">
                                            <i data-lucide="mail" class="w-4 h-4 text-slate-400"></i>
                                            <span>{{ $adminUser->user->email }}</span>
                                        </div>
                                    </td>
                                    <td class="px-5 py-4">
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[12px] font-medium bg-primary-100 text-primary-800 border border-primary-200">
                                            <i data-lucide="shield" class="w-3.5 h-3.5"></i>
                                            {{ __('roles.' . $adminUser->role) }}
                                        </span>
                                    </td>
                                    <td class="px-5 py-4">
                                        @if($adminUser->trashed())
                                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[12px] font-medium bg-red-100 text-red-800 border border-red-200">
                                                <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                                                {{ __('pages.deleted') }}
                                            </span>
                                        @elseif($adminUser->isBlocked())
                                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[12px] font-medium bg-orange-100 text-orange-800 border border-orange-200">
                                                <i data-lucide="lock" class="w-3.5 h-3.5"></i>
                                                {{ __('pages.blocked') }}
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[12px] font-medium bg-emerald-100 text-emerald-800 border border-emerald-200">
                                                <i data-lucide="check-circle" class="w-3.5 h-3.5"></i>
                                                {{ __('pages.active') }}
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-5 py-4 text-slate-600 text-[13px]">
                                        <div class="flex items-center gap-2">
                                            <i data-lucide="calendar" class="w-4 h-4 text-slate-400"></i>
                                            <span>{{ $adminUser->created_at->format('Y/m/d') }}</span>
                                        </div>
                                    </td>
                                    <td class="px-5 py-4">
                                        <div class="flex items-center justify-center gap-2">
                                            @if($adminUser->trashed())
                                                <form action="{{ route('admin.admin-users.restore', $adminUser->id) }}" method="POST" class="inline">
                                                    @csrf
                                                    <button type="submit" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-emerald-50 hover:bg-emerald-100 text-emerald-700 text-[13px] font-medium transition-all">
                                                        <i data-lucide="rotate-ccw" class="w-4 h-4"></i>
                                                        {{ __('pages.restore') }}
                                                    </button>
                                                </form>
                                            @else
                                                <a href="{{ route('admin.admin-users.edit', $adminUser) }}" 
                                                   class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-primary-50 hover:bg-primary-100 text-primary-700 text-[13px] font-medium transition-all">
                                                    <i data-lucide="edit" class="w-4 h-4"></i>
                                                    {{ __('common.edit') }}
                                                </a>
                                                
                                                @if($adminUser->isBlocked())
                                                    <form action="{{ route('admin.admin-users.unblock', $adminUser) }}" method="POST" class="inline">
                                                        @csrf
                                                        <button type="submit" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-emerald-50 hover:bg-emerald-100 text-emerald-700 text-[13px] font-medium transition-all">
                                                            <i data-lucide="unlock" class="w-4 h-4"></i>
                                                            {{ __('pages.unblock') }}
                                                        </button>
                                                    </form>
                                                @else
                                                    <button type="button" onclick="openBlockModal({{ $adminUser->id }}, '{{ $adminUser->user->name }}')" 
                                                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-orange-50 hover:bg-orange-100 text-orange-700 text-[13px] font-medium transition-all">
                                                        <i data-lucide="lock" class="w-4 h-4"></i>
                                                        {{ __('pages.block') }}
                                                    </button>
                                                @endif
                                                
                                                <button type="button" onclick="openDeleteModal({{ $adminUser->id }}, '{{ $adminUser->user->name }}')" 
                                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-red-50 hover:bg-red-100 text-red-700 text-[13px] font-medium transition-all">
                                                    <i data-lucide="trash-2" class="w-4 h-4"></i>
                                                    {{ __('common.delete') }}
                                                </button>
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
                                            <p class="text-[14px] font-medium">{{ __('pages.no_admin_users_found') }}</p>
                                            <p class="text-[12px] text-slate-400">{{ __('pages.change_search_conditions') }}</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                
                @if($adminUsers->hasPages())
                    <div class="px-5 py-4 border-t border-slate-200 bg-slate-50">
                        <div class="flex items-center justify-between">
                            <span class="text-[13px] text-slate-600">
                                {{ __('pages.all_items_showing') }} {{ $adminUsers->total() }}{{ __('pages.items_showing') }} {{ $adminUsers->firstItem() ?? 0 }}-{{ $adminUsers->lastItem() ?? 0 }}{{ __('pages.items_displayed') }}
                            </span>
                            <div class="flex items-center gap-2">
                                {{ $adminUsers->onEachSide(1)->links('pagination::tailwind') }}
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Block Modal --}}
    <div id="blockModal" class="fixed inset-0 bg-black/50 backdrop-blur-sm z-50 hidden items-center justify-center">
        <div class="bg-white rounded-xl shadow-xl max-w-md w-full mx-4">
            <form action="" method="POST" id="blockForm">
                @csrf
                <div class="p-6">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="h-12 w-12 rounded-full bg-orange-100 flex items-center justify-center">
                            <i data-lucide="lock" class="w-6 h-6 text-orange-600"></i>
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-slate-900">{{ __('pages.block_admin_user') }}</h3>
                            <p class="text-[13px] text-slate-600" id="blockUserName"></p>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-[13px] font-semibold text-slate-700 mb-2">
                            {{ __('pages.block_reason') }} <span class="text-red-500">*</span>
                        </label>
                        <textarea name="blocked_reason" rows="4" required
                                  class="w-full px-4 py-2.5 rounded-lg border border-slate-300 text-[14px] focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20"
                                  placeholder="{{ __('pages.block_reason_placeholder') }}"></textarea>
                    </div>
                    
                    <div class="flex gap-3">
                        <button type="button" onclick="closeBlockModal()" 
                                class="flex-1 px-4 py-2.5 rounded-lg border border-slate-300 hover:bg-slate-50 text-slate-700 text-[14px] font-medium transition-all">
                            {{ __('common.cancel') }}
                        </button>
                        <button type="submit" 
                                class="flex-1 px-4 py-2.5 rounded-lg bg-orange-600 hover:bg-orange-700 text-white text-[14px] font-medium shadow-md transition-all">
                            {{ __('pages.block') }}
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- Delete Modal --}}
    <div id="deleteModal" class="fixed inset-0 bg-black/50 backdrop-blur-sm z-50 hidden items-center justify-center">
        <div class="bg-white rounded-xl shadow-xl max-w-md w-full mx-4">
            <form action="" method="POST" id="deleteForm">
                @csrf
                @method('DELETE')
                <div class="p-6">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="h-12 w-12 rounded-full bg-red-100 flex items-center justify-center">
                            <i data-lucide="trash-2" class="w-6 h-6 text-red-600"></i>
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-slate-900">{{ __('pages.delete_admin_user') }}</h3>
                            <p class="text-[13px] text-slate-600" id="deleteUserName"></p>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-[13px] font-semibold text-slate-700 mb-2">
                            {{ __('pages.delete_reason') }} <span class="text-red-500">*</span>
                        </label>
                        <textarea name="deleted_reason" rows="4" required
                                  class="w-full px-4 py-2.5 rounded-lg border border-slate-300 text-[14px] focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20"
                                  placeholder="{{ __('pages.delete_reason_placeholder') }}"></textarea>
                    </div>
                    
                    <div class="flex gap-3">
                        <button type="button" onclick="closeDeleteModal()" 
                                class="flex-1 px-4 py-2.5 rounded-lg border border-slate-300 hover:bg-slate-50 text-slate-700 text-[14px] font-medium transition-all">
                            {{ __('common.cancel') }}
                        </button>
                        <button type="submit" 
                                class="flex-1 px-4 py-2.5 rounded-lg bg-red-600 hover:bg-red-700 text-white text-[14px] font-medium shadow-md transition-all">
                            {{ __('common.delete') }}
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openBlockModal(id, name) {
            document.getElementById('blockForm').action = `/admin/admin-users/${id}/block`;
            document.getElementById('blockUserName').textContent = name;
            document.getElementById('blockModal').classList.remove('hidden');
            document.getElementById('blockModal').classList.add('flex');
            if (typeof lucide !== 'undefined') {
                lucide.createIcons();
            }
        }

        function closeBlockModal() {
            document.getElementById('blockModal').classList.add('hidden');
            document.getElementById('blockModal').classList.remove('flex');
            document.getElementById('blockForm').reset();
        }

        function openDeleteModal(id, name) {
            document.getElementById('deleteForm').action = `/admin/admin-users/${id}`;
            document.getElementById('deleteUserName').textContent = name;
            document.getElementById('deleteModal').classList.remove('hidden');
            document.getElementById('deleteModal').classList.add('flex');
            if (typeof lucide !== 'undefined') {
                lucide.createIcons();
            }
        }

        function closeDeleteModal() {
            document.getElementById('deleteModal').classList.add('hidden');
            document.getElementById('deleteModal').classList.remove('flex');
            document.getElementById('deleteForm').reset();
        }

        // Close modals on outside click
        document.getElementById('blockModal')?.addEventListener('click', function(e) {
            if (e.target === this) {
                closeBlockModal();
            }
        });

        document.getElementById('deleteModal')?.addEventListener('click', function(e) {
            if (e.target === this) {
                closeDeleteModal();
            }
        });

        // Initialize Lucide icons
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
    </script>
@endsection


