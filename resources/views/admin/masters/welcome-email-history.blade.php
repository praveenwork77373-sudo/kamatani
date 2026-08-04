@extends('layouts.app')

@section('title', '利用開始メール送信履歴 | ' . config('app.name'))
@section('page_title', __('pages.welcome_email_history_title'))

@section('content')
    <div class="ui-page-header">
        <div>
            <h1 class="text-xl sm:text-2xl font-bold text-slate-900 tracking-tight">{{ __('pages.welcome_email_history_title') }}</h1>
            <p class="text-[13px] sm:text-[14px] text-slate-600 mt-1.5">{{ $institution->name }}（{{ $institution->code }}）</p>
        </div>
        <a href="{{ route('admin.masters.institutions') }}" class="ui-btn ui-btn-secondary">{{ __('pages.back_to_list') }}</a>
    </div>

    <div class="ui-card overflow-hidden">
        <div class="ui-table-wrap">
            <table class="min-w-full text-[13px] sm:text-[14px]">
                <thead class="bg-slate-50 border-b border-slate-200">
                    <tr>
                        <th class="px-4 sm:px-5 py-3.5 text-left font-semibold text-slate-700 whitespace-nowrap">{{ __('pages.sent_at') }}</th>
                        <th class="px-4 sm:px-5 py-3.5 text-left font-semibold text-slate-700 whitespace-nowrap">{{ __('pages.ui_7065744986') }}</th>
                        <th class="px-4 sm:px-5 py-3.5 text-left font-semibold text-slate-700 whitespace-nowrap">{{ __('pages.sender') }}</th>
                        <th class="px-4 sm:px-5 py-3.5 text-left font-semibold text-slate-700 whitespace-nowrap">{{ __('pages.status') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($histories as $history)
                        <tr>
                            <td class="px-4 sm:px-5 py-4 whitespace-nowrap">{{ optional($history->sent_at)->format('Y-m-d H:i') ?? '-' }}</td>
                            <td class="px-4 sm:px-5 py-4">{{ $history->destination_email }}</td>
                            <td class="px-4 sm:px-5 py-4">{{ $history->sentBy?->name ?? '-' }}</td>
                            <td class="px-4 sm:px-5 py-4">
                                @if($history->status === 'sent')
                                    <span class="px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-700 text-[12px]">{{ __('pages.issued') }}</span>
                                @else
                                    <span class="px-2 py-0.5 rounded-full bg-red-100 text-red-700 text-[12px]" title="{{ $history->error_message }}">{{ __('pages.backup_status_failed') }}</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-5 py-10 text-center text-slate-500">{{ __('pages.ui_6ec71e929e') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

            <x-table-pagination :paginator="$histories" />

    </div>

    
@endsection
