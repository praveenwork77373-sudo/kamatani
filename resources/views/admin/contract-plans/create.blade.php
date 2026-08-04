@extends('layouts.app')

@section('title', __('pages.ui_11d7630f25'))
@section('page_title', __('pages.ui_11d7630f25'))

@section('content')
<div class="max-w-3xl">
    <h1 class="text-2xl font-bold text-slate-900 mb-6">{{ __('pages.ui_11d7630f25') }}</h1>
    @include('admin.contract-plans._form')
</div>
@endsection
