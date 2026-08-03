@extends('layouts.app')

@section('title', __('pages.generate_billing_data') . ' | ' . config('app.name'))
@section('page_title', __('pages.generate_billing_data'))

@section('content')
    <div class="mb-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-slate-900 tracking-tight">{{ __('pages.generate_billing_data') }}</h1>
            </div>
            <a href="{{ route('admin.billing.index') }}" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-lg border border-slate-300 text-slate-700 hover:bg-slate-50 text-[14px] font-medium transition-all">
                <i data-lucide="arrow-left" class="w-4 h-4"></i>
                {{ __('pages.past_billing_history') }}
            </a>
        </div>
    </div>

    <form method="POST" action="{{ route('admin.billing.generate') }}" id="billingForm">
        @csrf
        <div class="bg-white border border-slate-200 rounded-xl shadow-sm overflow-hidden mb-6">
            <div class="px-6 py-4 border-b border-slate-200 bg-gradient-to-r from-slate-50 to-white">
                <h2 class="text-lg font-bold text-slate-900">{{ __('pages.billing_generation_conditions') }}</h2>
            </div>

            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-slate-700 text-[14px] font-semibold mb-2">
                            {{ __('pages.target_institution') }} <span class="text-red-500">*</span>
                        </label>
                        <select name="medical_institution_id" id="institution-select" required
                                class="w-full px-4 py-2.5 rounded-lg border border-slate-300 text-[14px] bg-white">
                            <option value="">{{ __('pages.select_please') }}</option>
                            @foreach($institutions as $inst)
                                <option value="{{ $inst->id }}"
                                    data-closing-day="{{ $inst->billing_closing_day ?? 1 }}"
                                    @selected(old('medical_institution_id', $selectedInstitutionId)==$inst->id)>
                                    {{ $inst->name }}（締め日: {{ $inst->billing_closing_day ?? 1 }}日）
                                </option>
                            @endforeach
                        </select>
                        @error('medical_institution_id')
                            <p class="text-[13px] text-red-600 mt-1.5">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-slate-700 text-[14px] font-semibold mb-2">{{ __('pages.target_month') }}</label>
                        <input type="month" name="target_month" id="target-month"
                               value="{{ old('target_month', \Carbon\Carbon::parse($targetDate)->format('Y-m')) }}"
                               class="w-full px-4 py-2.5 rounded-lg border border-slate-300 text-[14px] bg-white">
                    </div>

                    <div>
                        <label class="block text-slate-700 text-[14px] font-semibold mb-2">
                            {{ __('pages.reference_date') }} <span class="text-red-500">*</span>
                        </label>
                        <input type="date" name="target_date" id="target-date" value="{{ old('target_date', $targetDate) }}" required
                               class="w-full px-4 py-2.5 rounded-lg border border-slate-300 text-[14px] bg-white">
                        @error('target_date')
                            <p class="text-[13px] text-red-600 mt-1.5">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-slate-700 text-[14px] font-semibold mb-2">{{ __('pages.billing_model') }}</label>
                        <input type="text" value="{{ __('pages.billing_model_plan_assigned') }}" readonly
                               class="w-full px-4 py-2.5 rounded-lg border border-slate-300 bg-slate-50 text-[14px] text-slate-600">
                    </div>
                </div>

                @if($previewData)
                    <div class="mt-6 border border-slate-200 rounded-xl overflow-hidden">
                        <div class="px-5 py-3 bg-slate-50 border-b border-slate-200">
                            <h3 class="font-semibold text-slate-900">{{ __('pages.billing_preview_title', ['date' => $previewData['reference_date'] ?? $targetDate]) }}</h3>
                        </div>
                        <div class="p-5 grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                            <div class="rounded-lg bg-blue-50 border border-blue-100 p-4">
                                <p class="text-[12px] text-blue-700">{{ __('pages.billing_billed_headcount') }}</p>
                                <p class="text-2xl font-bold text-slate-900">{{ number_format($previewData['summary']['total_targets'] ?? 0) }}{{ __('pages.people') }}</p>
                            </div>
                            <div class="rounded-lg bg-emerald-50 border border-emerald-100 p-4">
                                <p class="text-[12px] text-emerald-700">{{ __('pages.billing_plan_count') }}</p>
                                <p class="text-2xl font-bold text-slate-900">{{ number_format($previewData['summary']['plan_count'] ?? 0) }}</p>
                            </div>
                            <div class="rounded-lg bg-amber-50 border border-amber-100 p-4">
                                <p class="text-[12px] text-amber-700">{{ __('pages.billing_amount_excl_tax') }}</p>
                                <p class="text-2xl font-bold text-slate-900">¥{{ number_format($previewData['summary']['billing_amount'] ?? 0) }}</p>
                            </div>
                        </div>
                        <div class="px-5 pb-5 overflow-x-auto">
                            <table class="min-w-full text-[13px]">
                                <thead class="bg-slate-50">
                                    <tr>
                                        <th class="px-3 py-2 text-left">{{ __('pages.plan_name') }}</th>
                                        <th class="px-3 py-2 text-left">{{ __('pages.plan_code') }}</th>
                                        <th class="px-3 py-2 text-left">{{ __('pages.contract_period_label') }}</th>
                                        <th class="px-3 py-2 text-right">{{ __('pages.billing_contract_slots') }}</th>
                                        <th class="px-3 py-2 text-right">{{ __('pages.assigned_count') }}</th>
                                        <th class="px-3 py-2 text-right">{{ __('pages.unit_price') }}</th>
                                        <th class="px-3 py-2 text-right">{{ __('pages.billing_official_amount') }}</th>
                                        <th class="px-3 py-2 text-right">{{ __('pages.billing_reference_capacity') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    @forelse(($previewData['plan_summary'] ?? []) as $plan)
                                        <tr>
                                            <td class="px-3 py-2">{{ $plan['plan_name'] }}</td>
                                            <td class="px-3 py-2">{{ $plan['plan_code'] }}</td>
                                            <td class="px-3 py-2 text-[12px] text-slate-600">
                                                {{ $plan['contract_start_date'] ?? '—' }}
                                                〜
                                                {{ $plan['contract_end_date'] ?? __('pages.billing_continued') }}
                                            </td>
                                            <td class="px-3 py-2 text-right">{{ number_format($plan['contracted_slots'] ?? 0) }}</td>
                                            <td class="px-3 py-2 text-right">{{ number_format($plan['assigned_count'] ?? 0) }}</td>
                                            <td class="px-3 py-2 text-right">¥{{ number_format($plan['unit_price_ex_tax'] ?? 0) }}</td>
                                            <td class="px-3 py-2 text-right font-semibold">¥{{ number_format($plan['amount_ex_tax'] ?? 0) }}</td>
                                            <td class="px-3 py-2 text-right text-slate-500">¥{{ number_format($plan['amount_by_contract_slots'] ?? 0) }}</td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="8" class="px-3 py-6 text-center text-slate-500">{{ __('pages.billing_no_plan_rows') }}</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif
            </div>

            <div class="px-6 py-4 bg-slate-50 border-t border-slate-200 flex items-center justify-end gap-3">
                <a href="{{ route('admin.billing.index') }}" class="px-5 py-2.5 rounded-lg border border-slate-300 text-slate-700 hover:bg-white text-[14px] font-medium">{{ __('common.cancel') }}</a>
                <button type="submit" class="px-6 py-2.5 rounded-lg bg-green-600 hover:bg-green-700 text-white text-[14px] font-semibold shadow-md flex items-center gap-2">
                    <i data-lucide="file-check" class="w-4 h-4"></i>
                    {{ __('pages.generate_billing_data') }}
                </button>
            </div>
        </div>
    </form>

    @push('scripts')
    <script>
        function closingDayForSelected() {
            const opt = document.getElementById('institution-select')?.selectedOptions?.[0];
            return parseInt(opt?.dataset?.closingDay || '1', 10) || 1;
        }
        function applyClosingDayFromMonth() {
            const month = document.getElementById('target-month')?.value;
            if (!month) return;
            const day = Math.min(closingDayForSelected(), 28);
            const d = new Date(month + '-01T00:00:00');
            const last = new Date(d.getFullYear(), d.getMonth() + 1, 0).getDate();
            const useDay = Math.min(day, last);
            document.getElementById('target-date').value = month + '-' + String(useDay).padStart(2, '0');
        }
        function reloadPreview() {
            const institutionId = document.getElementById('institution-select')?.value;
            const targetDate = document.getElementById('target-date')?.value;
            const month = document.getElementById('target-month')?.value;
            if (institutionId && targetDate) {
                let url = '{{ route("admin.billing.create") }}?medical_institution_id=' + institutionId + '&target_date=' + targetDate;
                if (month) url += '&target_month=' + month;
                window.location.href = url;
            }
        }
        document.getElementById('target-month')?.addEventListener('change', function () {
            applyClosingDayFromMonth();
            setTimeout(reloadPreview, 50);
        });
        document.getElementById('institution-select')?.addEventListener('change', function () {
            applyClosingDayFromMonth();
            reloadPreview();
        });
        document.getElementById('target-date')?.addEventListener('change', reloadPreview);
        document.addEventListener('DOMContentLoaded', function () {
            if (typeof lucide !== 'undefined') lucide.createIcons();
        });
    </script>
    @endpush
@endsection
