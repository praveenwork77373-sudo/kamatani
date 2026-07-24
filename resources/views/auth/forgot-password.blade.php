@extends('layouts.guest')

@section('title', 'パスワード再設定 | ユニフォーム管理システム')

@section('content')
    <div class="bg-white shadow rounded-xl px-6 py-8">
        <h1 class="text-lg font-semibold text-slate-800 mb-4">
            パスワード再設定
        </h1>
        <p class="text-xs text-slate-600 mb-4">
            登録済みのメールアドレスを入力してください。パスワード再設定用のURLをお送りします。
        </p>

        <form method="POST" action="{{ route('password.email') }}" class="space-y-4">
            @csrf
            <div>
                <label class="block text-sm text-slate-700 mb-1">メールアドレス</label>
                <input type="email" name="email" value="{{ old('email') }}" required
                       class="w-full px-4 py-3 rounded-lg border border-slate-300 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 text-[14px] bg-white shadow-sm transition-all">
                @error('email')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>
            <button type="submit"
                    class="w-full bg-sky-700 hover:bg-sky-800 text-white text-sm font-medium py-2.5 rounded transition">
                再設定メールを送信する
            </button>
        </form>
    </div>
@endsection


