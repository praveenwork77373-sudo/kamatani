@extends('layouts.app')

@section('title', __('pages.medical_institutions_management') . ' - ' . __('pages.new_registration') . ' | ' . config('app.name'))
@section('page_title', __('pages.medical_institutions_management'))

@section('content')
    <div class="mb-8">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h1 class="text-2xl font-bold text-slate-900 tracking-tight">{{ __('pages.new_registration') }}</h1>
                <p class="text-[14px] text-slate-600 mt-1.5">{{ __('pages.medical_institutions_management_description') }}</p>
            </div>
            <a href="{{ route('admin.masters.institutions') }}" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-lg border border-slate-300 text-slate-700 hover:bg-slate-50 text-[14px] font-medium transition-all">
                <i data-lucide="arrow-left" class="w-4 h-4"></i>
                {{ __('common.back_to_list') }}
            </a>
        </div>
    </div>

    <form method="POST" action="{{ route('admin.masters.institutions.store') }}" class="max-w-4xl mx-auto">
        @csrf

        <div class="bg-white border border-slate-200 rounded-xl shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-200 bg-gradient-to-r from-slate-50 to-white">
                <div class="flex items-center gap-3">
                    <div class="h-10 w-10 rounded-lg bg-primary-100 flex items-center justify-center">
                        <i data-lucide="building-2" class="w-5 h-5 text-primary-600"></i>
                    </div>
                    <div>
                        <h2 class="text-lg font-semibold text-slate-900">{{ __('pages.basic_information') }}</h2>
                        <p class="text-[13px] text-slate-500">{{ __('pages.fill_in_basic_information') }}</p>
                    </div>
                </div>
            </div>

            <div class="p-6 space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <x-master-code-field
                        type="institution"
                        name="code"
                        :value="old('code')"
                        :label="__('pages.institution_code')"
                    />

                    <div>
                        <label class="ui-label">{{ __('pages.notification_email') }} <span class="text-red-500">*</span></label>
                        <div class="ui-field-icon">
                            <i data-lucide="mail"></i>
                            <input type="email" name="notification_email" value="{{ old('notification_email') }}" required class="ui-input"
                                   placeholder="example@hospital.com">
                        </div>
                        @error('notification_email')<p class="ui-error">{{ $message }}</p>@enderror
                    </div>

                    <div class="md:col-span-2">
                        <label class="ui-label">{{ __('pages.institution_name') }} <span class="text-red-500">*</span></label>
                        <div class="ui-field-icon">
                            <i data-lucide="building-2"></i>
                            <input type="text" name="name" value="{{ old('name') }}" required class="ui-input"
                                   placeholder="{{ __('pages.institution_name_placeholder') }}">
                        </div>
                        @error('name')<p class="ui-error">{{ $message }}</p>@enderror
                    </div>
                </div>

                <div class="border-t border-slate-200 pt-6">
                    <h3 class="text-[15px] font-semibold text-slate-700 mb-1">{{ __('pages.overall_manager_account') }}</h3>
                    <p class="text-[13px] text-slate-500 mb-4">{{ __('pages.overall_manager_account_description') }}</p>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="ui-label">{{ __('pages.manager_name') }} <span class="text-red-500">*</span></label>
                            <div class="ui-field-icon">
                                <i data-lucide="user"></i>
                                <input type="text" name="manager_name" value="{{ old('manager_name') }}" required class="ui-input"
                                       placeholder="{{ __('pages.manager_name_placeholder') }}">
                            </div>
                            @error('manager_name')<p class="ui-error">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="ui-label">{{ __('pages.manager_email') }} <span class="text-red-500">*</span></label>
                            <div class="ui-field-icon">
                                <i data-lucide="mail"></i>
                                <input type="email" name="manager_email" value="{{ old('manager_email') }}" required class="ui-input"
                                       placeholder="manager@hospital.com">
                            </div>
                            @error('manager_email')<p class="ui-error">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="ui-label">{{ __('pages.manager_password') }} <span class="text-red-500">*</span></label>
                            <div class="ui-field-icon">
                                <i data-lucide="lock"></i>
                                <input type="password" name="manager_password" required class="ui-input"
                                       placeholder="{{ __('pages.password_placeholder') }}">
                            </div>
                            @error('manager_password')<p class="ui-error">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="ui-label">{{ __('pages.manager_password_confirmation') }} <span class="text-red-500">*</span></label>
                            <div class="ui-field-icon">
                                <i data-lucide="lock"></i>
                                <input type="password" name="manager_password_confirmation" required class="ui-input"
                                       placeholder="{{ __('pages.password_placeholder') }}">
                            </div>
                            @error('manager_password_confirmation')<p class="ui-error">{{ $message }}</p>@enderror
                        </div>
                    </div>
                </div>

                <div class="border-t border-slate-200 pt-6">
                    <h3 class="text-[15px] font-semibold text-slate-700 mb-1">{{ __('pages.additional_settings') }}</h3>
                    <p class="text-[13px] text-slate-500 mb-4">{{ __('pages.ui_04d62da1f7') }}</p>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="ui-label">{{ __('pages.closing_day_billing') }} <span class="text-[12px] font-normal text-slate-500">(1〜28)</span></label>
                            <div class="ui-field-icon">
                                <i data-lucide="calendar"></i>
                                <input type="number" name="billing_closing_day" value="{{ old('billing_closing_day', 1) }}" min="1" max="28" class="ui-input">
                            </div>
                            @error('billing_closing_day')<p class="ui-error">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="ui-label">{{ __('pages.sales_rep_name') }}</label>
                            <div class="ui-field-icon">
                                <i data-lucide="user-check"></i>
                                <input type="text" name="sales_person_name" value="{{ old('sales_person_name') }}" class="ui-input"
                                       placeholder="{{ __('pages.employee_name_placeholder') }}">
                            </div>
                            @error('sales_person_name')<p class="ui-error">{{ $message }}</p>@enderror
                        </div>
                    </div>
                </div>
            </div>

            <div class="px-6 py-4 bg-slate-50 border-t border-slate-200 flex items-center justify-between">
                <p class="text-[12px] text-slate-500">{{ __('pages.required_fields_note') }}</p>
                <div class="flex items-center gap-3">
                    <a href="{{ route('admin.masters.institutions') }}" class="ui-btn ui-btn-secondary">{{ __('common.cancel') }}</a>
                    <button type="submit" class="ui-btn ui-btn-primary">{{ __('pages.register') }}</button>
                </div>
            </div>
        </div>
    </form>
@endsection
