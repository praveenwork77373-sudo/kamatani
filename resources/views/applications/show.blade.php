@extends('layouts.app')

@section('title', __('pages.application_detail') . ' | ' . __('auth.system_title'))
@section('page_title', __('pages.application_detail'))

@section('content')
    <div class="mb-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-slate-900 tracking-tight">申請詳細</h1>
                <p class="text-[14px] text-slate-600 mt-1.5">申請内容と明細を確認できます。</p>
            </div>
            <a href="{{ route('applications.index') }}" class="inline-flex items-center gap-2 px-4 py-2 rounded-lg border border-slate-300 text-slate-700 hover:bg-slate-50 text-[14px] font-medium transition-all">
                <i class="ph ph-arrow-left text-base"></i>
                一覧へ戻る
            </a>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-6">
            {{-- 基本情報 --}}
            <div class="bg-white border border-slate-200 rounded-xl shadow-sm p-6">
                <h2 class="text-lg font-semibold text-slate-900 mb-4">基本情報</h2>
                <dl class="grid grid-cols-1 md:grid-cols-2 gap-4 text-[14px]">
                    <div>
                        <dt class="text-slate-500 mb-1">申請ID</dt>
                        <dd class="font-semibold text-slate-900">#APP-{{ str_pad($application->id, 4, '0', STR_PAD_LEFT) }}</dd>
                    </div>
                    <div>
                        <dt class="text-slate-500 mb-1">{{ __('pages.application_type_label') }}</dt>
                        <dd>
                            @php
                                $typeLabels = [
                                    'new_loan' => __('pages.new_loan_type'),
                                    'resize' => __('pages.resize_type'),
                                    'return' => __('pages.return_type'),
                                    'exchange' => __('pages.exchange_type'),
                                    'transfer' => __('pages.transfer_type'),
                                ];
                            @endphp
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-primary-100 text-primary-700 text-[13px] font-medium">
                                {{ $typeLabels[$application->type] ?? $application->type }}
                            </span>
                        </dd>
                    </div>
                    <div>
                        <dt class="text-slate-500 mb-1">{{ __('pages.status_label') }}</dt>
                        <dd>
                            @php
                                $statusConfig = [
                                    'pending' => ['label' => '申請', 'class' => 'bg-slate-100 text-slate-700'],
                                    'waiting_special_approval' => ['label' => __('pages.waiting_special_approval'), 'class' => 'bg-amber-100 text-amber-700'],
                                    'in_progress' => ['label' => '対応中', 'class' => 'bg-blue-100 text-blue-700'],
                                    'completed' => ['label' => '完了', 'class' => 'bg-emerald-100 text-emerald-700'],
                                    'approved' => ['label' => '対応中', 'class' => 'bg-blue-100 text-blue-700'],
                                    'rejected' => ['label' => __('pages.rejected_status'), 'class' => 'bg-red-100 text-red-700'],
                                    'cancel_request' => ['label' => 'キャンセル依頼中', 'class' => 'bg-orange-100 text-orange-700'],
                                    'cancel_approved' => ['label' => 'キャンセル承認', 'class' => 'bg-slate-100 text-slate-700'],
                                    'cancel_rejected' => ['label' => 'キャンセル却下', 'class' => 'bg-red-100 text-red-700'],
                                ];
                                $status = $statusConfig[$application->status] ?? ['label' => $application->status, 'class' => 'bg-slate-100 text-slate-700'];
                            @endphp
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full {{ $status['class'] }} text-[13px] font-medium">
                                {{ $status['label'] }}
                            </span>
                        </dd>
                    </div>
                    <div>
                        <dt class="text-slate-500 mb-1">{{ __('pages.application_date') }}</dt>
                        <dd class="text-slate-900">{{ $application->created_at->format('Y-m-d H:i') }}</dd>
                    </div>
                    <div>
                        <dt class="text-slate-500 mb-1">{{ __('pages.employee') }}</dt>
                        <dd class="text-slate-900">
                            {{ $application->employee?->name ?? '-' }}
                            <span class="text-slate-500 text-[13px] ml-2">({{ $application->employee?->staff_no ?? '-' }})</span>
                        </dd>
                    </div>
                    {{-- legacy handoff field; new 異動 apps do not set transfer_to --}}
                    @if($application->type === 'transfer' && $application->transfer_to_employee_id)
                    <div>
                        <dt class="text-slate-500 mb-1">移管先職員（旧データ）</dt>
                        <dd class="text-slate-900">
                            {{ $application->transferToEmployee?->name ?? '-' }}
                            <span class="text-slate-500 text-[13px] ml-2">({{ $application->transferToEmployee?->staff_no ?? '-' }})</span>
                        </dd>
                    </div>
                    @endif
                    <div>
                        <dt class="text-slate-500 mb-1">{{ __('pages.department') }}</dt>
                        <dd class="text-slate-900">{{ $application->department?->name ?? '-' }}</dd>
                    </div>
                    <div>
                        <dt class="text-slate-500 mb-1">{{ __('pages.start_date') }}</dt>
                        <dd class="text-slate-900">{{ $application->start_date?->format('Y-m-d') ?? '-' }}</dd>
                    </div>
                    <div>
                        <dt class="text-slate-500 mb-1">{{ __('pages.delivery_location') }}</dt>
                        <dd class="text-slate-900">{{ $application->deliveryLocation?->name ?? '-' }}</dd>
                    </div>
                </dl>
            </div>

            {{-- 申請アイテム --}}
            <div class="bg-white border border-slate-200 rounded-xl shadow-sm p-6">
                <h2 class="text-lg font-semibold text-slate-900 mb-4">申請アイテム</h2>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-[14px]">
                        <thead class="bg-slate-50 border-b border-slate-200">
                            <tr>
                                <th class="px-4 py-3 text-left font-semibold text-slate-700">商品コード</th>
                                <th class="px-4 py-3 text-left font-semibold text-slate-700">商品名</th>
                                <th class="px-4 py-3 text-left font-semibold text-slate-700">サイズ</th>
                                <th class="px-4 py-3 text-left font-semibold text-slate-700">区分</th>
                                <th class="px-4 py-3 text-right font-semibold text-slate-700">数量</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach($application->items as $item)
                                <tr>
                                    <td class="px-4 py-3 text-slate-900">{{ $item->uniformProduct?->product_code ?? '-' }}</td>
                                    <td class="px-4 py-3 text-slate-900">{{ $item->uniformProduct?->name ?? '-' }}</td>
                                    <td class="px-4 py-3 text-slate-600">{{ $item->uniformProduct?->size ?? '-' }}</td>
                                    <td class="px-4 py-3 text-slate-600 text-[13px]">
                                        @if($item->action === 'exchange_out')
                                            旧品（返却）
                                        @elseif($item->action === 'return')
                                            返却
                                        @else
                                            新品（貸与）
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-right font-semibold text-slate-900">{{ $item->quantity }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            @if($application->special_approval_reason)
                <div class="bg-amber-50 border-l-4 border-amber-500 rounded px-4 py-3">
                    <h3 class="text-[14px] font-semibold text-amber-800 mb-1">特別承認理由</h3>
                    <p class="text-[13px] text-amber-700">{{ $application->special_approval_reason }}</p>
                </div>
            @endif
        </div>

        {{-- アクションサイドバー --}}
        <div class="lg:col-span-1">
            <div class="bg-white border border-slate-200 rounded-xl shadow-sm p-6 sticky top-6">
                <h2 class="text-lg font-semibold text-slate-900 mb-4">操作</h2>
                <div class="space-y-3">
                    {{-- Cancel request — allowed until completed --}}
                    @if($application->isCancellableByHospital())
                        <form method="POST" action="{{ route('applications.request_cancel', $application) }}" class="inline-block w-full">
                            @csrf
                            <button type="submit" 
                                    onclick="return confirm('キャンセル申請を送信しますか？')"
                                    class="w-full px-4 py-2.5 rounded-lg bg-orange-600 hover:bg-orange-700 text-white text-[14px] font-medium shadow-md transition-all flex items-center justify-center gap-2">
                                <i class="ph ph-x-circle text-base"></i>
                                キャンセル申請
                            </button>
                        </form>
                    @endif
                    
                    {{-- Cancel request status messages --}}
                    @if($application->status === 'cancel_request')
                        <div class="p-3 bg-amber-50 border border-amber-200 rounded-lg text-[13px] text-amber-800 flex items-center gap-2">
                            <i class="ph ph-clock text-base"></i>
                            <span>{{ __('pages.cancel_request_pending_message') ?? 'キャンセル申請中です。承認をお待ちください。' }}</span>
                        </div>
                    @endif
                    
                    @if($application->status === 'cancel_approved')
                        <div class="p-3 bg-slate-50 border border-slate-200 rounded-lg text-[13px] text-slate-800 flex items-center gap-2">
                            <i class="ph ph-check-circle text-base"></i>
                            <span>{{ __('pages.cancel_approved_message') ?? 'キャンセルが承認されました。' }}</span>
                        </div>
                    @endif
                    
                    @if($application->status === 'cancel_rejected')
                        <div class="p-3 bg-red-50 border border-red-200 rounded-lg text-[13px] text-red-800 flex items-center gap-2">
                            <i class="ph ph-x-circle text-base"></i>
                            <span>{{ __('pages.cancel_rejected_message') ?? 'キャンセル申請が却下されました。' }}</span>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
