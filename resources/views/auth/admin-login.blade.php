@extends('layouts.guest')

@section('title', __('auth.admin_login') . ' | ' . __('auth.system_title'))

@section('content')
    <div class="guest-card px-8 py-10 w-full">
        <div class="text-center mb-6">
            <img
                src="{{ asset('images/brand/logo.png') }}"
                alt="{{ __('pages.system_brand_line') }}"
                class="mx-auto w-full max-w-[240px] h-auto object-contain select-none"
                decoding="async"
            >
            <p class="mt-3 inline-flex items-center px-3 py-1 rounded-full bg-slate-800 text-white text-[12px] font-semibold">
                {{ __('auth.admin_login') }}
            </p>
        </div>

        @if(session('error'))
            <div class="mb-5 p-3.5 rounded-[10px] bg-red-50 border border-red-200 text-[13px] text-red-800">
                {{ session('error') }}
            </div>
        @endif

        @if(session('status'))
            <div class="mb-5 p-3.5 rounded-[10px] bg-emerald-50 border border-emerald-200 text-[13px] text-emerald-800">
                {{ session('status') }}
            </div>
        @endif

        <form method="POST" action="{{ route('admin.login.post') }}" class="space-y-5">
            @csrf

            <div>
                <label class="block text-[13px] font-semibold text-slate-800 mb-2">{{ __('auth.email') }}</label>
                <input type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username"
                    class="guest-input w-full px-4 py-3 text-[14px] text-slate-900"
                    placeholder="{{ __('auth.email') }}">
                @error('email')
                    <p class="mt-1.5 text-[13px] text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="block text-[13px] font-semibold text-slate-800 mb-2">{{ __('auth.password') }}</label>
                <div class="relative">
                    <input type="password" name="password" id="password" required autocomplete="current-password"
                        class="guest-input w-full px-4 py-3 pr-11 text-[14px] text-slate-900"
                        placeholder="{{ __('auth.password') }}">
                    <button type="button" id="toggle-password"
                        class="absolute right-3 top-1/2 -translate-y-1/2 text-slatebrand text-[12px] font-medium hover:text-brand">{{ __('pages.view') }}</button>
                </div>
                @error('password')
                    <p class="mt-1.5 text-[13px] text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex items-center justify-between text-[13px]">
                <label class="inline-flex items-center gap-2 cursor-pointer text-slate-700">
                    <input type="checkbox" name="remember" class="rounded border-slate-300 text-brand focus:ring-brand">
                    <span>{{ __('auth.remember_me') }}</span>
                </label>
                <a href="{{ route('password.request', ['portal' => 'admin']) }}" class="font-medium text-brand hover:underline">
                    {{ __('auth.forgot_password') }}
                </a>
            </div>

            <button type="submit" class="guest-btn w-full py-3 text-[14px] font-semibold text-white">
                {{ __('auth.login') }}
            </button>
        </form>
    </div>

    <script>
        (function () {
            var input = document.getElementById('password');
            var btn = document.getElementById('toggle-password');
            if (!input || !btn) return;
            btn.addEventListener('click', function () {
                var show = input.type === 'password';
                input.type = show ? 'text' : 'password';
                btn.textContent = show ? @json(__('pages.hide')) : @json(__('pages.show'));
            });
        })();
    </script>
@endsection
