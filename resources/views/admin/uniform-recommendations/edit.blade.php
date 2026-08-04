@extends('layouts.app')

@section('title', __('pages.rec_edit'))
@section('page_title', __('pages.rec_edit'))

@section('content')
<div class="ui-page-header">
    <div>
        <h1 class="text-xl font-bold text-slate-900">{{ __('pages.rec_edit') }}</h1>
    </div>
</div>
<div class="ui-card p-6">
    @include('admin.uniform-recommendations._form')
</div>
@endsection
