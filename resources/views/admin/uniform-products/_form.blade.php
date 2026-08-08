{{--
  Shared uniform product form chrome (create + edit).
  Expects: $mode ('create'|'edit'), $makers, $categories
  Edit also expects: $product, $baseProductCode, $allSizes, size lists, etc.
--}}
@php
    $isEdit = ($mode ?? 'create') === 'edit';
    $pageTitle = $isEdit ? __('pages.edit_uniform') : __('pages.new_uniform_product');
    $submitLabel = $isEdit ? __('pages.update') : __('pages.register');
    $formAction = $isEdit
        ? route('admin.uniform-products.update', $product)
        : route('admin.uniform-products.store');
@endphp

<div class="mb-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 tracking-tight">{{ $pageTitle }}</h1>
            <p class="text-[14px] text-slate-600 mt-1.5">{{ __('pages.new_uniform_product_description') }}</p>
            <p class="text-[13px] text-slate-500 mt-2">{{ __('pages.ui_179a51c73a') }}</p>
        </div>
        <a href="{{ route('admin.uniform-products.index') }}" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-lg border border-slate-300 text-slate-700 hover:bg-slate-50 text-[14px] font-medium transition-all">
            <i data-lucide="arrow-left" class="w-4 h-4"></i>
            {{ __('common.back') }}
        </a>
    </div>
</div>

@if($errors->any())
    <div class="mb-6 p-4 bg-red-50 border-l-4 border-red-500 rounded-lg">
        <div class="flex items-center gap-2 mb-2">
            <i data-lucide="alert-circle" class="w-5 h-5 text-red-600"></i>
            <span class="text-[14px] text-red-800 font-medium">{{ __('pages.validation_error') }}</span>
        </div>
        <ul class="list-disc list-inside text-[13px] text-red-700 space-y-1">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<form method="POST" action="{{ $formAction }}" enctype="multipart/form-data" id="product-form" novalidate>
    @csrf
    @if($isEdit)
        @method('PUT')
    @endif

    {{-- Maker (same layout for create + edit) --}}
    <div class="bg-white border border-slate-200 rounded-xl shadow-sm p-6 mb-6">
        <h2 class="text-lg font-semibold text-slate-900 mb-4">{{ __('pages.maker_info') }}</h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label class="block text-[13px] font-semibold text-slate-700 mb-2">{{ __('menu.makers') }}</label>
                <select name="maker_id" class="w-full px-4 py-2.5 rounded-lg border border-slate-300 text-[14px] bg-white">
                    <option value="">{{ __('pages.not_set') }}</option>
                    @foreach(($makers ?? []) as $maker)
                        <option value="{{ $maker->id }}" @selected(old('maker_id', $isEdit ? $product->maker_id : null)==$maker->id)>{{ $maker->name }}（{{ $maker->code }}）</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-[13px] font-semibold text-slate-700 mb-2">{{ __('pages.maker_product_code') }}</label>
                <input type="text" name="maker_product_code" value="{{ old('maker_product_code', $isEdit ? $product->maker_product_code : '') }}"
                       class="w-full px-4 py-2.5 rounded-lg border border-slate-300 text-[14px]" placeholder="{{ __('pages.maker_side_code') }}">
            </div>
            <div>
                <label class="block text-[13px] font-semibold text-slate-700 mb-2">{{ __('pages.color_name') }}</label>
                <input type="text" name="color_name" value="{{ old('color_name', $isEdit ? $product->color_name : '') }}"
                       class="w-full px-4 py-2.5 rounded-lg border border-slate-300 text-[14px]" placeholder="例: ネイビー">
            </div>
        </div>
    </div>

    {{-- Product selection shell (same create layout) --}}
    <div class="bg-white border border-slate-200 rounded-xl shadow-sm overflow-hidden">
        <div class="p-6 border-b border-slate-200 bg-gradient-to-r from-primary-50 to-blue-50">
            <div class="flex items-center justify-between">
                <h2 class="text-lg font-semibold text-slate-900 flex items-center gap-2">
                    <i data-lucide="shopping-cart" class="w-5 h-5 text-primary-600"></i>
                    {{ __('pages.product_selection') }}
                </h2>
                @unless($isEdit)
                    <button type="button" id="add-product-btn" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-lg bg-primary-600 hover:bg-primary-700 text-white text-[14px] font-medium shadow-md transition-all">
                        <i data-lucide="plus" class="w-4 h-4"></i>
                        {{ __('pages.add_product') }}
                    </button>
                @endunless
            </div>
        </div>

        <div class="p-6">
            @if($isEdit)
                @include('admin.uniform-products._edit_product_card')
            @else
                <div id="products-container" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
                    <div class="col-span-full text-center py-12 text-slate-500">
                        <div class="uniform-placeholder mx-auto mb-3 w-16 h-16 rounded-xl">
                            <img src="{{ asset('images/brand/logo-mark-64.png') }}" alt="">
                        </div>
                        <p class="text-[14px]">{{ __('pages.click_add_product_to_start') }}</p>
                    </div>
                </div>
            @endif
        </div>

        <div class="p-6 bg-slate-50 flex items-center justify-end gap-3 border-t border-slate-200">
            <a href="{{ route('admin.uniform-products.index') }}" class="px-5 py-2.5 rounded-lg border border-slate-300 text-slate-700 hover:bg-white text-[14px] font-medium transition-all flex items-center gap-2">
                <i data-lucide="x" class="w-4 h-4"></i>
                {{ __('common.cancel') }}
            </a>
            <button type="submit" class="px-6 py-2.5 rounded-lg bg-primary-600 hover:bg-primary-700 text-white text-[14px] font-semibold shadow-md transition-all flex items-center gap-2">
                <i data-lucide="check" class="w-4 h-4"></i>
                {{ $submitLabel }}
            </button>
        </div>
    </div>
</form>

@if($isEdit)
    @include('admin.uniform-products._edit_scripts')
@else
    @include('admin.uniform-products._create_templates_and_scripts')
@endif
