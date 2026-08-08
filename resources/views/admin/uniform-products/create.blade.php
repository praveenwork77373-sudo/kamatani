@extends('layouts.app')

@section('title', __('pages.new_uniform_product') . ' | ' . config('app.name'))
@section('page_title', __('pages.new_uniform_product'))

@section('content')
    @include('admin.uniform-products._form', ['mode' => 'create'])
@endsection
