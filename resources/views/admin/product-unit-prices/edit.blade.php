@extends('layouts.app')

@section('title', __('pages.product_unit_prices'))
@section('page_title', __('pages.product_unit_prices'))

@section('content')
<div class="max-w-xl">
    <form method="POST" action="{{ route('admin.product-unit-prices.update', $price) }}" class="ui-card ui-form p-4 sm:p-6 space-y-4">
        @csrf
        @method('PUT')
        <div>
            <label class="ui-label">商品コード <span class="text-red-500">*</span></label>
            <input type="text" name="product_code" list="product-codes" value="{{ old('product_code', $price->product_code) }}" required class="ui-input">
            <datalist id="product-codes">
                @foreach($productCodes as $code)
                    <option value="{{ $code }}"></option>
                @endforeach
            </datalist>
            @error('product_code')<p class="ui-error">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="ui-label">{{ __('pages.size_name') }}</label>
            <input type="text" name="size_name" value="{{ old('size_name', $price->size_name) }}" class="ui-input">
        </div>
        <div>
            <label class="ui-label">{{ __('pages.purchase_unit_price') }} <span class="text-red-500">*</span></label>
            <input type="number" step="0.01" min="0" name="purchase_unit_price" value="{{ old('purchase_unit_price', $price->purchase_unit_price) }}" required class="ui-input">
        </div>
        <div>
            <label class="ui-label">{{ __('pages.supplier_notes') }}</label>
            <textarea name="notes" rows="2" class="ui-textarea">{{ old('notes', $price->notes) }}</textarea>
        </div>
        <div class="flex gap-3">
            <a href="{{ route('admin.product-unit-prices.index') }}" class="ui-btn ui-btn-secondary">戻る</a>
            <button type="submit" class="ui-btn ui-btn-primary">更新</button>
        </div>
    </form>
</div>
@endsection
