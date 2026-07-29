@extends('layouts.guest')

@section('title', 'パスワード再設定 | ユニフォーム管理システム')

@section('content')
    <div class="bg-white rounded-2xl shadow-xl px-8 py-10 border border-slate-200 max-w-md w-full">
        <div class="text-center mb-6">
            <div class="inline-flex items-center justify-center h-14 w-14 rounded-full bg-primary-100 mb-3">
                <i class="ph ph-key text-2xl text-primary-600"></i>
            </div>
            <h1 class="text-xl font-bold text-slate-900 mb-2">パスワード再設定</h1>
            <p class="text-[13px] text-slate-600">
                登録済みのメールアドレスを入力してください。<br>
                再設定用 URL（有効期限 24 時間・1 回限り）を送信します。
            </p>
        </div>

        @if(session('status'))
            <div class="mb-4 p-4 bg-emerald-50 border-l-4 border-emerald-500 rounded-lg text-[13px] text-emerald-800">
                {{ session('status') }}
            </div>
        @endif

        <form method="POST" action="{{ route('password.email') }}" class="space-y-4">
            @csrf
            <div>
                <label class="block text-[14px] font-semibold text-slate-700 mb-2">メールアドレス</label>
                <input type="email" name="email" value="{{ old('email') }}" required autofocus
                       class="w-full px-4 py-3 rounded-lg border border-slate-300 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 text-[14px]">
                @error('email')
                    <p class="mt-1.5 text-[13px] text-red-600">{{ $message }}</p>
                @enderror
            </div>
            <button type="submit"
                    class="w-full bg-primary-600 hover:bg-primary-700 text-white text-[14px] font-semibold py-3 rounded-lg">
                再設定メールを送信する
            </button>
            <p class="text-center text-[13px]">
                <a href="{{ route('login') }}" class="text-primary-600 hover:underline">ログイン画面へ戻る</a>
            </p>
        </form>
    </div>
@endsection
