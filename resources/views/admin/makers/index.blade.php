@extends('layouts.app')

@section('title', 'メーカーマスタ')
@section('page_title', 'メーカーマスタ')

@section('content')
<div class="ui-page-header">
    <div>
        <h1 class="text-xl sm:text-2xl font-bold text-slate-900 tracking-tight">メーカーマスタ</h1>
        <p class="text-[13px] text-slate-600 mt-1">商品に紐づけるメーカーを管理します</p>
    </div>
    <a href="{{ route('admin.makers.create') }}" class="ui-btn ui-btn-primary">新規登録</a>
</div>

<div class="ui-card overflow-hidden">
    <div class="ui-table-wrap">
        <table class="min-w-full text-sm">
            <thead class="bg-slate-50 text-slate-600">
                <tr>
                    <th class="px-4 py-3 text-left whitespace-nowrap">コード</th>
                    <th class="px-4 py-3 text-left whitespace-nowrap">名称</th>
                    <th class="px-4 py-3 text-left whitespace-nowrap">状態</th>
                    <th class="px-4 py-3 text-right whitespace-nowrap">操作</th>
                </tr>
            </thead>
            <tbody>
                @forelse($makers as $maker)
                    <tr class="border-t border-slate-100">
                        <td class="px-4 py-3 font-mono">{{ $maker->code }}</td>
                        <td class="px-4 py-3">{{ $maker->name }}</td>
                        <td class="px-4 py-3">
                            @if($maker->is_active)
                                <span class="text-emerald-700">有効</span>
                            @else
                                <span class="text-slate-400">無効</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('admin.makers.edit', $maker) }}" class="text-primary-600 hover:underline font-medium">編集</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="px-4 py-8 text-center text-slate-500">メーカーがありません</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-4">{{ $makers->links() }}</div>
@endsection
