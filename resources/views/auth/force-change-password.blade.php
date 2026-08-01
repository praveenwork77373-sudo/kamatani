@extends('layouts.guest')

@section('title', '初回パスワード変更 | ユニフォーム管理システム')

@section('content')
    <div class="guest-card px-8 py-10 w-full">
        <div class="text-center mb-6">
            <img
                src="{{ asset('brand.png') }}"
                alt="ユニフォーム管理システム — KAMATANI"
                class="mx-auto w-full max-w-[240px] h-auto object-contain select-none"
                decoding="async"
            >
            <h1 class="mt-4 text-lg font-bold text-slate-900">初回パスワード変更</h1>
            <p class="mt-2 text-[13px] text-slate-600 leading-relaxed">
                仮パスワードでログインしています。<br>
                新しいパスワードを設定してください。
            </p>
        </div>

        <form method="POST" action="{{ route('password.force-change.update') }}" class="space-y-4">
            @csrf
            <div>
                <label class="block text-[13px] font-semibold text-slate-800 mb-2">新しいパスワード</label>
                <input type="password" name="password" required autofocus minlength="8"
                    class="guest-input w-full px-4 py-3 text-[14px]" placeholder="8文字以上">
                @error('password')
                    <p class="mt-1.5 text-[13px] text-red-600">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label class="block text-[13px] font-semibold text-slate-800 mb-2">新しいパスワード（確認）</label>
                <input type="password" name="password_confirmation" required minlength="8"
                    class="guest-input w-full px-4 py-3 text-[14px]" placeholder="もう一度入力">
            </div>
            <button type="submit" class="guest-btn w-full py-3 text-[14px] font-semibold text-white">
                パスワードを変更して続行
            </button>
        </form>
    </div>
@endsection
