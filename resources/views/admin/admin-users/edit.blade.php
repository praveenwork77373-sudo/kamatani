@extends('layouts.app')

@section('title', __('pages.edit_admin_user') . ' | ' . config('app.name'))
@section('page_title', __('pages.edit_admin_user'))

@section('content')
    <div class="mb-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-slate-900 tracking-tight">{{ __('pages.edit_admin_user') }}</h1>
                <p class="text-[14px] text-slate-600 mt-1.5">{{ __('pages.edit_admin_user_description') }}</p>
            </div>
            <a href="{{ route('admin.admin-users.index') }}" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-lg border border-slate-300 hover:bg-slate-50 text-slate-700 text-[14px] font-medium transition-all">
                <i data-lucide="arrow-left" class="w-4 h-4"></i>
                {{ __('common.back') }}
            </a>
        </div>
    </div>

    @if (session('status'))
        <div class="mb-4 p-4 rounded-lg bg-emerald-50 border border-emerald-200 text-[14px] text-emerald-800">
            {{ session('status') }}
        </div>
    @endif
    @if (session('error'))
        <div class="mb-4 p-4 rounded-lg bg-red-50 border border-red-200 text-[14px] text-red-800">
            {{ session('error') }}
        </div>
    @endif

    <div class="bg-white border border-slate-200 rounded-xl shadow-sm p-6">
        <form action="{{ route('admin.admin-users.update', $adminUser) }}" method="POST">
            @csrf
            @method('PUT')
            
            <div class="space-y-6">
                {{-- Name --}}
                <div>
                    <label for="name" class="block text-[13px] font-semibold text-slate-700 mb-2">
                        {{ __('pages.name') }} <span class="text-red-500">*</span>
                    </label>
                    <input type="text" id="name" name="name" value="{{ old('name', $adminUser->user->name) }}" required
                           class="w-full px-4 py-2.5 rounded-lg border border-slate-300 text-[14px] focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 @error('name') border-red-300 @enderror">
                    @error('name')
                        <p class="mt-1.5 text-[13px] text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Email --}}
                <div>
                    <label for="email" class="block text-[13px] font-semibold text-slate-700 mb-2">
                        {{ __('pages.email') }} <span class="text-red-500">*</span>
                    </label>
                    <input type="email" id="email" name="email" value="{{ old('email', $adminUser->user->email) }}" required
                           class="w-full px-4 py-2.5 rounded-lg border border-slate-300 text-[14px] focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 @error('email') border-red-300 @enderror">
                    @error('email')
                        <p class="mt-1.5 text-[13px] text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Role --}}
                <div>
                    <label for="role" class="block text-[13px] font-semibold text-slate-700 mb-2">
                        {{ __('pages.role') }} <span class="text-red-500">*</span>
                    </label>
                    <select id="role" name="role" required
                            class="w-full px-4 py-2.5 rounded-lg border border-slate-300 text-[14px] focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 @error('role') border-red-300 @enderror">
                        <option value="system_admin" @selected(old('role', $adminUser->role) == 'system_admin')>{{ __('roles.system_admin') }}</option>
                        <option value="operation" @selected(old('role', $adminUser->role) == 'operation')>{{ __('roles.operation') }}</option>
                        <option value="sales" @selected(old('role', $adminUser->role) == 'sales')>{{ __('roles.sales') }}</option>
                        <option value="accounting" @selected(old('role', $adminUser->role) == 'accounting')>{{ __('roles.accounting') }}</option>
                    </select>
                    @error('role')
                        <p class="mt-1.5 text-[13px] text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Status Info --}}
                @if($adminUser->isBlocked())
                    <div class="p-4 bg-orange-50 border border-orange-200 rounded-lg">
                        <div class="flex items-start gap-3">
                            <i data-lucide="alert-triangle" class="w-5 h-5 text-orange-600 mt-0.5"></i>
                            <div>
                                <p class="text-[13px] font-semibold text-orange-900 mb-1">{{ __('pages.user_is_blocked') }}</p>
                                <p class="text-[13px] text-orange-700">{{ __('pages.block_reason') }}: {{ $adminUser->blocked_reason }}</p>
                                <p class="text-[13px] text-orange-600 mt-1">{{ __('pages.blocked_at') }}: {{ $adminUser->blocked_at->format('Y/m/d H:i') }}</p>
                            </div>
                        </div>
                    </div>
                @endif
            </div>

            <div class="flex gap-3 mt-8 pt-6 border-t border-slate-200">
                <a href="{{ route('admin.admin-users.index') }}" 
                   class="px-6 py-2.5 rounded-lg border border-slate-300 hover:bg-slate-50 text-slate-700 text-[14px] font-medium transition-all">
                    {{ __('common.cancel') }}
                </a>
                <button type="submit" 
                        class="px-6 py-2.5 rounded-lg bg-primary-600 hover:bg-primary-700 text-white text-[14px] font-medium shadow-md transition-all">
                    {{ __('pages.update_admin_user') }}
                </button>
            </div>
        </form>
    </div>

    {{-- Password: owned by the account holder — system admin only triggers reset email --}}
    <div class="bg-white border border-slate-200 rounded-xl shadow-sm p-6 mt-6">
        <h2 class="text-[16px] font-semibold text-slate-900 mb-2">{{ __('pages.admin_user_password_section') }}</h2>
        <p class="text-[13px] text-slate-600 mb-4 leading-relaxed">{{ __('pages.admin_user_password_policy_hint') }}</p>

        @if($adminUser->isBlocked() || $adminUser->trashed())
            <p class="text-[13px] text-orange-700">{{ __('pages.cannot_send_password_reset_to_inactive') }}</p>
        @else
            <form action="{{ route('admin.admin-users.send-password-reset', $adminUser) }}" method="POST"
                  onsubmit="return confirm(@json(__('pages.confirm_send_password_reset')));">
                @csrf
                <button type="submit"
                        class="inline-flex items-center gap-2 px-5 py-2.5 rounded-lg border border-slate-300 hover:bg-slate-50 text-slate-800 text-[14px] font-medium transition-all">
                    <i data-lucide="mail" class="w-4 h-4"></i>
                    {{ __('pages.send_password_reset_email') }}
                </button>
            </form>
        @endif
    </div>

    <script>
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
    </script>
@endsection
