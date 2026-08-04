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

    {{-- Statistics Cards --}}
    <x-stat-grid :cols="4">
        <x-stat-card tone="blue" icon="users" :value="$stats['total_admins'] ?? 0" :label="__('pages.total_admins')" :badge="__('pages.total')" />
        <x-stat-card tone="emerald" icon="user-check" :value="$stats['active_admins'] ?? 0" :label="__('pages.active')" :badge="__('pages.active')" />
        <x-stat-card tone="amber" icon="user-x" :value="$stats['blocked_admins'] ?? 0" :label="__('pages.blocked_admins')" :badge="__('pages.blocked')" />
        <x-stat-card tone="purple" icon="shield" :value="$stats['system_admins'] ?? 0" :label="__('pages.system_admins')" :badge="__('roles.system_admin')" />
    </x-stat-grid>

    <div class="space-y-6">
        {{-- Horizontal filters above table --}}
        <div class="bg-white border border-slate-200 rounded-xl shadow-sm overflow-hidden">
            <form method="GET" action="{{ route('admin.admin-users.index') }}" class="p-4">
                <x-per-page-field />
                <div class="flex flex-col lg:flex-row lg:items-end gap-3">
                    <div class="flex-1 min-w-0">
                        <label class="block text-[12px] font-semibold text-slate-600 mb-1.5">{{ __('pages.role') }}</label>
                        <select name="role" class="ui-select">
                            <option value="">{{ __('pages.all_roles') }}</option>
                            <option value="system_admin" @selected($selectedRole == 'system_admin')>{{ __('roles.system_admin') }}</option>
                            <option value="operation" @selected($selectedRole == 'operation')>{{ __('roles.operation') }}</option>
                            <option value="sales" @selected($selectedRole == 'sales')>{{ __('roles.sales') }}</option>
                            <option value="accounting" @selected($selectedRole == 'accounting')>{{ __('roles.accounting') }}</option>
                            <option value="contract_plan_manager" @selected($selectedRole == 'contract_plan_manager')>{{ __('roles.contract_plan_manager') }}</option>
                        </select>
                    </div>
                    <div class="flex-1 min-w-0">
                        <label class="block text-[12px] font-semibold text-slate-600 mb-1.5">{{ __('pages.status') }}</label>
                        <select name="status" class="ui-select">
                            <option value="">{{ __('pages.all_statuses') }}</option>
                            <option value="active" @selected($selectedStatus == 'active')>{{ __('pages.active') }}</option>
                            <option value="blocked" @selected($selectedStatus == 'blocked')>{{ __('pages.blocked') }}</option>
                            <option value="deleted" @selected($selectedStatus == 'deleted')>{{ __('pages.deleted') }}</option>
                        </select>
                    </div>
                    <div class="flex-[1.5] min-w-0">
                        <label class="block text-[12px] font-semibold text-slate-600 mb-1.5">{{ __('common.search') }}</label>
                        <input type="text" name="search" value="{{ $searchQuery }}"
                               placeholder="{{ __('pages.search_admin_user_placeholder') }}"
                               class="ui-input">
                    </div>
                    <div class="flex gap-2 shrink-0">
                        <button type="submit" class="ui-btn ui-btn-primary">
                            <i data-lucide="search" class="w-4 h-4"></i>
                            {{ __('common.search') }}
                        </button>
                        <a href="{{ route('admin.admin-users.index') }}" class="ui-btn ui-btn-secondary" title="{{ __('common.reset') }}">
                            <i data-lucide="x" class="w-4 h-4"></i>
                        </a>
                    </div>
                </div>
            </form>
        </div>

        {{-- Admin Users List --}}
        <div class="bg-white border border-slate-200 rounded-xl shadow-sm overflow-hidden">
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

            <x-table-pagination :paginator="$adminUsers" />

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

