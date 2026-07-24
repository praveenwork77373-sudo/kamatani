@extends('layouts.app')

@section('title', __('pages.generate_billing_data') . ' | ' . config('app.name'))
@section('page_title', __('pages.generate_billing_data'))

@section('content')
    <div class="mb-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-slate-900 tracking-tight">{{ __('pages.generate_billing_data') }}</h1>
                <p class="text-[14px] text-slate-600 mt-1.5">{{ __('pages.generate_billing_data_description') }}</p>
            </div>
            <a href="{{ route('admin.billing.index') }}" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-lg border border-slate-300 text-slate-700 hover:bg-slate-50 text-[14px] font-medium transition-all">
                <i data-lucide="arrow-left" class="w-4 h-4"></i>
                {{ __('pages.past_billing_history') }}
            </a>
        </div>
    </div>

    <form method="POST" action="{{ route('admin.billing.generate') }}" id="billingForm">
        @csrf

        {{-- 請求データ生成条件 --}}
        <div class="bg-white border border-slate-200 rounded-xl shadow-sm overflow-hidden mb-6">
            <div class="px-6 py-4 border-b border-slate-200 bg-gradient-to-r from-slate-50 to-white">
                <h2 class="text-lg font-bold text-slate-900 flex items-center gap-2">
                    <i data-lucide="file-text" class="w-5 h-5 text-primary-600"></i>
                    {{ __('pages.billing_generation_conditions') }}
                </h2>
            </div>
            
            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-slate-700 text-[14px] font-semibold mb-2">
                            {{ __('pages.target_institution') }} <span class="text-red-500">*</span>
                        </label>
                        <select name="medical_institution_id" id="institution-select" required
                                class="w-full px-4 py-2.5 rounded-lg border border-slate-300 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 text-[14px] bg-white shadow-sm transition-all">
                            <option value="">{{ __('pages.select_please') }}</option>
                            @foreach($institutions as $inst)
                                <option value="{{ $inst->id }}" @selected(old('medical_institution_id', $selectedInstitutionId)==$inst->id)>{{ $inst->name }}</option>
                            @endforeach
                        </select>
                        @error('medical_institution_id')
                            <p class="text-[13px] text-red-600 mt-1.5 flex items-center gap-1">
                                <i data-lucide="alert-circle" class="w-4 h-4"></i>
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-slate-700 text-[14px] font-semibold mb-2">
                            {{ __('pages.contract_number') }}
                        </label>
                        <input type="text" 
                               value="{{ $contract ? ($contract->contract_number ?? 'CT-' . \Carbon\Carbon::parse($targetDate)->format('Y') . '-' . str_pad($contract->id, 4, '0', STR_PAD_LEFT)) : '-' }}" 
                               readonly
                               class="w-full px-4 py-2.5 rounded-lg border border-slate-300 bg-slate-50 text-[14px] text-slate-600">
                    </div>

                    <div>
                        <label class="block text-slate-700 text-[14px] font-semibold mb-2">
                            {{ __('pages.billing_target_month') }} <span class="text-red-500">*</span>
                        </label>
                        <input type="month" name="target_month" id="target-month" 
                               value="{{ old('target_month', \Carbon\Carbon::parse($targetDate)->format('Y-m')) }}" 
                               required
                               class="w-full px-4 py-2.5 rounded-lg border border-slate-300 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 text-[14px] bg-white shadow-sm transition-all">
                    </div>

                    <div>
                        <label class="block text-slate-700 text-[14px] font-semibold mb-2">
                            {{ __('pages.reference_date') }} <span class="text-red-500">*</span>
                        </label>
                        <input type="date" name="target_date" id="target-date" value="{{ old('target_date', $targetDate) }}" required
                               class="w-full px-4 py-2.5 rounded-lg border border-slate-300 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 text-[14px] bg-white shadow-sm transition-all">
                        @error('target_date')
                            <p class="text-[13px] text-red-600 mt-1.5 flex items-center gap-1">
                                <i data-lucide="alert-circle" class="w-4 h-4"></i>
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-slate-700 text-[14px] font-semibold mb-2">
                            {{ __('pages.contract_type') }}
                        </label>
                        <input type="text" 
                               value="{{ $contract ? ($contract->billing_model === 'fixed_per_person' ? __('pages.fixed_price_system') . ' (' . __('pages.unit_price') . ')' : __('pages.point_usage_billing') . ' (' . __('pages.unit_price') . ')') : '-' }}" 
                               readonly
                               class="w-full px-4 py-2.5 rounded-lg border border-slate-300 bg-slate-50 text-[14px] text-slate-600">
                    </div>
                </div>

                {{-- 契約情報 --}}
                @if($contract)
                <div class="mt-6 p-5 bg-gradient-to-br from-blue-50 to-indigo-50 border-2 border-blue-200 rounded-xl shadow-sm">
                    <h3 class="text-base font-bold text-blue-900 mb-4 flex items-center gap-2">
                        <i data-lucide="file-signature" class="w-5 h-5"></i>
                        {{ __('pages.contract_information') }}
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-[13px]">
                        <div class="bg-white/60 rounded-lg p-3 border border-blue-100">
                            <p class="text-blue-600 font-medium mb-1">{{ __('pages.contract_period') }}</p>
                            <p class="text-slate-900 font-semibold">
                                {{ $contract->valid_from ? $contract->valid_from->format('Y/m/d') : '-' }} ~ {{ $contract->valid_until ? $contract->valid_until->format('Y/m/d') : __('pages.unlimited') }}
                            </p>
                        </div>
                        <div class="bg-white/60 rounded-lg p-3 border border-blue-100">
                            <p class="text-blue-600 font-medium mb-1">{{ __('pages.billing_model') }}</p>
                            <p class="text-slate-900 font-semibold">
                                {{ $contract->billing_model === 'fixed_per_person' ? __('pages.fixed_per_person_billing') : __('pages.point_usage_billing') }}
                            </p>
                        </div>
                        @if($contract->per_person_loan_limit)
                        <div class="bg-white/60 rounded-lg p-3 border border-blue-100">
                            <p class="text-blue-600 font-medium mb-1">{{ __('pages.return_limit_per_person') }}</p>
                            <p class="text-slate-900 font-semibold">{{ $contract->per_person_loan_limit }}{{ __('pages.points') }}</p>
                        </div>
                        @endif
                        @if($contract->loan_limit_per_item)
                        <div class="bg-white/60 rounded-lg p-3 border border-blue-100">
                            <p class="text-blue-600 font-medium mb-1">{{ __('pages.item_limit') }}</p>
                            <p class="text-slate-900 font-semibold">¥{{ number_format($contract->loan_limit_per_item) }}/{{ __('pages.per_person') }}</p>
                        </div>
                        @endif
                        @if($contract->point_unit_price)
                        <div class="bg-white/60 rounded-lg p-3 border border-blue-100">
                            <p class="text-blue-600 font-medium mb-1">{{ __('pages.point_unit_price_label') }}</p>
                            <p class="text-slate-900 font-semibold">¥{{ number_format($contract->point_unit_price) }}</p>
                        </div>
                        @endif
                        @if($contract->fixed_unit_price)
                        <div class="bg-white/60 rounded-lg p-3 border border-blue-100">
                            <p class="text-blue-600 font-medium mb-1">{{ __('pages.fixed_unit_price_label') }}</p>
                            <p class="text-slate-900 font-semibold">¥{{ number_format($contract->fixed_unit_price) }}</p>
                        </div>
                        @endif
                    </div>
                </div>
                @endif

                {{-- Preview Data --}}
                @if($previewData)
                <div class="mt-6 bg-white border-2 border-slate-200 rounded-xl shadow-sm overflow-hidden">
                    <div class="px-6 py-4 border-b border-slate-200 bg-gradient-to-r from-slate-50 to-white">
                        <h3 class="text-lg font-bold text-slate-900 flex items-center gap-2">
                            <i data-lucide="eye" class="w-5 h-5 text-primary-600"></i>
                            {{ __('pages.preview_data') }}
                        </h3>
                        <p class="text-[13px] text-slate-600 mt-1">{{ __('pages.preview_data_description') }}</p>
                    </div>
                    
                    <div class="p-6">
                        {{-- Summary Cards --}}
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                            <div class="bg-gradient-to-br from-blue-500 via-blue-600 to-indigo-700 rounded-xl p-4 text-white">
                                <p class="text-[12px] text-blue-100 mb-1">{{ __('pages.number_of_targets') }}</p>
                                <p class="text-3xl font-bold">{{ number_format($previewData['total_employees'] ?? 0) }}{{ __('pages.people') }}</p>
                            </div>
                            <div class="bg-gradient-to-br from-emerald-500 via-teal-600 to-cyan-700 rounded-xl p-4 text-white">
                                <p class="text-[12px] text-emerald-100 mb-1">{{ __('pages.total_loan_points') }}</p>
                                <p class="text-3xl font-bold">{{ number_format($previewData['total_loan_points'] ?? 0) }}{{ __('pages.points') }}</p>
                            </div>
                            <div class="bg-gradient-to-br from-purple-500 via-violet-600 to-indigo-700 rounded-xl p-4 text-white">
                                <p class="text-[12px] text-purple-100 mb-1">{{ __('pages.employees_with_loans') }}</p>
                                <p class="text-3xl font-bold">{{ number_format($previewData['employees_with_loans'] ?? 0) }}{{ __('pages.people') }}</p>
                            </div>
                            <div class="bg-gradient-to-br from-amber-500 via-orange-500 to-red-600 rounded-xl p-4 text-white">
                                <p class="text-[12px] text-amber-100 mb-1">{{ __('pages.estimated_billing_amount') }}</p>
                                <p class="text-3xl font-bold">¥{{ number_format($previewData['estimated_billing_amount'] ?? 0) }}</p>
                            </div>
                        </div>

                        {{-- Department Breakdown --}}
                        @if(!empty($previewData['department_breakdown']))
                        <div class="border border-slate-200 rounded-lg overflow-hidden">
                            <div class="px-4 py-3 bg-slate-50 border-b border-slate-200">
                                <h4 class="text-sm font-semibold text-slate-900 flex items-center gap-2">
                                    <i data-lucide="building-2" class="w-4 h-4 text-primary-600"></i>
                                    {{ __('pages.department_breakdown') }}
                                </h4>
                            </div>
                            <div class="overflow-x-auto">
                                <table class="min-w-full text-[13px]">
                                    <thead class="bg-slate-50">
                                        <tr>
                                            <th class="px-4 py-2.5 text-left font-semibold text-slate-700">{{ __('pages.department_name') }}</th>
                                            <th class="px-4 py-2.5 text-right font-semibold text-slate-700">{{ __('pages.number_of_targets') }}</th>
                                            <th class="px-4 py-2.5 text-right font-semibold text-slate-700">{{ __('pages.total_loan_points') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100">
                                        @foreach($previewData['department_breakdown'] as $dept)
                                        <tr class="hover:bg-slate-50">
                                            <td class="px-4 py-2.5 font-medium text-slate-900">{{ $dept['name'] }}</td>
                                            <td class="px-4 py-2.5 text-right text-slate-900">{{ number_format($dept['employee_count'] ?? 0) }}{{ __('pages.people') }}</td>
                                            <td class="px-4 py-2.5 text-right text-slate-900">{{ number_format($dept['loan_points'] ?? 0) }}{{ __('pages.points') }}</td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
                @endif
            </div>

            <div class="px-6 py-4 bg-slate-50 border-t border-slate-200 flex items-center justify-end gap-3">
                <a href="{{ route('admin.billing.index') }}" class="px-5 py-2.5 rounded-lg border border-slate-300 text-slate-700 hover:bg-white text-[14px] font-medium transition-all">
                    {{ __('common.cancel') }}
                </a>
                <button type="submit" class="px-6 py-2.5 rounded-lg bg-green-600 hover:bg-green-700 text-white text-[14px] font-semibold shadow-md transition-all flex items-center gap-2">
                    <i data-lucide="file-check" class="w-4 h-4"></i>
                    {{ __('pages.generate_billing_data') }}
                </button>
            </div>
        </div>
    </form>

    @push('scripts')
    <script>
        function reloadPreview() {
            const institutionId = document.getElementById('institution-select')?.value;
            const targetDate = document.getElementById('target-date')?.value;
            
            if (institutionId && targetDate) {
                window.location.href = '{{ route("admin.billing.create") }}?medical_institution_id=' + institutionId + '&target_date=' + targetDate;
            }
        }

        // Update target date when month changes
        document.getElementById('target-month')?.addEventListener('change', function() {
            const month = this.value;
            if (month) {
                const lastDay = new Date(month + '-01');
                lastDay.setMonth(lastDay.getMonth() + 1);
                lastDay.setDate(0);
                const targetDateInput = document.getElementById('target-date');
                targetDateInput.value = month + '-' + String(lastDay.getDate()).padStart(2, '0');
                
                // Reload preview if institution is selected
                setTimeout(() => reloadPreview(), 100);
            }
        });

        // Load contract info when institution changes
        document.getElementById('institution-select')?.addEventListener('change', function() {
            reloadPreview();
        });

        // Reload preview when target date changes
        document.getElementById('target-date')?.addEventListener('change', function() {
            reloadPreview();
        });

        // Initialize Lucide icons
        document.addEventListener('DOMContentLoaded', function() {
            if (typeof lucide !== 'undefined') {
                lucide.createIcons();
            }
        });
    </script>
    @endpush
@endsection
