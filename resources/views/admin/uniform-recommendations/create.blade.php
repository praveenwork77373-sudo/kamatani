@extends('layouts.app')

@section('title', __('pages.ui_5495aedc87'))
@section('page_title', __('pages.ui_5495aedc87'))

@section('content')
<div class="ui-page-header">
    <div>
        <h1 class="text-xl font-bold text-slate-900">{{ __('pages.ui_28d5144559') }}</h1>
    </div>
</div>
<div class="ui-card p-6">
    @include('admin.uniform-recommendations._form')
</div>
@endsection
