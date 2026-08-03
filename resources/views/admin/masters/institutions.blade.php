@extends('layouts.app')

@section('title', __('pages.medical_institutions_management') . ' | ' . config('app.name'))
@section('page_title', __('pages.medical_institutions_management'))

@section('content')
    <div class="mb-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-slate-900 tracking-tight">{{ __('pages.medical_institutions_management') }}</h1>
                <p class="text-[14px] text-slate-600 mt-1.5">{{ __('pages.medical_institutions_management_description') }}</p>
            </div>
            <a href="{{ route('admin.masters.institutions.create') }}" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-lg bg-primary-600 hover:bg-primary-700 text-white text-[14px] font-medium shadow-md transition-all">
                <i data-lucide="plus" class="w-4 h-4"></i>
                {{ __('pages.new_registration') }}
            </a>
        </div>
    </div>

    @if(session('status'))
        <div class="mb-6 p-4 bg-emerald-50 border-l-4 border-emerald-500 rounded-lg">
            <div class="flex items-center gap-2">
                <i class="ph ph-check-circle text-lg text-emerald-600"></i>
                <span class="text-[14px] text-emerald-800 font-medium">{{ session('status') }}</span>
            </div>
        </div>
    @endif

    <div class="bg-white border border-slate-200 rounded-xl shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full text-[14px]">
                <thead class="bg-slate-50 border-b border-slate-200">
                    <tr>
                        <th class="px-5 py-3.5 text-left font-semibold text-slate-700">{{ __('pages.institution_code') }}</th>
                        <th class="px-5 py-3.5 text-left font-semibold text-slate-700">{{ __('pages.institution_name') }}</th>
                        <th class="px-5 py-3.5 text-left font-semibold text-slate-700">締め日</th>
                        <th class="px-5 py-3.5 text-left font-semibold text-slate-700">{{ __('pages.notification_email') }}</th>
                        <th class="px-5 py-3.5 text-left font-semibold text-slate-700">{{ __('pages.overall_manager') }}</th>
                        <th class="px-5 py-3.5 text-right font-semibold text-slate-700">{{ __('pages.actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($institutions as $institution)
                        <tr class="hover:bg-slate-50/50 transition-colors">
                            <td class="px-5 py-4 font-semibold text-slate-900">{{ $institution->code }}</td>
                            <td class="px-5 py-4 text-slate-800">{{ $institution->name }}</td>
                            <td class="px-5 py-4 text-slate-600">{{ $institution->billing_closing_day ?? 1 }}日</td>
                            <td class="px-5 py-4 text-slate-600">
                                <div class="flex items-center gap-2">
                                    <i data-lucide="mail" class="w-4 h-4 text-slate-400"></i>
                                    <span>{{ $institution->notification_email }}</span>
                                </div>
                            </td>
                            <td class="px-5 py-4 text-slate-600">
                                @if($institution->institutionAdmin && $institution->institutionAdmin->user)
                                    <div class="flex flex-col gap-1">
                                        <div class="flex items-center gap-2">
                                            <i data-lucide="user" class="w-4 h-4 text-slate-400"></i>
                                            <span class="text-[13px] font-medium text-slate-900">{{ $institution->institutionAdmin->user->name }}</span>
                                        </div>
                                        <div class="flex items-center gap-2 ml-6">
                                            <i data-lucide="mail" class="w-3 h-3 text-slate-400"></i>
                                            <span class="text-[12px] text-slate-500">{{ $institution->institutionAdmin->user->email }}</span>
                                        </div>
                                    </div>
                                @else
                                    <span class="text-[13px] text-slate-400 italic">{{ __('pages.no_manager_registered') }}</span>
                                @endif
                            </td>
                            <td class="px-5 py-4 text-right">
                                <div class="flex items-center justify-end gap-2 flex-wrap">
                                    @if($institution->institutionAdmin && $institution->institutionAdmin->user)
                                        <form method="POST" action="{{ route('admin.masters.institutions.welcome_email', $institution) }}" class="inline" onsubmit="return confirm('利用開始メールを送信しますか？仮パスワードが再発行されます。');">
                                            @csrf
                                            <button type="submit" class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-lg border border-emerald-300 text-emerald-700 hover:bg-emerald-50 text-[13px] font-medium transition-all">
                                                <i data-lucide="send" class="w-4 h-4"></i>
                                                利用開始メール
                                            </button>
                                        </form>
                                        <a href="{{ route('admin.masters.institutions.welcome_email_history', $institution) }}"
                                           class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-lg border border-slate-300 text-slate-700 hover:bg-slate-50 text-[13px] font-medium transition-all">
                                            <i data-lucide="history" class="w-4 h-4"></i>
                                            送信履歴
                                        </a>
                                    @endif
                                    <a href="{{ route('admin.masters.institutions.edit', $institution) }}"
                                       class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-lg border border-slate-300 text-slate-700 hover:bg-slate-50 hover:border-slate-400 text-[13px] font-medium transition-all">
                                        <i data-lucide="edit" class="w-4 h-4"></i>
                                        {{ __('common.edit') }}
                                    </a>
                                    <form method="POST" action="{{ route('admin.masters.institutions.destroy', $institution) }}" class="inline" onsubmit="return confirm('{{ __('pages.confirm_delete_institution') }}');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-lg border border-red-300 text-red-700 hover:bg-red-50 hover:border-red-400 text-[13px] font-medium transition-all">
                                            <i data-lucide="trash-2" class="w-4 h-4"></i>
                                            {{ __('common.delete') }}
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-12 text-center">
                                <div class="flex flex-col items-center gap-3">
                                    <i class="ph ph-buildings text-4xl text-slate-300"></i>
                                    <p class="text-slate-500 text-[14px]">{{ __('pages.no_institutions_registered') }}</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

            <x-table-pagination :paginator="$institutions" />



    </div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
        setTimeout(() => { if (typeof lucide !== 'undefined') lucide.createIcons(); }, 100);
        setTimeout(() => { if (typeof lucide !== 'undefined') lucide.createIcons(); }, 500);
    }
});
</script>
@endpush
@endsection



