@extends('layouts.app')

@section('title', __('pages.departments_management') . ' | ' . config('app.name'))
@section('page_title', __('pages.departments_management'))

@section('content')
    <div class="mb-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-slate-900 tracking-tight">{{ __('pages.departments_management') }}</h1>
                <p class="text-[14px] text-slate-600 mt-1.5">{{ __('pages.departments_management_description') }}</p>
            </div>
            <a href="{{ route('customer.masters.departments') }}" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-lg border border-slate-300 text-slate-700 hover:bg-slate-50 text-[14px] font-medium transition-all">
                <i data-lucide="arrow-left" class="w-4 h-4"></i>
                {{ __('common.back_to_list') }}
            </a>
        </div>
    </div>

    <form method="POST" action="{{ route('customer.masters.departments.store') }}">
        @csrf

        <div class="bg-white border border-slate-200 rounded-xl shadow-sm overflow-hidden">
            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <x-master-code-field
                        type="department"
                        name="code"
                        :value="old('code')"
                        :institution-id="auth()->user()->customerUser->medical_institution_id"
                        :label="__('pages.department_code')"
                    />

                    <div class="md:col-span-2">
                        <label class="block text-slate-700 text-[14px] font-semibold mb-2 flex items-center gap-2">
                            <i data-lucide="briefcase" class="w-4 h-4 text-primary-600"></i>
                            {{ __('pages.department_name') }} <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="name" value="{{ old('name') }}" required
                               class="w-full px-4 py-2.5 rounded-lg border border-slate-300 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 text-[14px] bg-white shadow-sm transition-all"
                               placeholder="{{ __('pages.example_surgery') }}">
                        @error('name')
                            <p class="text-[13px] text-red-600 mt-1.5 flex items-center gap-1">
                                <i data-lucide="alert-circle" class="w-4 h-4"></i>
                                {{ $message }}
                            </p>
                        @enderror
                    </div>
                </div>
            </div>

            <div class="p-6 bg-slate-50 flex items-center justify-end gap-3">
                <a href="{{ route('customer.masters.departments') }}" class="px-5 py-2.5 rounded-lg border border-slate-300 text-slate-700 hover:bg-white text-[14px] font-medium transition-all">
                    {{ __('common.cancel') }}
                </a>
                <button type="submit" class="px-6 py-2.5 rounded-lg bg-primary-600 hover:bg-primary-700 text-white text-[14px] font-semibold shadow-md transition-all flex items-center gap-2">
                    <i data-lucide="check" class="w-4 h-4"></i>
                    {{ __('pages.register') }}
                </button>
            </div>
        </div>
    </form>

    <script>
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
    </script>
@endsection

