@extends('layouts.app')

@section('title', '推奨／指定ユニフォーム')
@section('page_title', '推奨／指定ユニフォーム')

@section('content')
@if(session('status'))
    <div class="mb-4 p-3 bg-emerald-50 border border-emerald-200 rounded-lg text-emerald-800 text-sm">{{ session('status') }}</div>
@endif

<div class="ui-page-header">
    <div>
        <h1 class="text-xl sm:text-2xl font-bold text-slate-900 tracking-tight">推奨／指定ユニフォーム</h1>
        <p class="text-[13px] text-slate-600 mt-1">部署・職種ごとの推奨／指定設定（申請可否は契約プランが判定）</p>
    </div>
    <a href="{{ route('admin.uniform-recommendations.create') }}" class="ui-btn ui-btn-primary">新規登録</a>
</div>

<form method="GET" class="mb-4 flex flex-wrap gap-2 items-end">
    <x-per-page-field />
    <div>
        <label class="block text-xs text-slate-500 mb-1">医療機関</label>
        <select name="medical_institution_id" class="rounded-lg border-slate-300 text-sm">
            <option value="">すべて</option>
            @foreach($institutions as $inst)
                <option value="{{ $inst->id }}" @selected((string) request('medical_institution_id') === (string) $inst->id)>{{ $inst->name }}</option>
            @endforeach
        </select>
    </div>
    <button class="px-3 py-2 rounded-lg border text-sm">絞り込み</button>
</form>

<div class="ui-card overflow-hidden">
    <div class="ui-table-wrap">
        <table class="min-w-full text-sm">
            <thead class="bg-slate-50 text-slate-600">
                <tr>
                    <th class="px-4 py-3 text-left">医療機関</th>
                    <th class="px-4 py-3 text-left">部署</th>
                    <th class="px-4 py-3 text-left">職種</th>
                    <th class="px-4 py-3 text-left">商品</th>
                    <th class="px-4 py-3 text-left">区分</th>
                    <th class="px-4 py-3 text-right">操作</th>
                </tr>
            </thead>
            <tbody>
                @forelse($recommendations as $row)
                    <tr class="border-t border-slate-100">
                        <td class="px-4 py-3">{{ $row->medicalInstitution?->name }}</td>
                        <td class="px-4 py-3">{{ $row->department?->name ?? '—' }}</td>
                        <td class="px-4 py-3">{{ $row->jobType?->name ?? '—' }}</td>
                        <td class="px-4 py-3">
                            <div class="font-mono text-[12px] text-slate-500">{{ $row->uniformProduct?->product_code }}</div>
                            <div>{{ $row->uniformProduct?->name }} ({{ $row->uniformProduct?->size }})</div>
                        </td>
                        <td class="px-4 py-3">
                            @if($row->is_required)
                                <span class="text-amber-700 font-medium">指定</span>
                            @endif
                            @if($row->is_recommended)
                                <span class="text-sky-700 font-medium">推奨</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right whitespace-nowrap">
                            <a href="{{ route('admin.uniform-recommendations.edit', $row) }}" class="text-primary-600 hover:underline">編集</a>
                            <form method="POST" action="{{ route('admin.uniform-recommendations.destroy', $row) }}" class="inline" onsubmit="return confirm('削除しますか？')">
                                @csrf
                                @method('DELETE')
                                <button class="text-red-600 hover:underline ml-2">削除</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-8 text-center text-slate-500">登録がありません。</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="p-4">{{ $recommendations->links() }}</div>
</div>
@endsection
