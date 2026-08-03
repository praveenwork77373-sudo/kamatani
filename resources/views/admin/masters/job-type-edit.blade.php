@extends('layouts.app')

@section('title', __('pages.job_types_management') . ' - ' . __('common.edit') . ' | ' . config('app.name'))
@section('page_title', __('pages.job_types_management'))

@section('content')
    <div class="mb-8">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h1 class="text-2xl font-bold text-slate-900 tracking-tight">{{ __('common.edit') }}</h1>
                <p class="text-[14px] text-slate-600 mt-1.5">{{ __('pages.job_types_management_description') }}</p>
            </div>
            <a href="{{ route('admin.masters.job-types') }}" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-lg border border-slate-300 text-slate-700 hover:bg-slate-50 text-[14px] font-medium transition-all">
                <i data-lucide="arrow-left" class="w-4 h-4"></i>
                {{ __('common.back_to_list') }}
            </a>
        </div>
    </div>

    <form method="POST" action="{{ route('admin.masters.job-types.update', $jobType) }}" class="max-w-4xl mx-auto">
        @csrf
        @method('PUT')

        <div class="bg-white border border-slate-200 rounded-xl shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-200 bg-gradient-to-r from-slate-50 to-white">
                <div class="flex items-center gap-3">
                    <div class="h-10 w-10 rounded-lg bg-primary-100 flex items-center justify-center">
                        <i data-lucide="briefcase" class="w-5 h-5 text-primary-600"></i>
                    </div>
                    <div>
                        <h2 class="text-lg font-semibold text-slate-900">{{ __('pages.basic_information') }}</h2>
                        <p class="text-[13px] text-slate-500">{{ __('pages.edit_job_type_information') }}</p>
                    </div>
                </div>
            </div>

            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="ui-label">{{ __('pages.medical_institution') }}</label>
                        <div class="ui-field-icon">
                            <i data-lucide="building-2"></i>
                            <input type="text" value="{{ $jobType->medicalInstitution?->name ?? '-' }}" disabled class="ui-input">
                        </div>
                        <p class="text-[12px] text-slate-500 mt-1.5">{{ __('pages.cannot_be_changed') }}</p>
                    </div>

                    <x-master-code-field
                        type="job_type"
                        name="code"
                        :value="old('code', $jobType->code)"
                        :exclude-id="$jobType->id"
                        :institution-id="$jobType->medical_institution_id"
                        :label="__('pages.job_type_code')"
                    />

                    <div class="md:col-span-2">
                        <label class="ui-label">{{ __('pages.job_type_name') }} <span class="text-red-500">*</span></label>
                        <div class="ui-field-icon">
                            <i data-lucide="briefcase"></i>
                            <input type="text" name="name" value="{{ old('name', $jobType->name) }}" required class="ui-input">
                        </div>
                        @error('name')<p class="ui-error">{{ $message }}</p>@enderror
                    </div>
                </div>
            </div>

            <div class="px-6 py-4 bg-slate-50 border-t border-slate-200 flex items-center justify-between">
                <p class="text-[12px] text-slate-500">{{ __('pages.required_fields_note') }}</p>
                <div class="flex items-center gap-3">
                    <a href="{{ route('admin.masters.job-types') }}" class="ui-btn ui-btn-secondary">{{ __('common.cancel') }}</a>
                    <button type="submit" class="ui-btn ui-btn-primary">{{ __('pages.update') }}</button>
                </div>
            </div>
        </div>
    </form>
@endsection
