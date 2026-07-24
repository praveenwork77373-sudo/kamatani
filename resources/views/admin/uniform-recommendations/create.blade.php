@extends('layouts.app')

@section('title', '管理: 推奨・必須ユニフォーム設定登録 | ユニフォーム管理システム')
@section('page_title', '推奨・必須ユニフォーム設定登録')

@section('content')
    <div class="mb-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-slate-900 tracking-tight">推奨・必須ユニフォーム設定登録</h1>
                <p class="text-[14px] text-slate-600 mt-1.5">
                    医療機関・部署・職種ごとに、貸与対象となるユニフォーム（上衣・ズボン・シューズなど）の推奨／必須設定を行います。
                </p>
            </div>
            <a href="{{ route('admin.uniform-recommendations.index') }}"
               class="inline-flex items-center gap-2 px-4 py-2.5 rounded-lg border border-slate-300 text-slate-700 hover:bg-slate-50 text-[14px] font-medium transition-all">
                <i class="ph ph-arrow-left text-base"></i>
                一覧に戻る
            </a>
        </div>
    </div>

    <form id="product-form" method="POST" action="{{ route('admin.uniform-recommendations.store') }}">
        @csrf

        <div class="bg-white border border-slate-200 rounded-xl shadow-sm overflow-hidden">
            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-slate-700 text-[14px] font-semibold mb-2">
                            医療機関 <span class="text-red-500">*</span>
                        </label>
                        <select name="medical_institution_id" id="institution-select" required
                                class="w-full px-4 py-2.5 rounded-lg border border-slate-300 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 text-[14px] bg-white shadow-sm transition-all">
                            <option value="">選択してください</option>
                            @foreach($institutions as $institution)
                                <option value="{{ $institution->id }}" @selected(old('medical_institution_id') == $institution->id)>
                                    {{ $institution->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('medical_institution_id')
                            <p class="text-[13px] text-red-600 mt-1.5 flex items-center gap-1">
                                <i class="ph ph-warning text-xs"></i>
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-slate-700 text-[14px] font-semibold mb-2">
                            部署
                        </label>
                        <select name="department_id" id="department-select"
                                data-placeholder="（全部署に適用）"
                                class="w-full px-4 py-2.5 rounded-lg border border-slate-300 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 text-[14px] bg-white shadow-sm transition-all">
                            <option value="">（全部署に適用）</option>
                            @foreach($departments as $department)
                                <option value="{{ $department->id }}" @selected(old('department_id') == $department->id)>
                                    {{ $department->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('department_id')
                            <p class="text-[13px] text-red-600 mt-1.5 flex items-center gap-1">
                                <i class="ph ph-warning text-xs"></i>
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-slate-700 text-[14px] font-semibold mb-2">
                            職種
                        </label>
                        <select name="job_type_id" id="job-type-select"
                                data-placeholder="（全職種に適用）"
                                class="w-full px-4 py-2.5 rounded-lg border border-slate-300 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 text-[14px] bg-white shadow-sm transition-all">
                            <option value="">（全職種に適用）</option>
                            @foreach($jobTypes as $jobType)
                                <option value="{{ $jobType->id }}" @selected(old('job_type_id') == $jobType->id)>
                                    {{ $jobType->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('job_type_id')
                            <p class="text-[13px] text-red-600 mt-1.5 flex items-center gap-1">
                                <i class="ph ph-warning text-xs"></i>
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-slate-700 text-[14px] font-semibold mb-2">
                            ユニフォーム商品 <span class="text-red-500">*</span>
                        </label>
                        <select name="uniform_product_id" required
                                class="w-full px-4 py-2.5 rounded-lg border border-slate-300 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 text-[14px] bg-white shadow-sm transition-all">
                            <option value="">選択してください</option>
                            @foreach($products as $product)
                                <option value="{{ $product->id }}" @selected(old('uniform_product_id') == $product->id)>
                                    {{ $product->product_code }} ／ {{ $product->name }} ／ サイズ: {{ $product->size }}
                                </option>
                            @endforeach
                        </select>
                        @error('uniform_product_id')
                            <p class="text-[13px] text-red-600 mt-1.5 flex items-center gap-1">
                                <i class="ph ph-warning text-xs"></i>
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-slate-700 text-[14px] font-semibold mb-2">
                            種別
                        </label>
                        <div class="flex flex-wrap items-center gap-4 bg-slate-50 border border-slate-200 rounded-lg px-4 py-3">
                            <label class="inline-flex items-center gap-2 text-[14px] text-slate-800">
                                <input type="checkbox" name="is_required" value="1"
                                       class="rounded border-slate-300 text-primary-600 focus:ring-primary-500"
                                       @checked(old('is_required'))>
                                <span class="font-semibold">必須（必ず貸与するアイテム）</span>
                            </label>
                            <label class="inline-flex items-center gap-2 text-[14px] text-slate-800">
                                <input type="checkbox" name="is_recommended" value="1"
                                       class="rounded border-slate-300 text-primary-600 focus:ring-primary-500"
                                       @checked(old('is_recommended', true))>
                                <span>推奨（初期表示で選択状態にするなど）</span>
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            <div class="p-6 bg-slate-50 flex items-center justify-end gap-3">
                <a href="{{ route('admin.uniform-recommendations.index') }}"
                   class="px-5 py-2.5 rounded-lg border border-slate-300 text-slate-700 hover:bg-white text-[14px] font-medium transition-all">
                    キャンセル
                </a>
                <button type="submit"
                        class="px-6 py-2.5 rounded-lg bg-primary-600 hover:bg-primary-700 text-white text-[14px] font-semibold shadow-md transition-all flex items-center gap-2">
                    <i class="ph ph-check text-base"></i>
                    登録する
                </button>
            </div>
        </div>
    </form>

    @include('admin.partials.institution-cascade-script')
@endsection




