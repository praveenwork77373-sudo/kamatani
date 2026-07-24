@extends('layouts.guest')

@section('title', 'ログイン | ユニフォーム管理システム')

@section('content')
    <div class="bg-white rounded-2xl shadow-xl px-8 py-10 border border-slate-200 max-w-md w-full">
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center h-16 w-16 rounded-full bg-primary-100 mb-4">
                <i class="ph ph-shield-check text-3xl text-primary-600"></i>
            </div>
            <h1 class="text-2xl font-bold text-slate-900 mb-2">
                {{ __('auth.system_title') }}
            </h1>
            <p class="text-[14px] text-slate-600">
                {{ __('auth.system_subtitle') }}
            </p>
        </div>

        @if(session('error'))
            <div class="mb-6 p-4 bg-red-50 border-l-4 border-red-500 rounded-lg">
                <div class="flex items-center gap-2">
                    <i class="ph ph-warning text-lg text-red-600"></i>
                    <span class="text-[14px] text-red-800 font-medium">{{ session('error') }}</span>
                </div>
            </div>
        @endif

        @if(session('status'))
            <div class="mb-6 p-4 bg-emerald-50 border-l-4 border-emerald-500 rounded-lg">
                <div class="flex items-center gap-2">
                    <i class="ph ph-check-circle text-lg text-emerald-600"></i>
                    <span class="text-[14px] text-emerald-800 font-medium">{{ session('status') }}</span>
                </div>
            </div>
        @endif

        <form method="POST" action="{{ route('login.post') }}" class="space-y-5">
            @csrf
            <div>
                <label class="block text-slate-700 text-[14px] font-semibold mb-2">
                    {{ __('auth.email') }}
                </label>
                <input type="email" name="email" value="{{ old('email') }}" required autofocus
                       class="w-full px-4 py-3 rounded-lg border border-slate-300 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 text-[14px] bg-white shadow-sm transition-all"
                       placeholder="{{ __('auth.email') }}">
                @error('email')
                    <p class="mt-1.5 text-[13px] text-red-600 flex items-center gap-1">
                        <i class="ph ph-warning text-xs"></i>
                        {{ $message }}
                    </p>
                @enderror
            </div>
            
            <div>
                <label class="block text-slate-700 text-[14px] font-semibold mb-2">
                    {{ __('auth.password') }}
                </label>
                <input type="password" name="password" required
                       class="w-full px-4 py-3 rounded-lg border border-slate-300 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 text-[14px] bg-white shadow-sm transition-all"
                       placeholder="{{ __('auth.password') }}">
                @error('password')
                    <p class="mt-1.5 text-[13px] text-red-600 flex items-center gap-1">
                        <i class="ph ph-warning text-xs"></i>
                        {{ $message }}
                    </p>
                @enderror
            </div>
            
            <div class="flex items-center justify-between text-[13px] text-slate-600">
                <label class="inline-flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" name="remember" class="rounded border-slate-300 text-primary-600 focus:ring-primary-500">
                    <span>{{ __('auth.remember_me') }}</span>
                </label>
                <a href="{{ route('password.request') }}" class="text-primary-600 hover:text-primary-700 hover:underline font-medium">
                    {{ __('auth.forgot_password') }}
                </a>
            </div>
            
            <div class="pt-2">
                <button type="submit"
                        class="w-full bg-primary-600 hover:bg-primary-700 text-white text-[14px] font-semibold py-3 rounded-lg transition-all shadow-md flex items-center justify-center gap-2">
                    <i class="ph ph-sign-in text-lg"></i>
                    {{ __('auth.login') }}
                </button>
            </div>
            
            <p class="text-center text-[12px] text-slate-500 mt-4">
                ※ 本システムのご利用には、事前に発行されたアカウントが必要です。
            </p>
        </form>
    </div>
@endsection
