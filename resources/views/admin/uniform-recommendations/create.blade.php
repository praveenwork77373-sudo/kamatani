@extends('layouts.app')

@section('title', '推奨／指定ユニフォーム 新規')
@section('page_title', '推奨／指定ユニフォーム 新規')

@section('content')
<div class="ui-page-header">
    <div>
        <h1 class="text-xl font-bold text-slate-900">推奨／指定ユニフォーム 新規登録</h1>
    </div>
</div>
<div class="ui-card p-6">
    @include('admin.uniform-recommendations._form')
</div>
@endsection
