@extends('layouts.app')

@section('title', __('pages.account_locks_page_title') . ' | ' . __('auth.system_title'))
@section('page_title', __('menu.account_locks'))

@section('content')
    <div class="mb-6 flex items-center justify-between gap-4 flex-wrap">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 tracking-tight">{{ __('menu.account_locks') }}</h1>
            <p class="text-[14px] text-slate-600 mt-1.5">{{ __('pages.account_locks_desc') }}</p>
        </div>
    </div>

    <x-filter-bar :action="route('admin.account-locks.index')" form-id="account-locks-filter-form" :active="filled($searchQuery)">
        <div class="ui-filter-field ui-filter-field--grow">
            <label class="ui-filter-field__label">{{ __('pages.search_name_email') }}</label>
            <input type="text" name="q" value="{{ $searchQuery }}"
                   class="ui-input"
                   placeholder="{{ __('pages.example_email_placeholder') }}">
        </div>
        <x-slot:actions>
            <button type="submit" class="ui-btn ui-btn-primary">{{ __('common.search') }}</button>
            @if(filled($searchQuery))
                <a href="{{ route('admin.account-locks.index') }}" class="ui-btn ui-btn-secondary" title="{{ __('common.reset') }}">
                    <i data-lucide="x" class="w-4 h-4"></i>
                </a>
            @endif
        </x-slot:actions>
    </x-filter-bar>

    <div class="bg-white border border-slate-200 rounded-xl shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full text-[14px]">
                <thead class="bg-slate-50 border-b border-slate-200">
                    <tr>
                        <th class="px-5 py-3 text-left font-semibold text-slate-700">{{ __('pages.user_label') }}</th>
                        <th class="px-5 py-3 text-left font-semibold text-slate-700">{{ __('pages.type') }}</th>
                        <th class="px-5 py-3 text-left font-semibold text-slate-700">{{ __('pages.unlock_scheduled') }}</th>
                        <th class="px-5 py-3 text-right font-semibold text-slate-700">{{ __('common.actions') }}</th>
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
                                    {{ __('pages.admin_user_label') }}（{{ $user->adminUser->role }}）
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
                                <div class="text-[12px] text-amber-600">{{ __('pages.remaining_minutes', ['minutes' => $remainingMinutes]) }}</div>
                            </td>
                            <td class="px-5 py-3 text-right">
                                <form method="POST" action="{{ route('admin.account-locks.unlock', $user) }}"
                                      onsubmit="return confirm(@js(__('pages.confirm_unlock_account')));">
                                    @csrf
                                    <button type="submit"
                                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-emerald-600 text-white text-[13px] font-medium hover:bg-emerald-700">
                                        <i data-lucide="unlock" class="w-3.5 h-3.5"></i>
                                        {{ __('pages.unlock') }}
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-5 py-10 text-center text-slate-500">
                                {{ __('pages.no_locked_accounts') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

            <x-table-pagination :paginator="$lockedUsers" />

    </div>
@endsection
