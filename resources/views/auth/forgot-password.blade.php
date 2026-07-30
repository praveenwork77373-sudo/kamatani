@extends('layouts.guest')

@section('title', 'パスワード再設定 | ユニフォーム管理システム')

@section('content')
    <div class="guest-card px-8 py-10 w-full">
        <div class="text-center mb-8 h-[240px]">
            {{-- Full brand lockup (same as login) --}}
            <img
                src="{{ asset('brand.png') }}"
                alt="ユニフォーム管理システム — KAMATANI"
                class="mx-auto w-full max-w-[280px] h-auto object-contain select-none"
                height="280"
                decoding="async"
            >
        </div>

        <div class="text-center mb-6">
            <h1 class="text-lg font-bold text-slate-900">パスワード再設定</h1>
            <p class="mt-2 text-[13px] text-slatebrand leading-relaxed">
                登録済みのメールアドレスを入力してください。<br>
                再設定用 URL（有効期限 24 時間・1 回限り）を送信します。
            </p>
        </div>

        @if(session('status'))
            <div class="mb-4 p-3.5 rounded-[10px] bg-emerald-50 border border-emerald-200 text-[13px] text-emerald-800">
                {{ session('status') }}
            </div>
        @endif

        <form method="POST" action="{{ route('password.email') }}" class="space-y-4">
            @csrf
            <div>
                <label class="block text-[13px] font-semibold text-slate-800 mb-2">メールアドレス</label>
                <input
                    type="email"
                    name="email"
                    value="{{ old('email') }}"
                    required
                    autofocus
                    class="guest-input w-full px-4 py-3 text-[14px]"
                    placeholder="メールアドレス"
                >
                @error('email')
                    <p class="mt-1.5 text-[13px] text-red-600">{{ $message }}</p>
                @enderror
            </div>
            <button type="submit" class="guest-btn w-full py-3 text-[14px] font-semibold text-white transition-colors">
                再設定メールを送信する
            </button>
            <p class="text-center text-[13px]">
                <a href="{{ route('login') }}" class="font-medium text-brand hover:text-brand-hover hover:underline">
                    ログイン画面へ戻る
                </a>
            </p>
        </form>
    </div>
@endsection
