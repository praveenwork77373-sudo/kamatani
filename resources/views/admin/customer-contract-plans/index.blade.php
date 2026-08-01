@extends('layouts.app')

@section('title', '顧客契約プラン')
@section('page_title', '顧客契約プラン')

@section('content')
@if(session('status'))
    <div class="mb-4 p-3 bg-emerald-50 border border-emerald-200 rounded-lg text-emerald-800 text-sm">{{ session('status') }}</div>
@endif

<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-slate-900">顧客契約プラン</h1>
        <p class="text-sm text-slate-600 mt-1">医療機関ごとの契約プラン・契約数（仮登録／本登録）</p>
    </div>
    <a href="{{ route('admin.customer-contract-plans.create') }}" class="px-4 py-2 rounded-lg bg-primary-600 text-white text-sm font-semibold">新規登録</a>
</div>

<form method="GET" class="mb-4 flex gap-2 items-end">
    <div>
        <label class="block text-xs text-slate-500 mb-1">医療機関</label>
        <select name="medical_institution_id" class="rounded-lg border-slate-300 text-sm">
            <option value="">すべて</option>
            @foreach($institutions as $inst)
                <option value="{{ $inst->id }}" @selected((string)$selectedInstitutionId === (string)$inst->id)>{{ $inst->name }}</option>
            @endforeach
        </select>
    </div>
    <button class="px-3 py-2 rounded-lg border text-sm">絞り込み</button>
</form>

<div class="bg-white border border-slate-200 rounded-xl overflow-hidden">
    <table class="min-w-full text-sm">
        <thead class="bg-slate-50 text-slate-600">
            <tr>
                <th class="px-4 py-3 text-left">医療機関</th>
                <th class="px-4 py-3 text-left">プラン</th>
                <th class="px-4 py-3 text-right">契約数</th>
                <th class="px-4 py-3 text-left">期間</th>
                <th class="px-4 py-3 text-left">状態</th>
                <th class="px-4 py-3 text-right">操作</th>
            </tr>
        </thead>
        <tbody>
            @forelse($plans as $row)
                <tr class="border-t">
                    <td class="px-4 py-3">{{ $row->medicalInstitution->name ?? '-' }}</td>
                    <td class="px-4 py-3">
                        <span class="font-mono text-xs">{{ $row->contractPlan->code ?? '' }}</span>
                        {{ $row->contractPlan->name ?? '' }}
                    </td>
                    <td class="px-4 py-3 text-right">{{ $row->contract_count }}</td>
                    <td class="px-4 py-3 text-xs">
                        {{ optional($row->start_date)->format('Y-m-d') ?? '-' }}
                        〜
                        {{ optional($row->end_date)->format('Y-m-d') ?? '-' }}
                    </td>
                    <td class="px-4 py-3">
                        @if($row->status === 'confirmed')
                            <span class="text-emerald-700 font-medium">本登録</span>
                        @else
                            <span class="text-amber-700 font-medium">仮登録</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-right space-x-2">
                        @if($row->status === 'draft')
                            <form method="POST" action="{{ route('admin.customer-contract-plans.confirm', $row) }}" class="inline">
                                @csrf
                                <button class="text-emerald-600 hover:underline">本登録</button>
                            </form>
                        @endif
                        <a href="{{ route('admin.customer-contract-plans.edit', $row) }}" class="text-primary-600 hover:underline">編集</a>
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="px-4 py-8 text-center text-slate-500">データがありません</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
<div class="mt-4">{{ $plans->links() }}</div>
@endsection
