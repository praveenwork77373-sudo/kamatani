@extends('layouts.app')

@section('title', __('pages.new_admin_user') . ' | ' . config('app.name'))
@section('page_title', __('pages.new_admin_user'))

@section('content')
    <div class="mb-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-slate-900 tracking-tight">{{ __('pages.new_admin_user') }}</h1>
                <p class="text-[14px] text-slate-600 mt-1.5">{{ __('pages.new_admin_user_description') }}</p>
            </div>
            <a href="{{ route('admin.admin-users.index') }}" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-lg border border-slate-300 hover:bg-slate-50 text-slate-700 text-[14px] font-medium transition-all">
                <i data-lucide="arrow-left" class="w-4 h-4"></i>
                {{ __('common.back') }}
            </a>
        </div>
    </div>

    <div class="bg-white border border-slate-200 rounded-xl shadow-sm p-6">
        <form action="{{ route('admin.admin-users.store') }}" method="POST">
            @csrf
            
            <div class="space-y-6">
                {{-- Name --}}
                <div>
                    <label for="name" class="block text-[13px] font-semibold text-slate-700 mb-2">
                        {{ __('pages.name') }} <span class="text-red-500">*</span>
                    </label>
                    <input type="text" id="name" name="name" value="{{ old('name') }}" required
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
                    <input type="email" id="email" name="email" value="{{ old('email') }}" required
                           class="w-full px-4 py-2.5 rounded-lg border border-slate-300 text-[14px] focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 @error('email') border-red-300 @enderror">
                    @error('email')
                        <p class="mt-1.5 text-[13px] text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Password --}}
                <div>
                    <label for="password" class="block text-[13px] font-semibold text-slate-700 mb-2">
                        {{ __('pages.password') }} <span class="text-red-500">*</span>
                    </label>
                    <input type="password" id="password" name="password" required
                           class="w-full px-4 py-2.5 rounded-lg border border-slate-300 text-[14px] focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 @error('password') border-red-300 @enderror">
                    <p class="mt-1.5 text-[13px] text-slate-500">{{ __('pages.password_min_length') }}</p>
                    @error('password')
                        <p class="mt-1.5 text-[13px] text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Password Confirmation --}}
                <div>
                    <label for="password_confirmation" class="block text-[13px] font-semibold text-slate-700 mb-2">
                        {{ __('pages.password_confirmation') }} <span class="text-red-500">*</span>
                    </label>
                    <input type="password" id="password_confirmation" name="password_confirmation" required
                           class="w-full px-4 py-2.5 rounded-lg border border-slate-300 text-[14px] focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20">
                </div>

                {{-- Role --}}
                <div>
                    <label for="role" class="block text-[13px] font-semibold text-slate-700 mb-2">
                        {{ __('pages.role') }} <span class="text-red-500">*</span>
                    </label>
                    <select id="role" name="role" required
                            class="w-full px-4 py-2.5 rounded-lg border border-slate-300 text-[14px] focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 @error('role') border-red-300 @enderror">
                        <option value="">{{ __('pages.select_role') }}</option>
                        <option value="system_admin" @selected(old('role') == 'system_admin')>{{ __('roles.system_admin') }}</option>
                        <option value="operation" @selected(old('role') == 'operation')>{{ __('roles.operation') }}</option>
                        <option value="sales" @selected(old('role') == 'sales')>{{ __('roles.sales') }}</option>
                        <option value="accounting" @selected(old('role') == 'accounting')>{{ __('roles.accounting') }}</option>
                        <option value="contract_plan_manager" @selected(old('role') == 'contract_plan_manager')>{{ __('roles.contract_plan_manager') }}</option>
                    </select>
                    @error('role')
                        <p class="mt-1.5 text-[13px] text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="inline-flex items-center gap-2 text-[13px] text-slate-700">
                        <input type="checkbox" name="receives_app_notifications" value="1"
                               @checked(old('receives_app_notifications', true))
                               class="rounded border-slate-300">
                        申請関連メール通知を受け取る
                    </label>
                </div>
            </div>

            <div class="flex gap-3 mt-8 pt-6 border-t border-slate-200">
                <a href="{{ route('admin.admin-users.index') }}" 
                   class="px-6 py-2.5 rounded-lg border border-slate-300 hover:bg-slate-50 text-slate-700 text-[14px] font-medium transition-all">
                    {{ __('common.cancel') }}
                </a>
                <button type="submit" 
                        class="px-6 py-2.5 rounded-lg bg-primary-600 hover:bg-primary-700 text-white text-[14px] font-medium shadow-md transition-all">
                    {{ __('pages.create_admin_user') }}
                </button>
            </div>
        </form>
    </div>

    <script>
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
    </script>
@endsection
