@extends('layouts.app')

@section('title', '管理: 推奨・必須ユニフォーム設定一覧 | ユニフォーム管理システム')
@section('page_title', '推奨・必須ユニフォーム設定')

@section('content')
    <div class="mb-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-slate-900 tracking-tight">推奨・必須ユニフォーム設定一覧</h1>
                <p class="text-[14px] text-slate-600 mt-1.5">
                    医療機関・部署・職種ごとの推奨／必須ユニフォーム（上衣・ズボン・シューズなど）を管理します。
                </p>
            </div>
            <a href="{{ route('admin.uniform-recommendations.create') }}"
               class="inline-flex items-center gap-2 px-4 py-2.5 rounded-lg bg-primary-600 hover:bg-primary-700 text-white text-[14px] font-medium shadow-md transition-all">
                <i class="ph ph-plus text-base"></i>
                新規登録
            </a>
        </div>
    </div>

    @if(session('status'))
        <div class="mb-6 p-4 bg-emerald-50 border-l-4 border-emerald-500 rounded-lg">
            <div class="flex items-center gap-2">
                <i class="ph ph-check-circle text-lg text-emerald-600"></i>
                <span class="text-[14px] text-emerald-800 font-medium">{{ session('status') }}</span>
            </div>
        </div>
    @endif

    <div class="bg-white border border-slate-200 rounded-xl shadow-sm overflow-hidden">
        <div class="p-4 border-b border-slate-200">
            <form method="GET" action="{{ route('admin.uniform-recommendations.index') }}" class="flex items-center gap-3">
                <div>
                    <label class="block text-[12px] font-semibold text-slate-600 mb-1">医療機関</label>
                    <select name="medical_institution_id"
                            class="px-4 py-2.5 rounded-lg border border-slate-300 text-[14px] focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20">
                        <option value="">すべて</option>
                        @foreach($institutions as $institution)
                            <option value="{{ $institution->id }}"
                                @selected($selectedInstitutionId == $institution->id)>
                                {{ $institution->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="pt-5">
                    <button type="submit"
                            class="px-5 py-2.5 rounded-lg bg-primary-600 hover:bg-primary-700 text-white text-[14px] font-medium shadow-md transition-all">
                        絞り込む
                    </button>
                </div>
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full text-[14px]">
                <thead class="bg-slate-50 border-b border-slate-200">
                    <tr>
                        <th class="px-5 py-3.5 text-left font-semibold text-slate-700">医療機関</th>
                        <th class="px-5 py-3.5 text-left font-semibold text-slate-700">部署</th>
                        <th class="px-5 py-3.5 text-left font-semibold text-slate-700">職種</th>
                        <th class="px-5 py-3.5 text-left font-semibold text-slate-700">ユニフォーム</th>
                        <th class="px-5 py-3.5 text-left font-semibold text-slate-700">種別</th>
                        <th class="px-5 py-3.5 text-right font-semibold text-slate-700">操作</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($recommendations as $rec)
                        <tr class="hover:bg-slate-50/50 transition-colors">
                            <td class="px-5 py-4 text-slate-800">
                                {{ $rec->medicalInstitution->name ?? '-' }}
                            </td>
                            <td class="px-5 py-4 text-slate-800">
                                {{ $rec->department->name ?? '（全部署）' }}
                            </td>
                            <td class="px-5 py-4 text-slate-800">
                                {{ $rec->jobType->name ?? '（全職種）' }}
                            </td>
                            <td class="px-5 py-4 text-slate-800">
                                <div class="flex items-center gap-3">
                                    @if($rec->uniformProduct->image_path)
                                        <div class="w-10 h-10 rounded-lg border border-slate-200 overflow-hidden bg-slate-50 flex items-center justify-center">
                                            <img src="{{ asset('storage/'.$rec->uniformProduct->image_path) }}" alt="{{ $rec->uniformProduct->name }}" class="w-full h-full object-cover">
                                        </div>
                                    @else
                                        <div class="w-10 h-10 rounded-lg border border-dashed border-slate-300 flex items-center justify-center text-slate-300">
                                            <i class="ph ph-image text-lg"></i>
                                        </div>
                                    @endif
                                    <div>
                                        <div class="font-semibold text-slate-900">
                                            {{ $rec->uniformProduct->name }}
                                        </div>
                                        <div class="text-[12px] text-slate-500">
                                            {{ $rec->uniformProduct->product_code }} ／ サイズ: {{ $rec->uniformProduct->size }}
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-5 py-4">
                                <div class="flex flex-wrap gap-1.5">
                                    @if($rec->is_required)
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-semibold bg-red-50 text-red-700 border border-red-200">
                                            必須
                                        </span>
                                    @endif
                                    @if($rec->is_recommended)
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-semibold bg-blue-50 text-blue-700 border border-blue-200">
                                            推奨
                                        </span>
                                    @endif
                                    @if(!$rec->is_required && !$rec->is_recommended)
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-semibold bg-slate-50 text-slate-600 border border-slate-200">
                                            任意
                                        </span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-5 py-4 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('admin.uniform-recommendations.edit', $rec) }}"
                                       class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-lg border border-slate-300 text-slate-700 hover:bg-slate-50 hover:border-slate-400 text-[13px] font-medium transition-all">
                                        <i class="ph ph-pencil text-sm"></i>
                                        編集
                                    </a>
                                    <form method="POST" action="{{ route('admin.uniform-recommendations.destroy', $rec) }}"
                                          class="inline"
                                          onsubmit="return confirm('この設定を削除しますか？');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                                class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-lg border border-red-300 text-red-700 hover:bg-red-50 hover:border-red-400 text-[13px] font-medium transition-all">
                                            <i class="ph ph-trash text-sm"></i>
                                            削除
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-12 text-center">
                                <div class="flex flex-col items-center gap-3">
                                    <i class="ph ph-t-shirt text-4xl text-slate-300"></i>
                                    <p class="text-slate-500 text-[14px]">
                                        まだ推奨・必須ユニフォーム設定が登録されていません。
                                    </p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($recommendations->hasPages())
            <div class="px-5 py-4 border-t border-slate-200 flex items-center justify-between text-[13px] text-slate-600">
                <span>
                    全 {{ $recommendations->total() }} 件中
                    {{ $recommendations->firstItem() ?? 0 }} - {{ $recommendations->lastItem() ?? 0 }} 件を表示
                </span>
                <div class="flex items-center gap-2">
                    {{ $recommendations->onEachSide(1)->links('pagination::tailwind') }}
                </div>
            </div>
        @endif
    </div>
@endsection


