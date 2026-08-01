@extends('layouts.guest')

@section('title', '新しいパスワードの設定 | ユニフォーム管理システム')

@section('content')
    @php $portal = $portal ?? 'hospital'; @endphp
    <div class="guest-card px-8 py-10 w-full">
        <div class="text-center mb-6">
            <img
                src="{{ asset('brand.png') }}"
                alt="ユニフォーム管理システム — KAMATANI"
                class="mx-auto w-full max-w-[240px] h-auto object-contain select-none"
                decoding="async"
            >
            <h1 class="mt-4 text-lg font-bold text-slate-900">新しいパスワードを設定</h1>
            <p class="mt-2 text-[13px] text-slatebrand">有効な再設定リンクからアクセスしています。</p>
        </div>

        <form method="POST" action="{{ route('password.update') }}" class="space-y-4">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">
            <input type="hidden" name="portal" value="{{ $portal }}">

            <div>
                <label class="block text-[13px] font-semibold text-slate-800 mb-2">メールアドレス</label>
                <input type="email" name="email" value="{{ old('email', $email ?? '') }}" required
                    class="guest-input w-full px-4 py-3 text-[14px]">
                @error('email')
                    <p class="mt-1.5 text-[13px] text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="block text-[13px] font-semibold text-slate-800 mb-2">新しいパスワード</label>
                <input type="password" name="password" required minlength="8"
                    class="guest-input w-full px-4 py-3 text-[14px]">
                <p class="mt-1 text-[12px] text-slatebrand">8文字以上</p>
            </div>

            <div>
                <label class="block text-[13px] font-semibold text-slate-800 mb-2">新しいパスワード（確認）</label>
                <input type="password" name="password_confirmation" required minlength="8"
                    class="guest-input w-full px-4 py-3 text-[14px]">
                @error('password')
                    <p class="mt-1.5 text-[13px] text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <button type="submit" class="guest-btn w-full py-3 text-[14px] font-semibold text-white">
                パスワードを変更する
            </button>

            <p class="text-center text-[13px]">
                <a href="{{ $portal === 'admin' ? route('admin.login') : route('login') }}"
                   class="font-medium text-brand hover:underline">
                    ログイン画面へ戻る
                </a>
            </p>
        </form>
    </div>
@endsection
