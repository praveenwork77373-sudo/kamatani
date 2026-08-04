@extends('layouts.app')

@section('title', __('pages.category_create'))
@section('page_title', __('pages.category_create'))
@section('header_category', __('menu.uniform_categories'))

@section('content')
<div class="max-w-xl mx-auto sm:mx-0">
    <div class="ui-page-header">
        <div>
            <h1 class="text-xl sm:text-2xl font-bold text-slate-900 tracking-tight">{{ __('menu.uniform_categories') }} {{ __('pages.suffix_create') }}</h1>
            <p class="text-[13px] text-slate-500 mt-1">{{ __('pages.uniform_categories_description') }}</p>
        </div>
    </div>

    <form method="POST" action="{{ route('admin.uniform-categories.store') }}" class="ui-card ui-form p-4 sm:p-6 space-y-4">
        @csrf
        <x-master-code-field type="uniform_category" name="code" :value="old('code')" :label="__('pages.code')" />
        @include('admin.uniform-categories._form')
        <div class="flex flex-col-reverse sm:flex-row gap-3 pt-2">
            <a href="{{ route('admin.uniform-categories.index') }}" class="ui-btn ui-btn-secondary">{{ __('common.back') }}</a>
            <button type="submit" class="ui-btn ui-btn-primary">{{ __('common.save') }}</button>
        </div>
    </form>
</div>
@endsection
