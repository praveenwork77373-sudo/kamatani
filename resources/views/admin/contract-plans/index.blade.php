@extends('layouts.app')

@section('title', '契約プランマスタ')
@section('page_title', '契約プランマスタ')

@section('content')
@if(session('status'))
    <div class="mb-4 p-3 bg-emerald-50 border border-emerald-200 rounded-lg text-emerald-800 text-sm">{{ session('status') }}</div>
@endif

<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-slate-900">契約プランマスタ</h1>
        <p class="text-sm text-slate-600 mt-1">貸与枚数・単価・適用可能商品を定義します</p>
    </div>
    <a href="{{ route('admin.contract-plans.create') }}" class="px-4 py-2 rounded-lg bg-primary-600 text-white text-sm font-semibold">新規登録</a>
</div>

<div class="bg-white border border-slate-200 rounded-xl overflow-hidden">
    <table class="min-w-full text-sm">
        <thead class="bg-slate-50 text-slate-600">
            <tr>
                <th class="px-4 py-3 text-left">コード</th>
                <th class="px-4 py-3 text-left">プラン名</th>
                <th class="px-4 py-3 text-right">貸与枚数</th>
                <th class="px-4 py-3 text-right">単価（税抜）</th>
                <th class="px-4 py-3 text-right">商品数</th>
                <th class="px-4 py-3 text-left">状態</th>
                <th class="px-4 py-3 text-right">操作</th>
            </tr>
        </thead>
        <tbody>
            @forelse($plans as $plan)
                <tr class="border-t border-slate-100">
                    <td class="px-4 py-3 font-mono">{{ $plan->code }}</td>
                    <td class="px-4 py-3">{{ $plan->name }}</td>
                    <td class="px-4 py-3 text-right">{{ $plan->rentable_points }}</td>
                    <td class="px-4 py-3 text-right">{{ number_format($plan->unit_price_ex_tax) }}</td>
                    <td class="px-4 py-3 text-right">{{ $plan->uniform_products_count }}</td>
                    <td class="px-4 py-3">{{ $plan->is_active ? '有効' : '無効' }}</td>
                    <td class="px-4 py-3 text-right space-x-2">
                        <a href="{{ route('admin.contract-plans.show', $plan) }}" class="text-slate-600 hover:underline">詳細</a>
                        <a href="{{ route('admin.contract-plans.edit', $plan) }}" class="text-primary-600 hover:underline">編集</a>
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" class="px-4 py-8 text-center text-slate-500">契約プランがありません</td></tr>
            @endforelse
        </tbody>
    </table>
    <x-table-pagination :paginator="$plans" />
</div>

@endsection
