@extends('layouts.app')

@section('title', __('pages.maker_create'))
@section('page_title', __('pages.maker_create'))
@section('header_category', __('menu.makers'))

@section('content')
<div class="max-w-xl mx-auto sm:mx-0">
    <div class="ui-page-header">
        <div>
            <h1 class="text-xl sm:text-2xl font-bold text-slate-900 tracking-tight">{{ __('pages.maker_master') }} {{ __('pages.suffix_create') }}</h1>
            <p class="text-[13px] text-slate-500 mt-1">{{ __('pages.maker_create_desc') }}</p>
        </div>
    </div>

    <form method="POST" action="{{ route('admin.makers.store') }}" class="ui-card ui-form p-4 sm:p-6 space-y-4">
        @csrf
        <x-master-code-field type="maker" name="code" :value="old('code')" :label="__('pages.code')" />
        <div>
            <label class="ui-label" for="name">{{ __('pages.ui_aeef0fe5d6') }} <span class="text-red-500">*</span></label>
            <input id="name" type="text" name="name" value="{{ old('name') }}" required class="ui-input">
            @error('name')<p class="ui-error">{{ $message }}</p>@enderror
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="ui-label" for="tel">{{ __('pages.tel') }}</label>
                <input id="tel" type="text" name="tel" value="{{ old('tel') }}" class="ui-input">
            </div>
            <div>
                <label class="ui-label" for="fax">{{ __('pages.fax') }}</label>
                <input id="fax" type="text" name="fax" value="{{ old('fax') }}" class="ui-input">
            </div>
        </div>
        <div>
            <label class="ui-label" for="notes">{{ __('pages.supplier_notes') }}</label>
            <textarea id="notes" name="notes" rows="2" class="ui-textarea">{{ old('notes') }}</textarea>
        </div>
        <label class="inline-flex items-center gap-2 text-[14px] text-slate-700">
            <input type="checkbox" name="is_active" value="1" class="ui-check" {{ old('is_active', true) ? 'checked' : '' }}>
            有効
        </label>
        <div class="flex flex-col-reverse sm:flex-row gap-3 pt-2">
            <a href="{{ route('admin.makers.index') }}" class="ui-btn ui-btn-secondary">{{ __('common.back') }}</a>
            <button type="submit" class="ui-btn ui-btn-primary">{{ __('common.save') }}</button>
        </div>
    </form>
</div>
@endsection
