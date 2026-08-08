@extends('layouts.app')

@section('title', __('pages.ui_f33add7e53'))
@section('page_title', __('pages.application_detail'))

@section('content')
    <div class="mb-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-slate-900 tracking-tight">{{ __('pages.application_detail') }}</h1>
                <p class="text-[14px] text-slate-600 mt-1.5">{{ __('pages.ui_2551238c99') }}</p>
            </div>
            <a href="{{ route('admin.applications.index') }}" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-lg border border-slate-300 text-slate-700 hover:bg-slate-50 text-[14px] font-medium transition-all">
                <i class="ph ph-arrow-left text-base"></i>
                {{ __('pages.back_to_list') }}
            </a>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-6">
            {{-- 基本情報 --}}
            <div class="bg-white border border-slate-200 rounded-xl shadow-sm p-6">
                <h2 class="text-lg font-semibold text-slate-900 mb-4">{{ __('pages.basic_info') }}</h2>
                <dl class="grid grid-cols-1 md:grid-cols-2 gap-4 text-[14px]">
                    <div>
                        <dt class="text-slate-500 mb-1.5 text-[13px]">{{ __('pages.application_id') }}</dt>
                        <dd class="font-semibold text-slate-900">#APP-{{ str_pad($application->id, 4, '0', STR_PAD_LEFT) }}</dd>
                    </div>
                    <div>
                        <dt class="text-slate-500 mb-1.5 text-[13px]">{{ __('pages.medical_institution') }}</dt>
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
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full {{ $application->statusBadgeClass() }} text-[13px] font-medium">{{ $application->statusLabel() }}</span>
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
                <div class="flex items-center justify-between gap-3 mb-4 flex-wrap">
                    <h2 class="text-lg font-semibold text-slate-900">{{ __('pages.application_items') }}</h2>
                    <div class="flex flex-wrap gap-2 text-[12px]">
                        <span class="inline-flex items-center px-2.5 py-1 rounded-full bg-emerald-50 text-emerald-700 font-medium">
                            {{ __('pages.this_app_outbound_qty') }}: {{ $thisAppOutboundQty ?? 0 }}
                        </span>
                        @if(($thisAppReturnQty ?? 0) > 0)
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full bg-amber-50 text-amber-700 font-medium">
                                {{ __('pages.this_app_return_qty') }}: {{ $thisAppReturnQty }}
                            </span>
                        @endif
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-[14px]">
                        <thead class="bg-slate-50 border-b border-slate-200">
                            <tr>
                                <th class="px-4 py-3 text-left font-semibold text-slate-700">{{ __('pages.item_action') }}</th>
                                <th class="px-4 py-3 text-left font-semibold text-slate-700">{{ __('pages.product_code') }}</th>
                                <th class="px-4 py-3 text-left font-semibold text-slate-700">{{ __('pages.product_name') }}</th>
                                <th class="px-4 py-3 text-left font-semibold text-slate-700">{{ __('pages.size') }}</th>
                                <th class="px-4 py-3 text-right font-semibold text-slate-700">{{ __('pages.quantity') }}</th>
                                <th class="px-4 py-3 text-right font-semibold text-slate-700">{{ __('pages.current_stock') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse($application->items as $item)
                                @php
                                    $action = $item->action ?: 'loan';
                                    $actionLabel = match ($action) {
                                        'exchange_out' => __('pages.item_action_old_return'),
                                        'return' => __('pages.item_action_return'),
                                        default => __('pages.item_action_new_loan'),
                                    };
                                    $isOutbound = in_array($action, ['loan', 'exchange_in', 'transfer_change'], true);
                                @endphp
                                <tr class="hover:bg-slate-50/50">
                                    <td class="px-4 py-3">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-medium {{ $isOutbound ? 'bg-primary-50 text-primary-700' : 'bg-slate-100 text-slate-600' }}">
                                            {{ $actionLabel }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-slate-900 font-mono text-[13px]">{{ $item->uniformProduct?->product_code ?? '-' }}</td>
                                    <td class="px-4 py-3 text-slate-900">{{ $item->uniformProduct?->name ?? '-' }}</td>
                                    <td class="px-4 py-3 text-slate-600">{{ $item->uniformProduct?->size ?? '-' }}</td>
                                    <td class="px-4 py-3 text-right font-semibold text-slate-900">{{ $item->quantity }}</td>
                                    <td class="px-4 py-3 text-right text-slate-600">{{ $item->uniformProduct?->stock_quantity ?? '-' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-4 py-8 text-center text-slate-500">{{ __('pages.no_data') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            @if(($otherPendingApps ?? collect())->isNotEmpty())
                <div class="bg-white border border-slate-200 rounded-xl shadow-sm p-6">
                    <h2 class="text-lg font-semibold text-slate-900 mb-1">{{ __('pages.other_pending_at_hospital') }}</h2>
                    <p class="text-[13px] text-slate-500 mb-4">{{ $application->medicalInstitution?->name }}</p>
                    <ul class="divide-y divide-slate-100 border border-slate-100 rounded-lg overflow-hidden">
                        @foreach($otherPendingApps as $other)
                            <li class="flex items-center justify-between gap-3 px-4 py-3 hover:bg-slate-50">
                                <div class="min-w-0">
                                    <p class="text-[13px] font-medium text-slate-900 truncate">
                                        #APP-{{ str_pad($other->id, 4, '0', STR_PAD_LEFT) }}
                                        · {{ $other->employee?->name ?? '-' }}
                                    </p>
                                    <p class="text-[11px] text-slate-500">
                                        {{ $other->statusLabel() }}
                                        · {{ $other->items->sum('quantity') }}{{ __('pages.items') }}
                                        · {{ optional($other->created_at)->format('Y-m-d H:i') }}
                                    </p>
                                </div>
                                <a href="{{ route('admin.applications.show', $other) }}" class="shrink-0 text-[12px] text-primary-600 hover:underline font-medium">
                                    {{ __('pages.details') }}
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if($application->special_approval_reason)
                <div class="bg-amber-50 border-l-4 border-amber-500 rounded-lg px-4 py-3">
                    <h3 class="text-[14px] font-semibold text-amber-800 mb-1">{{ __('pages.special_approval_reason') }}</h3>
                    <p class="text-[13px] text-amber-700">{{ $application->special_approval_reason }}</p>
                </div>
            @endif

            {{-- メモ（帳票印字） --}}
            <div class="bg-white border border-slate-200 rounded-xl shadow-sm p-6">
                <h2 class="text-lg font-semibold text-slate-900 mb-4">{{ __('pages.memo_documents') }}</h2>
                <form method="POST" action="{{ route('admin.applications.update_memos', $application) }}" class="space-y-4">
                    @csrf
                    <div>
                        <label class="block text-[13px] font-semibold text-slate-700 mb-1.5">{{ __('pages.memo1') }}</label>
                        <input type="text" name="memo1" value="{{ old('memo1', $application->memo1) }}" maxlength="500"
                               class="w-full px-3 py-2 rounded-lg border border-slate-300 text-[14px]">
                    </div>
                    <div>
                        <label class="block text-[13px] font-semibold text-slate-700 mb-1.5">{{ __('pages.memo2') }}</label>
                        <input type="text" name="memo2" value="{{ old('memo2', $application->memo2) }}" maxlength="500"
                               class="w-full px-3 py-2 rounded-lg border border-slate-300 text-[14px]">
                    </div>
                    <button type="submit" class="px-4 py-2 rounded-lg bg-slate-800 text-white text-[13px] font-medium hover:bg-slate-900">
                        {{ __('pages.save_memo') }}
                    </button>
                </form>
            </div>

            {{-- 帳票PDF --}}
            @if(in_array($application->status, ['in_progress', 'completed'], true))
                <div class="bg-white border border-slate-200 rounded-xl shadow-sm p-6">
                    <div class="flex items-center justify-between mb-4 gap-3 flex-wrap">
                        <h2 class="text-lg font-semibold text-slate-900">{{ __('pages.ui_1e27b32bc8') }}</h2>
                        <form method="POST" action="{{ route('admin.applications.documents.issue', $application) }}">
                            @csrf
                            <button type="submit" class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-lg border border-primary-300 text-primary-700 hover:bg-primary-50 text-[13px] font-medium">
                                <i class="ph ph-file-pdf text-base"></i>
                                {{ ($latestDocuments ?? collect())->isEmpty() ? __('pages.issue_five_types') : __('pages.reissue_five_types') }}
                            </button>
                        </form>
                    </div>
                    <div class="space-y-2">
                        @php
                            $types = [
                                'order_slip' => __('pages.doc_order_slip'),
                                'delivery_notice' => __('pages.doc_delivery_notice'),
                                'shipping_sales' => __('pages.doc_shipping_sales'),
                                'shipping_warehouse' => __('pages.doc_shipping_warehouse'),
                                'work_slip' => __('pages.doc_work_slip'),
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
                                        <p class="text-[11px] text-slate-400">{{ __('pages.not_issued') }}</p>
                                    @endif
                                </div>
                                <div class="flex items-center gap-2">
                                    @if($doc)
                                        <a href="{{ route('admin.application-documents.preview', $doc) }}" target="_blank"
                                           class="px-2.5 py-1 rounded border border-slate-300 text-[12px] text-slate-700 hover:bg-white">{{ __('pages.view') }}</a>
                                        <a href="{{ route('admin.application-documents.download', $doc) }}"
                                           class="px-2.5 py-1 rounded border border-slate-300 text-[12px] text-slate-700 hover:bg-white">DL</a>
                                    @endif
                                    <form method="POST" action="{{ route('admin.applications.documents.reissue', [$application, $type]) }}">
                                        @csrf
                                        <button type="submit" class="px-2.5 py-1 rounded border border-emerald-300 text-[12px] text-emerald-700 hover:bg-emerald-50">
                                            {{ $doc ? __('pages.reissue') : __('pages.issue') }}
                                        </button>
                                    </form>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    @if(($documentHistory ?? collect())->where('is_latest', false)->isNotEmpty())
                        <details class="mt-4">
                            <summary class="text-[13px] text-slate-600 cursor-pointer">{{ __('pages.document_history') }}</summary>
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
        <div class="lg:col-span-1 space-y-6">
            <div class="bg-white border border-slate-200 rounded-xl shadow-sm p-5 sticky top-6 space-y-5">
                <div>
                    <h2 class="text-lg font-semibold text-slate-900 mb-3">{{ __('pages.approval_glance_title') }}</h2>
                    <dl class="space-y-2.5 text-[13px]">
                        <div class="flex items-center justify-between gap-2">
                            <dt class="text-slate-500">{{ __('pages.hospital_pending_apps') }}</dt>
                            <dd class="font-semibold text-amber-700">{{ $hospitalPendingApps ?? 0 }}</dd>
                        </div>
                        <div class="flex items-center justify-between gap-2">
                            <dt class="text-slate-500">{{ __('pages.hospital_pending_other') }}</dt>
                            <dd class="font-semibold text-slate-900">{{ $hospitalPendingOtherApps ?? 0 }}</dd>
                        </div>
                        <div class="flex items-center justify-between gap-2">
                            <dt class="text-slate-500">{{ __('pages.hospital_pending_outbound_qty') }}</dt>
                            <dd class="font-semibold text-slate-900">{{ $hospitalPendingItemQty ?? 0 }}</dd>
                        </div>
                        <div class="border-t border-slate-100 pt-2.5 flex items-center justify-between gap-2">
                            <dt class="text-slate-500">{{ __('pages.this_app_outbound_qty') }}</dt>
                            <dd class="font-semibold text-primary-700">{{ $thisAppOutboundQty ?? 0 }}</dd>
                        </div>
                        @if(($thisAppReturnQty ?? 0) > 0)
                            <div class="flex items-center justify-between gap-2">
                                <dt class="text-slate-500">{{ __('pages.this_app_return_qty') }}</dt>
                                <dd class="font-semibold text-slate-900">{{ $thisAppReturnQty }}</dd>
                            </div>
                        @endif
                    </dl>
                </div>

                <div>
                    <h3 class="text-[13px] font-semibold text-slate-800 mb-2">{{ __('pages.ordered_items_glance') }}</h3>
                    <ul class="max-h-56 overflow-y-auto space-y-2 border border-slate-100 rounded-lg p-2 bg-slate-50">
                        @forelse($application->items as $item)
                            @php
                                $action = $item->action ?: 'loan';
                                $isOutbound = in_array($action, ['loan', 'exchange_in', 'transfer_change'], true);
                            @endphp
                            <li class="rounded-md bg-white border border-slate-100 px-2.5 py-2">
                                <div class="flex items-start justify-between gap-2">
                                    <div class="min-w-0">
                                        <p class="text-[12px] font-medium text-slate-900 truncate">{{ $item->uniformProduct?->name ?? '-' }}</p>
                                        <p class="text-[11px] text-slate-500 truncate">
                                            {{ $item->uniformProduct?->product_code ?? '-' }}
                                            · {{ $item->uniformProduct?->size ?? '-' }}
                                        </p>
                                    </div>
                                    <span class="shrink-0 text-[13px] font-bold text-slate-900">×{{ $item->quantity }}</span>
                                </div>
                                <p class="text-[10px] mt-1 {{ $isOutbound ? 'text-primary-600' : 'text-slate-500' }}">
                                    @if($action === 'exchange_out')
                                        {{ __('pages.item_action_old_return') }}
                                    @elseif($action === 'return')
                                        {{ __('pages.item_action_return') }}
                                    @else
                                        {{ __('pages.item_action_new_loan') }}
                                    @endif
                                    · {{ __('pages.current_stock') }} {{ $item->uniformProduct?->stock_quantity ?? '-' }}
                                </p>
                            </li>
                        @empty
                            <li class="text-[12px] text-slate-500 px-1 py-2">{{ __('pages.no_data') }}</li>
                        @endforelse
                    </ul>
                </div>

                <div>
                    <h2 class="text-lg font-semibold text-slate-900 mb-3">{{ __('common.actions') }}</h2>
                    <div class="space-y-3">
                    @if(in_array($application->status, ['pending', 'waiting_special_approval']))
                        <form method="POST" action="{{ route('admin.applications.approve', $application) }}" class="inline-block w-full">
                            @csrf
                            <button type="submit" class="w-full px-4 py-2.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-[14px] font-semibold shadow-md transition-all flex items-center justify-center gap-2">
                                <i class="ph ph-check-circle text-base"></i>
                                {{ __('pages.accept_in_progress') }}
                            </button>
                        </form>
                        <button type="button" onclick="openRejectModal()" class="w-full px-4 py-2.5 rounded-lg bg-red-600 hover:bg-red-700 text-white text-[14px] font-semibold shadow-md transition-all flex items-center justify-center gap-2">
                            <i class="ph ph-x-circle text-base"></i>
                            {{ __('pages.reject_do') }}
                        </button>
                    @endif

                    @if($application->status === 'in_progress')
                        <form method="POST" action="{{ route('admin.applications.complete', $application) }}" class="inline-block w-full"
                              data-ui-confirm="{{ __('pages.confirm_ship_complete') }}"
                              data-ui-confirm-title="{{ __('pages.confirm_action_title') }}"
                              data-ui-confirm-tone="brand"
                              data-ui-confirm-ok="{{ __('pages.ship_complete') }}">
                            @csrf
                            <button type="submit" class="w-full px-4 py-2.5 rounded-lg bg-blue-600 hover:bg-blue-700 text-white text-[14px] font-semibold shadow-md transition-all flex items-center justify-center gap-2">
                                <i class="ph ph-truck text-base"></i>
                                {{ __('pages.ship_complete') }}
                            </button>
                        </form>
                    @endif

                    @if($application->status === 'cancel_request')
                        <form method="POST" action="{{ route('admin.applications.cancel_approve', $application) }}" class="inline-block w-full">
                            @csrf
                            <button type="submit" class="w-full px-4 py-2.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-[14px] font-semibold shadow-md transition-all flex items-center justify-center gap-2">
                                <i class="ph ph-check-circle text-base"></i>
                                {{ __('pages.cancel_approved') }}
                            </button>
                        </form>
                        <button type="button" onclick="openRejectCancelModal()" class="w-full px-4 py-2.5 rounded-lg bg-red-600 hover:bg-red-700 text-white text-[14px] font-semibold shadow-md transition-all flex items-center justify-center gap-2">
                            <i class="ph ph-x-circle text-base"></i>
                            {{ __('pages.cancel_rejected') }}
                        </button>
                    @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- 却下モーダル --}}
    <div id="reject-modal" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-xl shadow-xl max-w-md w-full p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-slate-900">{{ __('pages.ui_14b0eb3aa8') }}</h3>
                <button type="button" onclick="closeRejectModal()" class="text-slate-400 hover:text-slate-600">
                    <i class="ph ph-x text-xl"></i>
                </button>
            </div>
            <form method="POST" action="{{ route('admin.applications.reject', $application) }}">
                @csrf
                <div class="mb-4">
                    <label class="block text-slate-700 text-[14px] font-semibold mb-2">
                        {{ __('pages.rejection_reason_label') }} <span class="text-red-500">*</span>
                    </label>
                    <textarea name="rejection_reason" rows="4" required
                              class="w-full px-4 py-2.5 rounded-lg border border-slate-300 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 text-[14px] bg-white shadow-sm transition-all resize-none"
                              placeholder="{{ __('pages.rejection_reason_placeholder') }}"></textarea>
                </div>
                <div class="flex items-center justify-end gap-3">
                    <button type="button" onclick="closeRejectModal()" class="px-4 py-2.5 rounded-lg border border-slate-300 text-slate-700 hover:bg-slate-50 text-[14px] font-medium transition-all">
                        {{ __('pages.cancel') }}
                    </button>
                    <button type="submit" class="px-4 py-2.5 rounded-lg bg-red-600 hover:bg-red-700 text-white text-[14px] font-semibold transition-all">
                        {{ __('pages.reject_do') }}
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- キャンセル却下モーダル --}}
    <div id="reject-cancel-modal" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-xl shadow-xl max-w-md w-full p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-slate-900">{{ __('pages.reject_cancel') }}</h3>
                <button type="button" onclick="closeRejectCancelModal()" class="text-slate-400 hover:text-slate-600">
                    <i class="ph ph-x text-xl"></i>
                </button>
            </div>
            <form method="POST" action="{{ route('admin.applications.cancel_reject', $application) }}">
                @csrf
                <div class="mb-4">
                    <label class="block text-slate-700 text-[14px] font-semibold mb-2">
                        {{ __('pages.rejection_reason_label') }} <span class="text-red-500">*</span>
                    </label>
                    <textarea name="rejection_reason" rows="4" required
                              class="w-full px-4 py-2.5 rounded-lg border border-slate-300 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 text-[14px] bg-white shadow-sm transition-all resize-none"
                              placeholder="{{ __('pages.rejection_reason_placeholder') }}"></textarea>
                </div>
                <div class="flex items-center justify-end gap-3">
                    <button type="button" onclick="closeRejectCancelModal()" class="px-4 py-2.5 rounded-lg border border-slate-300 text-slate-700 hover:bg-slate-50 text-[14px] font-medium transition-all">
                        {{ __('pages.cancel') }}
                    </button>
                    <button type="submit" class="px-4 py-2.5 rounded-lg bg-red-600 hover:bg-red-700 text-white text-[14px] font-semibold transition-all">
                        {{ __('pages.reject_do') }}
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
