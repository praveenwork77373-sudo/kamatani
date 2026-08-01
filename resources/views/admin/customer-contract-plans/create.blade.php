@extends('layouts.app')

@section('title', '顧客契約プラン登録')
@section('page_title', '顧客契約プラン登録')

@section('content')
<div class="max-w-xl">
    <h1 class="text-2xl font-bold mb-6">顧客契約プラン登録</h1>
    @include('admin.customer-contract-plans._form')
</div>
@endsection
