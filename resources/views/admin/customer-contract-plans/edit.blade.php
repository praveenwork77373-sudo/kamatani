@extends('layouts.app')

@section('title', __('pages.ccp_edit'))
@section('page_title', __('pages.ccp_edit'))

@section('content')
<div class="max-w-xl">
    <h1 class="text-2xl font-bold mb-6">{{ __('pages.ccp_edit') }}</h1>
    @include('admin.customer-contract-plans._form')
</div>
@endsection
