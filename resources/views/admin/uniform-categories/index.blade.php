@extends('layouts.app')

@section('title', 'ユニフォームカテゴリ')
@section('page_title', 'ユニフォームカテゴリ')
@section('header_category', __('menu.uniform_categories'))

@section('content')
<div class="ui-page-header">
    <div>
        <h1 class="text-xl sm:text-2xl font-bold text-slate-900 tracking-tight">{{ __('menu.uniform_categories') }}</h1>
        <p class="text-[13px] text-slate-600 mt-1">{{ __('pages.uniform_categories_description') }}</p>
    </div>
    <a href="{{ route('admin.uniform-categories.create') }}" class="ui-btn ui-btn-primary">新規登録</a>
</div>

@if(session('status'))
    <div class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-[13px] text-emerald-800">{{ session('status') }}</div>
@endif

<div class="ui-card overflow-hidden">
    <div class="ui-table-wrap">
        <table class="min-w-full text-sm">
            <thead class="bg-slate-50 text-slate-600">
                <tr>
                    <th class="px-4 py-3 text-left whitespace-nowrap">{{ __('pages.code') }}</th>
                    <th class="px-4 py-3 text-left whitespace-nowrap">{{ __('pages.name') }}</th>
                    <th class="px-4 py-3 text-left whitespace-nowrap">{{ __('pages.size_scheme') }}</th>
                    <th class="px-4 py-3 text-right whitespace-nowrap">{{ __('pages.sort_order') }}</th>
                    <th class="px-4 py-3 text-left whitespace-nowrap">状態</th>
                    <th class="px-4 py-3 text-right whitespace-nowrap">操作</th>
                </tr>
            </thead>
            <tbody>
                @forelse($categories as $category)
                    <tr class="border-t border-slate-100">
                        <td class="px-4 py-3 font-mono">{{ $category->code }}</td>
                        <td class="px-4 py-3">{{ $category->name }}</td>
                        <td class="px-4 py-3">{{ __('pages.size_scheme_'.$category->size_scheme) }}</td>
                        <td class="px-4 py-3 text-right">{{ $category->sort_order }}</td>
                        <td class="px-4 py-3">
                            @if($category->is_active)
                                <span class="text-emerald-700">有効</span>
                            @else
                                <span class="text-slate-400">無効</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right space-x-3">
                            <a href="{{ route('admin.uniform-categories.edit', $category) }}" class="text-primary-600 hover:underline font-medium">編集</a>
                            <form method="POST" action="{{ route('admin.uniform-categories.destroy', $category) }}" class="inline" onsubmit="return confirm('無効化または削除しますか？');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-600 hover:underline font-medium">削除</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-8 text-center text-slate-500">カテゴリがありません</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <x-table-pagination :paginator="$categories" />
</div>
@endsection
