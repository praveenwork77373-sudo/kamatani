@extends('layouts.app')

@section('title', '受注一覧 | ' . __('auth.system_title'))
@section('page_title', '受注一覧')

@section('content')
@if(session('status'))
    <div class="mb-4 p-3 bg-emerald-50 border border-emerald-200 rounded-lg text-emerald-800 text-sm">{{ session('status') }}</div>
@endif

<div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
    <div>
        <h2 class="text-xl font-bold text-slate-900 mb-1">受注一覧</h2>
        <p class="text-[14px] text-slate-500">対応中／完了の申請（受付済み）から帳票PDFを発行・確認できます。</p>
    </div>
    <a href="{{ route('admin.applications.index') }}" class="text-[13px] text-primary-600 hover:underline font-medium">申請一覧へ</a>
</div>

<form method="GET" class="mb-4 flex flex-wrap gap-2 items-end">
    <x-per-page-field />
    <div>
        <label class="block text-xs text-slate-500 mb-1">医療機関</label>
        <select name="institution_id" class="rounded-lg border-slate-300 text-sm">
            <option value="">すべて</option>
            @foreach($institutions as $inst)
                <option value="{{ $inst->id }}" @selected((string) request('institution_id') === (string) $inst->id)>{{ $inst->name }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="block text-xs text-slate-500 mb-1">ステータス</label>
        <select name="status" class="rounded-lg border-slate-300 text-sm">
            <option value="">すべて</option>
            <option value="in_progress" @selected(request('status') === 'in_progress')>対応中</option>
            <option value="completed" @selected(request('status') === 'completed')>完了</option>
        </select>
    </div>
    <button class="px-3 py-2 rounded-lg border text-sm">絞り込み</button>
</form>

<form method="POST" action="{{ route('admin.applications.documents.bulk_issue') }}">
    @csrf
    <div class="mb-3">
        <button type="submit" class="inline-flex items-center gap-2 px-3 py-2 rounded-lg bg-primary-600 text-white text-[13px] font-semibold">一括帳票発行</button>
    </div>

    <div class="bg-white border border-slate-200 rounded-xl overflow-hidden">
        <table class="min-w-full text-sm">
            <thead class="bg-slate-50 text-slate-600">
                <tr>
                    <th class="px-3 py-3"><input type="checkbox" id="orders-select-all" class="rounded"></th>
                    <th class="px-4 py-3 text-left">申請ID</th>
                    <th class="px-4 py-3 text-left">種別</th>
                    <th class="px-4 py-3 text-left">医療機関</th>
                    <th class="px-4 py-3 text-left">職員</th>
                    <th class="px-4 py-3 text-left">受付日</th>
                    <th class="px-4 py-3 text-left">状態</th>
                    <th class="px-4 py-3 text-left">帳票</th>
                    <th class="px-4 py-3 text-right">操作</th>
                </tr>
            </thead>
            <tbody>
                @forelse($applications as $app)
                    <tr class="border-t border-slate-100">
                        <td class="px-3 py-3"><input type="checkbox" name="application_ids[]" value="{{ $app->id }}" class="order-check rounded"></td>
                        <td class="px-4 py-3 font-mono">#APP-{{ str_pad($app->id, 4, '0', STR_PAD_LEFT) }}</td>
                        <td class="px-4 py-3">{{ __('pages.'.$app->type) === 'pages.'.$app->type ? $app->type : __('pages.'.$app->type) }}</td>
                        <td class="px-4 py-3">{{ $app->medicalInstitution?->name }}</td>
                        <td class="px-4 py-3">{{ $app->employee?->name }} ({{ $app->employee?->staff_no }})</td>
                        <td class="px-4 py-3">{{ optional($app->accepted_at)->format('Y-m-d H:i') ?? '—' }}</td>
                        <td class="px-4 py-3">{{ $app->statusLabel() }}</td>
                        <td class="px-4 py-3">{{ $app->documents->count() }}件</td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('admin.applications.show', $app) }}" class="text-primary-600 hover:underline">詳細</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="9" class="px-4 py-10 text-center text-slate-500">受注データがありません。</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</form>

<div class="mt-4">{{ $applications->links() }}</div>

@push('scripts')
<script>
document.getElementById('orders-select-all')?.addEventListener('change', function () {
    document.querySelectorAll('.order-check').forEach(cb => { cb.checked = this.checked; });
});
</script>
@endpush
@endsection
