@extends('layouts.guest')

@section('title', '新しいパスワードの設定 | ユニフォーム管理システム')

@section('content')
    <div class="bg-white rounded-2xl shadow-xl px-8 py-10 border border-slate-200 max-w-md w-full">
        <div class="text-center mb-6">
            <h1 class="text-xl font-bold text-slate-900 mb-2">新しいパスワードを設定</h1>
            <p class="text-[13px] text-slate-600">有効な再設定リンクからアクセスしています。</p>
        </div>

        <form method="POST" action="{{ route('password.update') }}" class="space-y-4">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">

            <div>
                <label class="block text-[14px] font-semibold text-slate-700 mb-2">メールアドレス</label>
                <input type="email" name="email" value="{{ old('email', $email ?? '') }}" required
                       class="w-full px-4 py-3 rounded-lg border border-slate-300 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 text-[14px]">
                @error('email')
                    <p class="mt-1.5 text-[13px] text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="block text-[14px] font-semibold text-slate-700 mb-2">新しいパスワード</label>
                <input type="password" name="password" required minlength="8"
                       class="w-full px-4 py-3 rounded-lg border border-slate-300 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 text-[14px]">
                <p class="mt-1 text-[12px] text-slate-500">8文字以上</p>
            </div>

            <div>
                <label class="block text-[14px] font-semibold text-slate-700 mb-2">新しいパスワード（確認）</label>
                <input type="password" name="password_confirmation" required minlength="8"
                       class="w-full px-4 py-3 rounded-lg border border-slate-300 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 text-[14px]">
                @error('password')
                    <p class="mt-1.5 text-[13px] text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <button type="submit"
                    class="w-full bg-primary-600 hover:bg-primary-700 text-white text-[14px] font-semibold py-3 rounded-lg">
                パスワードを変更する
            </button>
        </form>
    </div>
@endsection
