@extends('layouts.app')

@section('title', __('pages.edit_uniform') . ' | ' . config('app.name'))
@section('page_title', __('pages.edit_uniform'))

@section('content')
    @include('admin.uniform-products._form', ['mode' => 'edit'])
@endsection
