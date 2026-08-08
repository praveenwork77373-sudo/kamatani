{{-- Single product card for edit — same visual layout as create product cards --}}
<div id="products-container" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
    <div class="product-card group border border-slate-200 rounded-xl overflow-hidden hover:shadow-lg transition-all duration-300 min-w-0 md:col-span-2 xl:col-span-1" data-category="{{ $product->category }}">
        <div class="relative h-40 bg-gradient-to-br from-slate-50 to-slate-100 overflow-hidden">
            <div class="w-full h-full flex items-center justify-center product-image-placeholder uniform-placeholder">
                @if($product->image_path)
                    <img src="{{ asset('storage/'.$product->image_path) }}" alt="{{ $product->name }}" class="w-full h-full object-cover">
                @else
                    <img src="{{ asset('images/brand/logo-mark-64.png') }}" alt="" class="w-14 h-14 object-contain opacity-80">
                @endif
            </div>
            <input type="file" name="image" accept="image/*" class="product-image-input hidden" id="product-image-input">
            <label for="product-image-input" class="absolute inset-0 cursor-pointer flex items-center justify-center bg-black/0 hover:bg-black/10 transition-all product-image-label">
                <div class="opacity-0 group-hover:opacity-100 transition-opacity bg-white/90 rounded-lg px-4 py-2 text-[13px] font-medium text-slate-700">
                    <i data-lucide="image" class="w-4 h-4 inline-block mr-1"></i>
                    {{ __('pages.select_image_file') }}
                </div>
            </label>
        </div>

        <div class="p-4 space-y-3 min-w-0">
            <div>
                <label class="block text-[13px] font-semibold text-slate-700 mb-1.5">{{ __('pages.product_code') }}</label>
                <div class="ui-field-icon">
                    <i data-lucide="hash"></i>
                    <input type="text"
                           value="{{ $baseProductCode }}"
                           class="ui-input bg-slate-50 text-slate-700 font-mono"
                           readonly
                           tabindex="-1">
                </div>
                <input type="hidden" name="product_code" value="{{ $baseProductCode }}">
                @error('product_code')
                    <p class="text-[12px] text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="block text-[13px] font-semibold text-slate-700 mb-1.5">
                    {{ __('pages.product_name') }} <span class="text-red-500">*</span>
                </label>
                <input type="text" name="name" value="{{ old('name', $product->name) }}" required
                       class="product-name-input w-full min-w-0 px-3 py-2.5 text-[14px] border border-slate-300 rounded-lg focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 transition-all bg-white"
                       placeholder="{{ __('pages.example_product_name') }}">
                @error('name')
                    <p class="text-[12px] text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div class="min-w-0">
                    <label class="block text-[13px] font-semibold text-slate-700 mb-1.5">
                        {{ __('pages.category') }} <span class="text-red-500">*</span>
                    </label>
                    <select name="category" required
                            class="product-category-select w-full min-w-0 px-3 py-2.5 text-[14px] border border-slate-300 rounded-lg focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 transition-all bg-white">
                        <option value="">{{ __('pages.select_please') }}</option>
                        @foreach(($categories ?? []) as $cat)
                            <option value="{{ $cat->code }}" data-size-scheme="{{ $cat->size_scheme }}" @selected(old('category', $product->category)===$cat->code)>{{ $cat->name }}</option>
                        @endforeach
                    </select>
                    @error('category')
                        <p class="text-[12px] text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div class="min-w-0">
                    <label class="block text-[13px] font-semibold text-slate-700 mb-1.5">
                        {{ __('pages.gender') }} <span class="text-red-500">*</span>
                    </label>
                    <select name="gender" required
                            class="w-full min-w-0 px-3 py-2.5 text-[14px] border border-slate-300 rounded-lg focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 transition-all bg-white">
                        <option value="">{{ __('pages.select_please') }}</option>
                        <option value="male" @selected(old('gender', $product->gender)==='male')>{{ __('pages.male') }}</option>
                        <option value="female" @selected(old('gender', $product->gender)==='female')>{{ __('pages.female') }}</option>
                        <option value="unisex" @selected(old('gender', $product->gender)==='unisex')>{{ __('pages.unisex') }}</option>
                    </select>
                    @error('gender')
                        <p class="text-[12px] text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="min-w-0">
                <label class="block text-[13px] font-semibold text-slate-700 mb-1.5 flex items-center gap-1.5">
                    <i data-lucide="ruler" class="w-4 h-4 text-primary-600"></i>
                    {{ __('pages.sizes') }} <span class="text-red-500">*</span>
                </label>
                <div class="product-sizes-container space-y-2 p-2 border border-slate-200 rounded-lg bg-slate-50 min-h-[3rem] max-h-72 overflow-y-auto overflow-x-hidden">
                    @foreach($allSizes as $sizeProduct)
                        <div class="size-row grid grid-cols-[1fr_5.5rem_auto] gap-2 items-end p-2 bg-white rounded-lg border border-slate-200 min-w-0">
                            <div class="min-w-0">
                                <label class="block text-[11px] font-medium text-slate-500 mb-1">{{ __('pages.size') }}</label>
                                @if(\App\Models\UniformCategory::sizeSchemeFor($product->category) === 'shoe')
                                    <input type="number" name="sizes[{{ $loop->index }}][size]" value="{{ old("sizes.{$loop->index}.size", $sizeProduct->size) }}" step="0.5" min="20" max="30" required
                                           class="size-select w-full min-w-0 px-2.5 py-2 text-[13px] border border-slate-300 rounded-lg focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 bg-white"
                                           placeholder="{{ __('pages.example_shoe_size') }}">
                                @else
                                    <select name="sizes[{{ $loop->index }}][size]" required
                                            class="size-select w-full min-w-0 px-2.5 py-2 text-[13px] border border-slate-300 rounded-lg focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 bg-white">
                                        <option value="">{{ __('pages.select_size') }}</option>
                                        @foreach($topBottomSizes as $size)
                                            <option value="{{ $size }}" @selected(old("sizes.{$loop->index}.size", $sizeProduct->size)===$size)>{{ $size }}</option>
                                        @endforeach
                                    </select>
                                @endif
                            </div>
                            <div class="min-w-0">
                                <label class="block text-[11px] font-medium text-slate-500 mb-1">{{ __('pages.stock') }}</label>
                                <input type="number" name="sizes[{{ $loop->index }}][stock_quantity]" value="{{ old("sizes.{$loop->index}.stock_quantity", $sizeProduct->stock_quantity) }}" min="0" required
                                       class="stock-quantity-input w-full min-w-0 px-2 py-2 text-[13px] border border-slate-300 rounded-lg focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 bg-white text-center"
                                       placeholder="0">
                            </div>
                            @if($allSizes->count() > 1)
                                <button type="button" class="remove-size-btn text-red-500 hover:text-red-700 p-2 mb-0.5 flex items-center justify-center rounded-lg hover:bg-red-50">
                                    <i data-lucide="trash-2" class="w-4 h-4"></i>
                                </button>
                            @else
                                <span class="w-9"></span>
                            @endif
                        </div>
                    @endforeach
                </div>
                <button type="button" id="add-size-btn" class="add-size-btn mt-2 w-full px-3 py-2 rounded-lg border border-dashed border-slate-300 hover:border-primary-400 hover:bg-primary-50 text-[13px] font-medium text-slate-600 hover:text-primary-700 transition-all disabled:opacity-50 disabled:cursor-not-allowed">
                    <i data-lucide="plus" class="w-4 h-4 inline-block mr-1"></i>
                    {{ __('pages.add_size') }}
                </button>
                @error('sizes.*.size')
                    <p class="text-[12px] text-red-600 mt-1">{{ $message }}</p>
                @enderror
                @error('sizes.*.stock_quantity')
                    <p class="text-[12px] text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </div>
        </div>
    </div>
</div>
