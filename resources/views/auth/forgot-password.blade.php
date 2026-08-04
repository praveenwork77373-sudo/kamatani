@extends('layouts.guest')

@section('title', __('pages.ui_4d458b8270'))

@section('content')
    @php $portal = $portal ?? 'hospital'; @endphp
    <div class="guest-card px-8 py-10 w-full">
        <div class="text-center mb-6">
            <img
                src="{{ asset('images/brand/logo.png') }}"
                alt="{{ __('pages.system_brand_line') }}"
                class="mx-auto w-full max-w-[240px] h-auto object-contain select-none"
                decoding="async"
            >
            <h1 class="mt-4 text-lg font-bold text-slate-900">{{ __('pages.ui_77a81aaae7') }}</h1>
            <p class="mt-2 text-[13px] text-slatebrand leading-relaxed">
                {{ __('pages.forgot_intro_1') }}<br>
                {{ __('pages.forgot_intro_2') }}
            </p>
        </div>

        @if(session('status'))
            <div class="mb-4 p-3.5 rounded-[10px] bg-emerald-50 border border-emerald-200 text-[13px] text-emerald-800">
                {{ session('status') }}
            </div>
        @endif

        <form method="POST" action="{{ route('password.email') }}" class="space-y-4">
            @csrf
            <input type="hidden" name="portal" value="{{ $portal }}">
            <div>
                <label class="block text-[13px] font-semibold text-slate-800 mb-2">{{ __('common.email') }}</label>
                <input type="email" name="email" value="{{ old('email') }}" required autofocus
                    class="guest-input w-full px-4 py-3 text-[14px]" placeholder="{{ __('common.email') }}">
                @error('email')
                    <p class="mt-1.5 text-[13px] text-red-600">{{ $message }}</p>
                @enderror
            </div>
            <button type="submit" class="guest-btn w-full py-3 text-[14px] font-semibold text-white">
                {{ __('pages.send_reset_email') }}
            </button>
            <p class="text-center text-[13px]">
                <a href="{{ $portal === 'admin' ? route('admin.login') : route('login') }}"
                   class="font-medium text-brand hover:underline">
                    {{ __('pages.back_to_login') }}
                </a>
            </p>
        </form>
    </div>
@endsection
