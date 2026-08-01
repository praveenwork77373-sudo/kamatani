@extends('layouts.app')

@section('title', '管理: 申請詳細 | ユニフォーム管理システム')
@section('page_title', '申請詳細')

@section('content')
    <div class="mb-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-slate-900 tracking-tight">申請詳細</h1>
                <p class="text-[14px] text-slate-600 mt-1.5">申請内容を確認し、承認・却下・キャンセル承認ができます。</p>
            </div>
            <a href="{{ route('admin.applications.index') }}" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-lg border border-slate-300 text-slate-700 hover:bg-slate-50 text-[14px] font-medium transition-all">
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
                        <dt class="text-slate-500 mb-1.5 text-[13px]">申請ID</dt>
                        <dd class="font-semibold text-slate-900">#APP-{{ str_pad($application->id, 4, '0', STR_PAD_LEFT) }}</dd>
                    </div>
                    <div>
                        <dt class="text-slate-500 mb-1.5 text-[13px]">医療機関</dt>
                        <dd class="text-slate-900">{{ $application->medicalInstitution?->name ?? '-' }}</dd>
                    </div>
                    <div>
                        <dt class="text-slate-500 mb-1.5 text-[13px]">{{ __('pages.employee') }}</dt>
                        <dd class="text-slate-900">
                            {{ $application->employee?->name ?? '-' }}
                            <span class="text-slate-500 text-[13px] ml-2">({{ $application->employee?->staff_no ?? '-' }})</span>
                        </dd>
                    </div>
                    <div>
                        <dt class="text-slate-500 mb-1.5 text-[13px]">{{ __('pages.department') }}</dt>
                        <dd class="text-slate-900">{{ $application->department?->name ?? '-' }}</dd>
                    </div>
                    <div>
                        <dt class="text-slate-500 mb-1.5 text-[13px]">{{ __('pages.type') }}</dt>
                        <dd>
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-primary-100 text-primary-700 text-[13px] font-medium">
                                @if($application->type === 'new_loan'){{ __('pages.new_loan') }}
                                @elseif($application->type === 'resize'){{ __('pages.size_exchange') }}
                                @elseif($application->type === 'return'){{ __('pages.return') }}
                                @elseif($application->type === 'exchange'){{ __('pages.exchange') }}
                                @elseif($application->type === 'transfer'){{ __('pages.transfer') }}
                                @else{{ $application->type }}@endif
                            </span>
                        </dd>
                    </div>
                    <div>
                        <dt class="text-slate-500 mb-1.5 text-[13px]">{{ __('pages.status') }}</dt>
                        <dd>
                            @php
                                $statusConfig = [
                                    'pending' => ['label' => '申請', 'class' => 'bg-slate-100 text-slate-700'],
                                    'waiting_special_approval' => ['label' => __('pages.status_waiting_special_approval'), 'class' => 'bg-amber-100 text-amber-700'],
                                    'in_progress' => ['label' => '対応中', 'class' => 'bg-blue-100 text-blue-700'],
                                    'completed' => ['label' => '完了', 'class' => 'bg-emerald-100 text-emerald-700'],
                                    'approved' => ['label' => '対応中', 'class' => 'bg-blue-100 text-blue-700'],
                                    'rejected' => ['label' => __('pages.status_rejected'), 'class' => 'bg-red-100 text-red-700'],
                                    'cancel_request' => ['label' => __('pages.status_cancel_request'), 'class' => 'bg-orange-100 text-orange-700'],
                                    'cancel_approved' => ['label' => __('pages.status_cancel_approved'), 'class' => 'bg-slate-100 text-slate-700'],
                                    'cancel_rejected' => ['label' => __('pages.status_cancel_rejected'), 'class' => 'bg-red-100 text-red-700'],
                                ];
                                $status = $statusConfig[$application->status] ?? ['label' => $application->status, 'class' => 'bg-slate-100 text-slate-700'];
                            @endphp
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full {{ $status['class'] }} text-[13px] font-medium">
                                {{ $status['label'] }}
                            </span>
                        </dd>
                    </div>
                    <div>
                        <dt class="text-slate-500 mb-1.5 text-[13px]">{{ __('pages.application_date') }}</dt>
                        <dd class="text-slate-900">{{ $application->created_at->format('Y-m-d H:i') }}</dd>
                    </div>
                    <div>
                        <dt class="text-slate-500 mb-1.5 text-[13px]">{{ __('pages.start_date') }}</dt>
                        <dd class="text-slate-900">{{ $application->start_date?->format('Y-m-d') ?? '-' }}</dd>
                    </div>
                    <div>
                        <dt class="text-slate-500 mb-1.5 text-[13px]">{{ __('pages.delivery_location') }}</dt>
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
                                <th class="px-4 py-3 text-right font-semibold text-slate-700">数量</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach($application->items as $item)
                                <tr class="hover:bg-slate-50/50">
                                    <td class="px-4 py-3 text-slate-900">{{ $item->uniformProduct?->product_code ?? '-' }}</td>
                                    <td class="px-4 py-3 text-slate-900">{{ $item->uniformProduct?->name ?? '-' }}</td>
                                    <td class="px-4 py-3 text-slate-600">{{ $item->uniformProduct?->size ?? '-' }}</td>
                                    <td class="px-4 py-3 text-right font-semibold text-slate-900">{{ $item->quantity }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            @if($application->special_approval_reason)
                <div class="bg-amber-50 border-l-4 border-amber-500 rounded-lg px-4 py-3">
                    <h3 class="text-[14px] font-semibold text-amber-800 mb-1">特別承認理由</h3>
                    <p class="text-[13px] text-amber-700">{{ $application->special_approval_reason }}</p>
                </div>
            @endif

            {{-- メモ（帳票印字） --}}
            <div class="bg-white border border-slate-200 rounded-xl shadow-sm p-6">
                <h2 class="text-lg font-semibold text-slate-900 mb-4">メモ（帳票）</h2>
                <form method="POST" action="{{ route('admin.applications.update_memos', $application) }}" class="space-y-4">
                    @csrf
                    <div>
                        <label class="block text-[13px] font-semibold text-slate-700 mb-1.5">メモ1</label>
                        <input type="text" name="memo1" value="{{ old('memo1', $application->memo1) }}" maxlength="500"
                               class="w-full px-3 py-2 rounded-lg border border-slate-300 text-[14px]">
                    </div>
                    <div>
                        <label class="block text-[13px] font-semibold text-slate-700 mb-1.5">メモ2</label>
                        <input type="text" name="memo2" value="{{ old('memo2', $application->memo2) }}" maxlength="500"
                               class="w-full px-3 py-2 rounded-lg border border-slate-300 text-[14px]">
                    </div>
                    <button type="submit" class="px-4 py-2 rounded-lg bg-slate-800 text-white text-[13px] font-medium hover:bg-slate-900">
                        メモを保存
                    </button>
                </form>
            </div>

            {{-- 帳票PDF --}}
            @if(in_array($application->status, ['in_progress', 'completed'], true))
                <div class="bg-white border border-slate-200 rounded-xl shadow-sm p-6">
                    <div class="flex items-center justify-between mb-4 gap-3 flex-wrap">
                        <h2 class="text-lg font-semibold text-slate-900">帳票PDF</h2>
                        <form method="POST" action="{{ route('admin.applications.documents.issue', $application) }}">
                            @csrf
                            <button type="submit" class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-lg border border-primary-300 text-primary-700 hover:bg-primary-50 text-[13px] font-medium">
                                <i class="ph ph-file-pdf text-base"></i>
                                {{ ($latestDocuments ?? collect())->isEmpty() ? '5種を発行' : '5種を再発行' }}
                            </button>
                        </form>
                    </div>
                    <div class="space-y-2">
                        @php
                            $types = [
                                'order_slip' => '受注伝票',
                                'delivery_notice' => '納入予定ご連絡書',
                                'shipping_sales' => '出荷指示書（営業所用）',
                                'shipping_warehouse' => '出荷指示書（倉庫用）',
                                'work_slip' => '作業用伝票',
                            ];
                            $latestDocuments = $latestDocuments ?? collect();
                        @endphp
                        @foreach($types as $type => $label)
                            @php $doc = $latestDocuments->get($type); @endphp
                            <div class="flex items-center justify-between gap-3 py-2.5 px-3 rounded-lg bg-slate-50 border border-slate-100">
                                <div>
                                    <p class="text-[13px] font-medium text-slate-900">{{ $label }}</p>
                                    @if($doc)
                                        <p class="text-[11px] text-slate-500">v{{ $doc->version }} · {{ optional($doc->issued_at)->format('Y-m-d H:i') }}</p>
                                    @else
                                        <p class="text-[11px] text-slate-400">未発行</p>
                                    @endif
                                </div>
                                <div class="flex items-center gap-2">
                                    @if($doc)
                                        <a href="{{ route('admin.application-documents.preview', $doc) }}" target="_blank"
                                           class="px-2.5 py-1 rounded border border-slate-300 text-[12px] text-slate-700 hover:bg-white">表示</a>
                                        <a href="{{ route('admin.application-documents.download', $doc) }}"
                                           class="px-2.5 py-1 rounded border border-slate-300 text-[12px] text-slate-700 hover:bg-white">DL</a>
                                    @endif
                                    <form method="POST" action="{{ route('admin.applications.documents.reissue', [$application, $type]) }}">
                                        @csrf
                                        <button type="submit" class="px-2.5 py-1 rounded border border-emerald-300 text-[12px] text-emerald-700 hover:bg-emerald-50">
                                            {{ $doc ? '再発行' : '発行' }}
                                        </button>
                                    </form>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    @if(($documentHistory ?? collect())->where('is_latest', false)->isNotEmpty())
                        <details class="mt-4">
                            <summary class="text-[13px] text-slate-600 cursor-pointer">過去バージョン履歴</summary>
                            <ul class="mt-2 space-y-1 text-[12px] text-slate-600">
                                @foreach(($documentHistory ?? collect())->where('is_latest', false)->sortByDesc('issued_at') as $old)
                                    <li class="flex justify-between gap-2">
                                        <span>{{ $old->label() }} v{{ $old->version }} · {{ optional($old->issued_at)->format('Y-m-d H:i') }}</span>
                                        <a href="{{ route('admin.application-documents.download', $old) }}" class="text-primary-600 hover:underline">DL</a>
                                    </li>
                                @endforeach
                            </ul>
                        </details>
                    @endif
                </div>
            @endif
        </div>

        {{-- アクションサイドバー --}}
        <div class="lg:col-span-1">
            <div class="bg-white border border-slate-200 rounded-xl shadow-sm p-6 sticky top-6">
                <h2 class="text-lg font-semibold text-slate-900 mb-4">操作</h2>
                <div class="space-y-3">
                    @if(in_array($application->status, ['pending', 'waiting_special_approval']))
                        <form method="POST" action="{{ route('admin.applications.approve', $application) }}" class="inline-block w-full">
                            @csrf
                            <button type="submit" class="w-full px-4 py-2.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-[14px] font-semibold shadow-md transition-all flex items-center justify-center gap-2">
                                <i class="ph ph-check-circle text-base"></i>
                                受付する（対応中）
                            </button>
                        </form>
                        <button type="button" onclick="openRejectModal()" class="w-full px-4 py-2.5 rounded-lg bg-red-600 hover:bg-red-700 text-white text-[14px] font-semibold shadow-md transition-all flex items-center justify-center gap-2">
                            <i class="ph ph-x-circle text-base"></i>
                            却下する
                        </button>
                    @endif

                    @if($application->status === 'in_progress')
                        <form method="POST" action="{{ route('admin.applications.complete', $application) }}" class="inline-block w-full" onsubmit="return confirm('出荷完了として申請を完了にしますか？');">
                            @csrf
                            <button type="submit" class="w-full px-4 py-2.5 rounded-lg bg-blue-600 hover:bg-blue-700 text-white text-[14px] font-semibold shadow-md transition-all flex items-center justify-center gap-2">
                                <i class="ph ph-truck text-base"></i>
                                出荷登録（完了）
                            </button>
                        </form>
                    @endif

                    @if($application->status === 'cancel_request')
                        <form method="POST" action="{{ route('admin.applications.cancel_approve', $application) }}" class="inline-block w-full">
                            @csrf
                            <button type="submit" class="w-full px-4 py-2.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-[14px] font-semibold shadow-md transition-all flex items-center justify-center gap-2">
                                <i class="ph ph-check-circle text-base"></i>
                                キャンセル承認
                            </button>
                        </form>
                        <button type="button" onclick="openRejectCancelModal()" class="w-full px-4 py-2.5 rounded-lg bg-red-600 hover:bg-red-700 text-white text-[14px] font-semibold shadow-md transition-all flex items-center justify-center gap-2">
                            <i class="ph ph-x-circle text-base"></i>
                            キャンセル却下
                        </button>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- 却下モーダル --}}
    <div id="reject-modal" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-xl shadow-xl max-w-md w-full p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-slate-900">申請を却下</h3>
                <button type="button" onclick="closeRejectModal()" class="text-slate-400 hover:text-slate-600">
                    <i class="ph ph-x text-xl"></i>
                </button>
            </div>
            <form method="POST" action="{{ route('admin.applications.reject', $application) }}">
                @csrf
                <div class="mb-4">
                    <label class="block text-slate-700 text-[14px] font-semibold mb-2">
                        却下理由 <span class="text-red-500">*</span>
                    </label>
                    <textarea name="rejection_reason" rows="4" required
                              class="w-full px-4 py-2.5 rounded-lg border border-slate-300 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 text-[14px] bg-white shadow-sm transition-all resize-none"
                              placeholder="却下理由を入力してください"></textarea>
                </div>
                <div class="flex items-center justify-end gap-3">
                    <button type="button" onclick="closeRejectModal()" class="px-4 py-2.5 rounded-lg border border-slate-300 text-slate-700 hover:bg-slate-50 text-[14px] font-medium transition-all">
                        キャンセル
                    </button>
                    <button type="submit" class="px-4 py-2.5 rounded-lg bg-red-600 hover:bg-red-700 text-white text-[14px] font-semibold transition-all">
                        却下する
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- キャンセル却下モーダル --}}
    <div id="reject-cancel-modal" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-xl shadow-xl max-w-md w-full p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-slate-900">キャンセルを却下</h3>
                <button type="button" onclick="closeRejectCancelModal()" class="text-slate-400 hover:text-slate-600">
                    <i class="ph ph-x text-xl"></i>
                </button>
            </div>
            <form method="POST" action="{{ route('admin.applications.cancel_reject', $application) }}">
                @csrf
                <div class="mb-4">
                    <label class="block text-slate-700 text-[14px] font-semibold mb-2">
                        却下理由 <span class="text-red-500">*</span>
                    </label>
                    <textarea name="rejection_reason" rows="4" required
                              class="w-full px-4 py-2.5 rounded-lg border border-slate-300 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 text-[14px] bg-white shadow-sm transition-all resize-none"
                              placeholder="却下理由を入力してください"></textarea>
                </div>
                <div class="flex items-center justify-end gap-3">
                    <button type="button" onclick="closeRejectCancelModal()" class="px-4 py-2.5 rounded-lg border border-slate-300 text-slate-700 hover:bg-slate-50 text-[14px] font-medium transition-all">
                        キャンセル
                    </button>
                    <button type="submit" class="px-4 py-2.5 rounded-lg bg-red-600 hover:bg-red-700 text-white text-[14px] font-semibold transition-all">
                        却下する
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
<script>
function openRejectModal() {
    document.getElementById('reject-modal').classList.remove('hidden');
}

function closeRejectModal() {
    document.getElementById('reject-modal').classList.add('hidden');
}

function openRejectCancelModal() {
    document.getElementById('reject-cancel-modal').classList.remove('hidden');
}

function closeRejectCancelModal() {
    document.getElementById('reject-cancel-modal').classList.add('hidden');
}

// モーダル外クリックで閉じる
document.getElementById('reject-modal')?.addEventListener('click', (e) => {
    if (e.target.id === 'reject-modal') closeRejectModal();
});
document.getElementById('reject-cancel-modal')?.addEventListener('click', (e) => {
    if (e.target.id === 'reject-cancel-modal') closeRejectCancelModal();
});
</script>
@endpush
