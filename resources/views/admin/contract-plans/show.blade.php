@extends('layouts.app')

@section('title', '契約プラン詳細')
@section('page_title', '契約プラン詳細')

@section('content')
<div class="mb-6 flex items-center justify-between">
    <div>
        <h1 class="text-2xl font-bold text-slate-900">{{ $plan->name }}</h1>
        <p class="text-sm text-slate-600 mt-1 font-mono">{{ $plan->code }}</p>
    </div>
    <a href="{{ route('admin.contract-plans.edit', $plan) }}" class="px-4 py-2 rounded-lg bg-primary-600 text-white text-sm font-semibold">編集</a>
</div>

<div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
    <div class="bg-white border rounded-xl p-4"><p class="text-xs text-slate-500">貸与枚数</p><p class="text-2xl font-bold">{{ $plan->rentable_points }}</p></div>
    <div class="bg-white border rounded-xl p-4"><p class="text-xs text-slate-500">単価（税抜）</p><p class="text-2xl font-bold">¥{{ number_format($plan->unit_price_ex_tax) }}</p></div>
    <div class="bg-white border rounded-xl p-4"><p class="text-xs text-slate-500">状態</p><p class="text-2xl font-bold">{{ $plan->is_active ? '有効' : '無効' }}</p></div>
</div>

<div class="bg-white border rounded-xl overflow-hidden">
    <div class="px-4 py-3 border-b font-semibold text-sm">適用可能ユニフォーム</div>
    <table class="min-w-full text-sm">
        <thead class="bg-slate-50"><tr>
            <th class="px-4 py-2 text-left">商品コード</th>
            <th class="px-4 py-2 text-left">名称</th>
            <th class="px-4 py-2 text-left">サイズ</th>
        </tr></thead>
        <tbody>
            @forelse($plan->uniformProducts as $p)
                <tr class="border-t"><td class="px-4 py-2 font-mono">{{ $p->product_code }}</td><td class="px-4 py-2">{{ $p->name }}</td><td class="px-4 py-2">{{ $p->size }}</td></tr>
            @empty
                <tr><td colspan="3" class="px-4 py-6 text-center text-slate-500">商品未設定</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
