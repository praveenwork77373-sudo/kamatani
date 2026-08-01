@extends('layouts.app')

@section('title', '申請一覧 | ユニフォーム管理システム')
@section('page_title', 'ユニフォーム申請履歴')

@section('content')
    <div class="mb-6">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-slate-900 tracking-tight">ユニフォーム申請履歴</h1>
                <p class="text-[14px] text-slate-600 mt-1.5">最新順に表示しています。</p>
            </div>
            <a href="{{ route('applications.create') }}"
               class="inline-flex items-center gap-2 px-5 py-2.5 rounded-lg bg-primary-600 hover:bg-primary-700 text-white text-[14px] font-medium shadow-md transition-all">
                <i class="ph ph-plus text-base"></i>
                新規申請
            </a>
        </div>
    </div>

    <div class="bg-white border border-slate-200 rounded-xl overflow-hidden shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full text-[14px]">
                <thead class="bg-slate-50 border-b border-slate-200">
                    <tr>
                        <th class="px-5 py-3.5 text-left font-semibold text-slate-700">申請ID</th>
                        <th class="px-5 py-3.5 text-left font-semibold text-slate-700">職員</th>
                        <th class="px-5 py-3.5 text-left font-semibold text-slate-700">部署</th>
                        <th class="px-5 py-3.5 text-left font-semibold text-slate-700">種別</th>
                        <th class="px-5 py-3.5 text-left font-semibold text-slate-700">状態</th>
                        <th class="px-5 py-3.5 text-left font-semibold text-slate-700">申請日</th>
                        <th class="px-5 py-3.5 text-right font-semibold text-slate-700">操作</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($applications as $app)
                        <tr class="hover:bg-slate-50/50 transition-colors">
                            <td class="px-5 py-4 font-semibold text-slate-900">
                                #APP-{{ str_pad($app->id, 4, '0', STR_PAD_LEFT) }}
                            </td>
                            <td class="px-5 py-4 text-slate-800">
                                {{ $app->employee?->name ?? '-' }}
                                <span class="text-slate-500 text-[13px] ml-2">({{ $app->employee?->staff_no ?? '-' }})</span>
                            </td>
                            <td class="px-5 py-4 text-slate-600">{{ $app->department?->name ?? '-' }}</td>
                            <td class="px-5 py-4">
                                @php
                                    $typeLabels = [
                                        'new_loan' => ['label' => '新規貸与', 'class' => 'bg-primary-100 text-primary-700'],
                                        'resize' => ['label' => 'サイズ交換', 'class' => 'bg-slate-100 text-slate-700'],
                                        'return' => ['label' => '返却', 'class' => 'bg-slate-100 text-slate-700'],
                                        'exchange' => ['label' => '交換', 'class' => 'bg-slate-100 text-slate-700'],
                                        'transfer' => ['label' => '異動', 'class' => 'bg-slate-100 text-slate-700'],
                                    ];
                                    $type = $typeLabels[$app->type] ?? ['label' => $app->type, 'class' => 'bg-slate-100 text-slate-700'];
                                @endphp
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full {{ $type['class'] }} text-[13px] font-medium">
                                    {{ $type['label'] }}
                                </span>
                            </td>
                            <td class="px-5 py-4">
                                @php
                                    $statusConfig = [
                                        'pending' => ['label' => '申請', 'class' => 'bg-slate-100 text-slate-700'],
                                        'waiting_special_approval' => ['label' => '特別承認待ち', 'class' => 'bg-amber-100 text-amber-700'],
                                        'in_progress' => ['label' => '対応中', 'class' => 'bg-blue-100 text-blue-700'],
                                        'completed' => ['label' => '完了', 'class' => 'bg-emerald-100 text-emerald-700'],
                                        'approved' => ['label' => '対応中', 'class' => 'bg-blue-100 text-blue-700'],
                                        'rejected' => ['label' => '却下', 'class' => 'bg-red-100 text-red-700'],
                                        'cancel_request' => ['label' => 'キャンセル依頼中', 'class' => 'bg-orange-100 text-orange-700'],
                                        'cancel_approved' => ['label' => 'キャンセル承認', 'class' => 'bg-slate-100 text-slate-700'],
                                        'cancel_rejected' => ['label' => 'キャンセル却下', 'class' => 'bg-red-100 text-red-700'],
                                    ];
                                    $status = $statusConfig[$app->status] ?? ['label' => $app->status, 'class' => 'bg-slate-100 text-slate-700'];
                                @endphp
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full {{ $status['class'] }} text-[13px] font-medium">
                                    @if($app->status === 'pending')
                                        <span class="w-1.5 h-1.5 rounded-full bg-amber-400"></span>
                                    @endif
                                    {{ $status['label'] }}
                                </span>
                            </td>
                            <td class="px-5 py-4 text-slate-600">{{ $app->created_at->format('Y-m-d') }}</td>
                            <td class="px-5 py-4 text-right">
                                <a href="{{ route('applications.show', $app) }}"
                                   class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-lg border border-slate-300 text-slate-700 hover:bg-slate-50 hover:border-slate-400 text-[13px] font-medium transition-all">
                                    <i class="ph ph-eye text-sm"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-5 py-12 text-center">
                                <div class="flex flex-col items-center gap-3">
                                    <i class="ph ph-clipboard-text text-4xl text-slate-300"></i>
                                    <p class="text-slate-500 text-[14px]">申請はまだありません。</p>
                                    <a href="{{ route('applications.create') }}" class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-primary-600 hover:bg-primary-700 text-white text-[13px] font-medium shadow-md transition-all">
                                        <i class="ph ph-plus text-sm"></i>
                                        新規申請を作成する
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if($applications->hasPages())
        <div class="mt-6 flex items-center justify-between text-[13px] text-slate-600">
            <span>全 {{ $applications->total() }} 件中 {{ $applications->firstItem() ?? 0 }} - {{ $applications->lastItem() ?? 0 }} 件を表示</span>
            <div class="flex items-center gap-2">
                {{ $applications->onEachSide(1)->links('pagination::tailwind') }}
            </div>
        </div>
    @endif
@endsection
