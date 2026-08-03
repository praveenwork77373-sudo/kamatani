@extends('layouts.app')

@section('title', 'アカウントロック管理 | ' . __('auth.system_title'))
@section('page_title', 'アカウントロック管理')

@section('content')
    <div class="mb-6 flex items-center justify-between gap-4 flex-wrap">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 tracking-tight">アカウントロック管理</h1>
            <p class="text-[14px] text-slate-600 mt-1.5">ログイン連続失敗による一時ロックを確認・解除できます。</p>
        </div>
    </div>

    @if(session('status'))
        <div class="mb-4 p-4 bg-emerald-50 border-l-4 border-emerald-500 rounded-lg text-[14px] text-emerald-800">
            {{ session('status') }}
        </div>
    @endif

    <div class="bg-white border border-slate-200 rounded-xl shadow-sm overflow-hidden mb-6">
        <form method="GET" action="{{ route('admin.account-locks.index') }}" class="p-4 flex gap-3 items-end">
                <x-per-page-field />
            <div class="flex-1">
                <label class="block text-[13px] font-semibold text-slate-700 mb-1">検索（氏名・メール）</label>
                <input type="text" name="q" value="{{ $searchQuery }}"
                       class="w-full px-3 py-2 rounded-lg border border-slate-300 text-[14px]"
                       placeholder="例: hospital-admin@example.com">
            </div>
            <button type="submit" class="px-4 py-2 rounded-lg bg-primary-600 text-white text-[14px] font-medium hover:bg-primary-700">
                検索
            </button>
        </form>
    </div>

    <div class="bg-white border border-slate-200 rounded-xl shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full text-[14px]">
                <thead class="bg-slate-50 border-b border-slate-200">
                    <tr>
                        <th class="px-5 py-3 text-left font-semibold text-slate-700">ユーザー</th>
                        <th class="px-5 py-3 text-left font-semibold text-slate-700">種別</th>
                        <th class="px-5 py-3 text-left font-semibold text-slate-700">ロック解除予定</th>
                        <th class="px-5 py-3 text-right font-semibold text-slate-700">操作</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($lockedUsers as $user)
                        <tr class="hover:bg-slate-50">
                            <td class="px-5 py-3">
                                <div class="font-medium text-slate-900">{{ $user->name }}</div>
                                <div class="text-[12px] text-slate-500">{{ $user->email }}</div>
                            </td>
                            <td class="px-5 py-3 text-slate-700">
                                @if($user->adminUser)
                                    管理者（{{ $user->adminUser->role }}）
                                @elseif($user->customerUser)
                                    顧客（{{ $user->customerUser->role }}）
                                    @if($user->customerUser->medicalInstitution)
                                        <div class="text-[12px] text-slate-500">{{ $user->customerUser->medicalInstitution->name }}</div>
                                    @endif
                                @else
                                    {{ $user->type ?? '-' }}
                                @endif
                            </td>
                            <td class="px-5 py-3 text-slate-700">
                                {{ $user->locked_until?->timezone(config('app.timezone'))->format('Y-m-d H:i') }}
                                @php
                                    $remainingMinutes = max(0, (int) round(abs(now()->floatDiffInRealMinutes($user->locked_until))));
                                @endphp
                                <div class="text-[12px] text-amber-600">残り約 {{ $remainingMinutes }} 分</div>
                            </td>
                            <td class="px-5 py-3 text-right">
                                <form method="POST" action="{{ route('admin.account-locks.unlock', $user) }}"
                                      onsubmit="return confirm('このアカウントの一時ロックを解除しますか？');">
                                    @csrf
                                    <button type="submit"
                                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-emerald-600 text-white text-[13px] font-medium hover:bg-emerald-700">
                                        <i data-lucide="unlock" class="w-3.5 h-3.5"></i>
                                        ロック解除
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-5 py-10 text-center text-slate-500">
                                現在、一時ロック中のアカウントはありません。
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

            <x-table-pagination :paginator="$lockedUsers" />


    </div>
@endsection
