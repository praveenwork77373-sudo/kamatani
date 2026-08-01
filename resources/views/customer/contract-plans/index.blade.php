@extends('layouts.app')

@section('title', '契約情報')
@section('page_title', '契約情報')

@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-bold text-slate-900">契約情報・上限</h1>
    <p class="text-sm text-slate-600 mt-1">{{ $institution->name ?? '' }} — 本登録済みの契約プランと割当状況</p>
</div>

@if($institution)
    <p class="text-sm text-slate-600 mb-4">
        締め日: 毎月 {{ $institution->billing_closing_day ?? 1 }} 日
        @if($institution->sales_person_name)
            ／ 担当セールス: {{ $institution->sales_person_name }}
        @endif
    </p>
@endif

<div class="bg-white border border-slate-200 rounded-xl overflow-hidden">
    <table class="min-w-full text-sm">
        <thead class="bg-slate-50 text-slate-600">
            <tr>
                <th class="px-4 py-3 text-left">プランコード</th>
                <th class="px-4 py-3 text-left">プラン名</th>
                <th class="px-4 py-3 text-right">貸与枚数</th>
                <th class="px-4 py-3 text-right">単価（税抜）</th>
                <th class="px-4 py-3 text-right">契約数</th>
                <th class="px-4 py-3 text-right">割当中（在籍）</th>
            </tr>
        </thead>
        <tbody>
            @forelse($plans as $row)
                @php $assigned = (int)($assignmentCounts[$row->id] ?? 0); @endphp
                <tr class="border-t">
                    <td class="px-4 py-3 font-mono">{{ $row->contractPlan->code ?? '-' }}</td>
                    <td class="px-4 py-3">{{ $row->contractPlan->name ?? '-' }}</td>
                    <td class="px-4 py-3 text-right">{{ $row->contractPlan->rentable_points ?? '-' }}</td>
                    <td class="px-4 py-3 text-right">¥{{ number_format($row->contractPlan->unit_price_ex_tax ?? 0) }}</td>
                    <td class="px-4 py-3 text-right">{{ $row->contract_count }}</td>
                    <td class="px-4 py-3 text-right {{ $assigned > $row->contract_count ? 'text-red-600 font-semibold' : '' }}">
                        {{ $assigned }}
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="px-4 py-8 text-center text-slate-500">本登録の契約プランがありません。カマタニへご連絡ください。</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
