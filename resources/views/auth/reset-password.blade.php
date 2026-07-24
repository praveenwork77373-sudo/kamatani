@extends('layouts.guest')

@section('title', 'パスワード再設定 | ユニフォーム管理システム')

@section('content')
    <div class="bg-white shadow rounded-xl px-6 py-8">
        <h1 class="text-lg font-semibold text-slate-800 mb-4">
            新しいパスワードを設定
        </h1>

        <form method="POST" action="{{ route('password.update') }}" class="space-y-4">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">

            <div>
                <label class="block text-sm text-slate-700 mb-1">メールアドレス</label>
                <input type="email" name="email" value="{{ old('email') }}" required
                       class="w-full px-4 py-3 rounded-lg border border-slate-300 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 text-[14px] bg-white shadow-sm transition-all">
                @error('email')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="block text-sm text-slate-700 mb-1">新しいパスワード</label>
                <input type="password" name="password" required
                       class="w-full px-4 py-3 rounded-lg border border-slate-300 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 text-[14px] bg-white shadow-sm transition-all">
            </div>

            <div>
                <label class="block text-sm text-slate-700 mb-1">新しいパスワード（確認用）</label>
                <input type="password" name="password_confirmation" required
                       class="w-full px-4 py-3 rounded-lg border border-slate-300 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 text-[14px] bg-white shadow-sm transition-all">
                @error('password')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <button type="submit"
                    class="w-full bg-sky-700 hover:bg-sky-800 text-white text-sm font-medium py-2.5 rounded transition">
                パスワードを変更する
            </button>
        </form>
    </div>
@endsection


