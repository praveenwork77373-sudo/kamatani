@extends('layouts.guest')

@section('title', __('pages.ui_8cc81aac50'))

@section('content')
    <div class="guest-card px-8 py-10 w-full">
        <div class="text-center mb-6">
            <img
                src="{{ asset('images/brand/logo.png') }}"
                alt="{{ __('pages.system_brand_line') }}"
                class="mx-auto w-full max-w-[240px] h-auto object-contain select-none"
                decoding="async"
            >
            <h1 class="mt-4 text-lg font-bold text-slate-900">{{ __('pages.force_change_password_title') }}</h1>
            <p class="mt-2 text-[13px] text-slate-600 leading-relaxed">
                {{ __('pages.force_pw_intro_1') }}<br>
                {{ __('pages.force_pw_intro_2') }}
            </p>
        </div>

        <form method="POST" action="{{ route('password.force-change.update') }}" class="space-y-4">
            @csrf
            <div>
                <label class="block text-[13px] font-semibold text-slate-800 mb-2">{{ __('pages.new_password') }}</label>
                <input type="password" name="password" required autofocus minlength="8"
                    class="guest-input w-full px-4 py-3 text-[14px]" placeholder="{{ __('pages.password_placeholder') }}">
                @error('password')
                    <p class="mt-1.5 text-[13px] text-red-600">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label class="block text-[13px] font-semibold text-slate-800 mb-2">{{ __('pages.new_password_confirm') }}</label>
                <input type="password" name="password_confirmation" required minlength="8"
                    class="guest-input w-full px-4 py-3 text-[14px]" placeholder="{{ __('pages.enter_again') }}">
            </div>
            <button type="submit" class="guest-btn w-full py-3 text-[14px] font-semibold text-white">
                {{ __('pages.change_password_continue') }}
            </button>
        </form>
    </div>
@endsection
