@extends('layouts.app')

@section('title', __('pages.application_detail') . ' | ' . __('auth.system_title'))
@section('page_title', __('pages.application_detail'))

@section('content')
    <div class="mb-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-slate-900 tracking-tight">{{ __('pages.application_detail') }}</h1>
                <p class="text-[14px] text-slate-600 mt-1.5">{{ __('pages.application_detail_description') }}</p>
            </div>
            <a href="{{ route('applications.index') }}" class="inline-flex items-center gap-2 px-4 py-2 rounded-lg border border-slate-300 text-slate-700 hover:bg-slate-50 text-[14px] font-medium transition-all">
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
                        <dt class="text-slate-500 mb-1">{{ __('pages.application_id') }}</dt>
                        <dd class="font-semibold text-slate-900">#APP-{{ str_pad($application->id, 4, '0', STR_PAD_LEFT) }}</dd>
                    </div>
                    <div>
                        <dt class="text-slate-500 mb-1">{{ __('pages.application_type_label') }}</dt>
                        <dd>
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-primary-100 text-primary-700 text-[13px] font-medium">{{ $application->typeLabel() }}</span>
                        </dd>
                    </div>
                    <div>
                        <dt class="text-slate-500 mb-1">{{ __('pages.status_label') }}</dt>
                        <dd>
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full {{ $application->statusBadgeClass() }} text-[13px] font-medium">{{ $application->statusLabel() }}</span>
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
                        <dt class="text-slate-500 mb-1">{{ __('pages.ui_8ab5ed56b9') }}</dt>
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
                <h2 class="text-lg font-semibold text-slate-900 mb-4">{{ __('pages.application_items') }}</h2>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-[14px]">
                        <thead class="bg-slate-50 border-b border-slate-200">
                            <tr>
                                <th class="px-4 py-3 text-left font-semibold text-slate-700">{{ __('pages.product_code') }}</th>
                                <th class="px-4 py-3 text-left font-semibold text-slate-700">{{ __('pages.product_name') }}</th>
                                <th class="px-4 py-3 text-left font-semibold text-slate-700">{{ __('pages.size') }}</th>
                                <th class="px-4 py-3 text-left font-semibold text-slate-700">{{ __('pages.classification') }}</th>
                                <th class="px-4 py-3 text-right font-semibold text-slate-700">{{ __('pages.quantity') }}</th>
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
                                            {{ __('pages.item_action_old_return') }}
                                        @elseif($item->action === 'return')
                                            {{ __('pages.item_action_return') }}
                                        @else
                                            {{ __('pages.item_action_new_loan') }}
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
                    <h3 class="text-[14px] font-semibold text-amber-800 mb-1">{{ __('pages.special_approval_reason') }}</h3>
                    <p class="text-[13px] text-amber-700">{{ $application->special_approval_reason }}</p>
                </div>
            @endif
        </div>

        {{-- アクションサイドバー --}}
        <div class="lg:col-span-1">
            <div class="bg-white border border-slate-200 rounded-xl shadow-sm p-6 sticky top-6">
                <h2 class="text-lg font-semibold text-slate-900 mb-4">{{ __('common.actions') }}</h2>
                <div class="space-y-3">
                    {{-- Cancel request — allowed until completed --}}
                    @if($application->isCancellableByHospital())
                        <form method="POST" action="{{ route('applications.request_cancel', $application) }}" class="inline-block w-full">
                            @csrf
                            <button type="submit" 
                                    onclick="return confirm(@js(__('pages.confirm_cancel_request')))"
                                    class="w-full px-4 py-2.5 rounded-lg bg-orange-600 hover:bg-orange-700 text-white text-[14px] font-medium shadow-md transition-all flex items-center justify-center gap-2">
                                <i class="ph ph-x-circle text-base"></i>
                                {{ __('pages.cancel_request') }}
                            </button>
                        </form>
                    @endif
                    
                    {{-- Cancel request status messages --}}
                    @if($application->status === 'cancel_request')
                        <div class="p-3 bg-amber-50 border border-amber-200 rounded-lg text-[13px] text-amber-800 flex items-center gap-2">
                            <i class="ph ph-clock text-base"></i>
                            <span>{{ __('pages.cancel_request_pending_message') }}</span>
                        </div>
                    @endif
                    
                    @if($application->status === 'cancel_approved')
                        <div class="p-3 bg-slate-50 border border-slate-200 rounded-lg text-[13px] text-slate-800 flex items-center gap-2">
                            <i class="ph ph-check-circle text-base"></i>
                            <span>{{ __('pages.cancel_approved_message') }}</span>
                        </div>
                    @endif
                    
                    @if($application->status === 'cancel_rejected')
                        <div class="p-3 bg-red-50 border border-red-200 rounded-lg text-[13px] text-red-800 flex items-center gap-2">
                            <i class="ph ph-x-circle text-base"></i>
                            <span>{{ __('pages.cancel_rejected_message') }}</span>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
