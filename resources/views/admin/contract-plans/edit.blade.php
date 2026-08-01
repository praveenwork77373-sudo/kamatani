@extends('layouts.app')

@section('title', '契約プラン編集')
@section('page_title', '契約プラン編集')

@section('content')
<div class="max-w-3xl">
    <h1 class="text-2xl font-bold text-slate-900 mb-6">契約プラン編集</h1>
    @include('admin.contract-plans._form')
</div>
@endsection
