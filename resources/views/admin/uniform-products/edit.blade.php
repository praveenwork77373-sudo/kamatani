@extends('layouts.app')

@section('title', __('pages.edit_uniform') . ' | ' . config('app.name'))
@section('page_title', __('pages.edit_uniform'))

@section('content')
    <div class="mb-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-slate-900 tracking-tight">{{ __('pages.edit_uniform') }}</h1>
                <p class="text-[14px] text-slate-600 mt-1.5">{{ __('pages.new_uniform_product_description') }}</p>
                <p class="text-[13px] text-slate-500 mt-2">{{ __('pages.ui_179a51c73a') }}</p>
            </div>
            <a href="{{ route('admin.uniform-products.index') }}" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-lg border border-slate-300 text-slate-700 hover:bg-slate-50 text-[14px] font-medium transition-all">
                <i data-lucide="arrow-left" class="w-4 h-4"></i>
                {{ __('common.back') }}
            </a>
        </div>
    </div>

    {{-- Status messages removed - redirects to index page where messages are shown --}}

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

    <form method="POST" action="{{ route('admin.uniform-products.update', $product) }}" enctype="multipart/form-data" id="product-form">
        @csrf
        @method('PUT')

        {{-- Maker (SPEC) --}}
        <div class="max-w-xl mx-auto mb-6">
            <div class="bg-white border border-slate-200 rounded-xl shadow-sm p-6">
                <h2 class="text-lg font-semibold text-slate-900 mb-4">{{ __('pages.maker_info') }}</h2>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-[13px] font-semibold text-slate-700 mb-2">{{ __('menu.makers') }}</label>
                        <select name="maker_id" class="w-full px-4 py-2.5 rounded-lg border border-slate-300 text-[14px] bg-white">
                            <option value="">{{ __('pages.not_set') }}</option>
                            @foreach(($makers ?? []) as $maker)
                                <option value="{{ $maker->id }}" @selected(old('maker_id', $product->maker_id)==$maker->id)>{{ $maker->name }}（{{ $maker->code }}）</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-[13px] font-semibold text-slate-700 mb-2">{{ __('pages.maker_product_code') }}</label>
                        <input type="text" name="maker_product_code" value="{{ old('maker_product_code', $product->maker_product_code) }}"
                               class="w-full px-4 py-2.5 rounded-lg border border-slate-300 text-[14px]">
                    </div>
                    <div>
                        <label class="block text-[13px] font-semibold text-slate-700 mb-2">{{ __('pages.color_name') }}</label>
                        <input type="text" name="color_name" value="{{ old('color_name', $product->color_name) }}"
                               class="w-full px-4 py-2.5 rounded-lg border border-slate-300 text-[14px]">
                    </div>
                </div>
            </div>
        </div>

        {{-- Product Card Section - Centered --}}
        <div class="max-w-xl mx-auto">
            <div class="bg-white border border-slate-200 rounded-xl shadow-sm overflow-hidden">
                <div class="p-6 border-b border-slate-200 bg-gradient-to-r from-primary-50 to-blue-50">
                    <h2 class="text-lg font-semibold text-slate-900 flex items-center gap-2">
                        <i data-lucide="shopping-cart" class="w-5 h-5 text-primary-600"></i>
                        {{ __('pages.product_selection') }}
                    </h2>
                </div>

                <div class="p-6">
                    {{-- Product Card --}}
                    <div id="products-container" class="grid grid-cols-1 gap-6">
                        <div class="product-card group border border-slate-200 rounded-xl overflow-hidden hover:shadow-lg transition-all duration-300" data-category="{{ $product->category }}">
                            {{-- Product Image --}}
                            <div class="relative h-48 bg-gradient-to-br from-slate-50 to-slate-100 overflow-hidden">
                                <div class="w-full h-full flex items-center justify-center product-image-placeholder">
                                    @if($product->image_path)
                                        <img src="{{ asset('storage/'.$product->image_path) }}" alt="{{ $product->name }}" class="w-full h-full object-cover">
                                    @else
                                        <i data-lucide="shirt" class="w-16 h-16 text-slate-300"></i>
                                    @endif
                                </div>
                                <input type="file" name="image" accept="image/*" class="product-image-input hidden" id="product-image-input">
                                <label for="product-image-input" class="absolute inset-0 cursor-pointer flex items-center justify-center bg-black/0 hover:bg-black/10 transition-all product-image-label">
                                    <div class="opacity-0 hover:opacity-100 transition-opacity bg-white/90 rounded-lg px-4 py-2 text-[13px] font-medium text-slate-700">
                                        <i data-lucide="image" class="w-4 h-4 inline-block mr-1"></i>
                                        {{ __('pages.select_image_file') }}
                                    </div>
                                </label>
                            </div>
                            
                            <div class="p-5 space-y-4">
                                <div>
                                    <label class="block text-[13px] font-semibold text-slate-700 mb-2">
                                        {{ __('pages.product_code') }}
                                    </label>
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
                                    <label class="block text-[13px] font-semibold text-slate-700 mb-2">
                                        {{ __('pages.product_name') }} <span class="text-red-500">*</span>
                                    </label>
                                    <input type="text" name="name" value="{{ old('name', $product->name) }}" required
                                           class="product-name-input w-full px-4 py-2.5 text-[14px] border-2 border-slate-300 rounded-lg focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 transition-all bg-white"
                                           placeholder="{{ __('pages.example_product_name') }}">
                                    @error('name')
                                        <p class="text-[12px] text-red-600 mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                                
                                <div>
                                    <label class="block text-[13px] font-semibold text-slate-700 mb-2">
                                        {{ __('pages.category') }} <span class="text-red-500">*</span>
                                    </label>
                                    <select name="category" required
                                            class="product-category-select w-full px-4 py-2.5 text-[14px] border-2 border-slate-300 rounded-lg focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 transition-all bg-white font-medium">
                                        <option value="">{{ __('pages.select_please') }}</option>
                                        @foreach(($categories ?? []) as $cat)
                                            <option value="{{ $cat->code }}" data-size-scheme="{{ $cat->size_scheme }}" @selected(old('category', $product->category)===$cat->code)>{{ $cat->name }}</option>
                                        @endforeach
                                    </select>
                                    @error('category')
                                        <p class="text-[12px] text-red-600 mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                                
                                <div>
                                    <label class="block text-[13px] font-semibold text-slate-700 mb-2">
                                        {{ __('pages.gender') }} <span class="text-red-500">*</span>
                                    </label>
                                    <div class="relative">
<select name="gender" required
                                                class="w-full px-4 py-2.5 text-[14px] border-2 border-slate-300 rounded-lg focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 transition-all bg-white font-medium">
                                            <option value="">{{ __('pages.select_please') }}</option>
                                            <option value="male" @selected(old('gender', $product->gender)==='male')>{{ __('pages.male') }}</option>
                                            <option value="female" @selected(old('gender', $product->gender)==='female')>{{ __('pages.female') }}</option>
                                            <option value="unisex" @selected(old('gender', $product->gender)==='unisex')>{{ __('pages.unisex') }}</option>
                                        </select>
                                    </div>
                                    @error('gender')
                                        <p class="text-[12px] text-red-600 mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                                
                                {{-- Sizes Section (matching create form with multiple sizes) --}}
                                <div>
                                    <label class="block text-[13px] font-semibold text-slate-700 mb-2 flex items-center gap-1.5">
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
                                                                <option value="{{ $size }}" @selected(old("sizes.{$loop->parent->index}.size", $sizeProduct->size)===$size)>{{ $size }}</option>
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
                                    <button type="button" id="add-size-btn" class="add-size-btn mt-2 w-full px-3 py-2 rounded-lg border border-dashed border-slate-300 hover:border-primary-400 hover:bg-primary-50 text-[13px] font-medium text-slate-600 hover:text-primary-700 transition-all disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:bg-slate-50 disabled:hover:border-slate-300">
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
                </div>

                <div class="p-6 bg-slate-50 flex items-center justify-end gap-3 border-t border-slate-200">
                    <a href="{{ route('admin.uniform-products.index') }}" class="px-5 py-2.5 rounded-lg border border-slate-300 text-slate-700 hover:bg-white text-[14px] font-medium transition-all">
                        {{ __('common.cancel') }}
                    </a>
                    <button type="submit" class="px-6 py-2.5 rounded-lg bg-primary-600 hover:bg-primary-700 text-white text-[14px] font-semibold shadow-md transition-all flex items-center gap-2">
                        <i data-lucide="check" class="w-4 h-4"></i>
                        {{ __('pages.update') }}
                    </button>
                </div>
            </div>
        </div>
    </form>

    {{-- Size Template (Hidden) --}}
    <template id="size-row-template">
        <div class="size-row grid grid-cols-[1fr_5.5rem_auto] gap-2 items-end p-2 bg-white rounded-lg border border-slate-200 min-w-0">
            <div class="min-w-0">
                <label class="block text-[11px] font-medium text-slate-500 mb-1">{{ __('pages.size') }}</label>
                <select name="sizes[SIZE_INDEX][size]" required
                        class="size-select w-full min-w-0 px-2.5 py-2 text-[13px] border border-slate-300 rounded-lg focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 bg-white">
                    <option value="">{{ __('pages.select_size') }}</option>
                </select>
            </div>
            <div class="min-w-0">
                <label class="block text-[11px] font-medium text-slate-500 mb-1">{{ __('pages.stock') }}</label>
                <input type="number" name="sizes[SIZE_INDEX][stock_quantity]" required min="0" value="0"
                       class="stock-quantity-input w-full min-w-0 px-2 py-2 text-[13px] border border-slate-300 rounded-lg focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 bg-white text-center"
                       placeholder="0">
            </div>
            <button type="button" class="remove-size-btn text-red-500 hover:text-red-700 p-2 mb-0.5 flex items-center justify-center rounded-lg hover:bg-red-50">
                <i data-lucide="trash-2" class="w-4 h-4"></i>
            </button>
        </div>
    </template>

    <!-- jQuery CDN -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>
    
    <script>
        $(document).ready(function() {
            if (typeof lucide !== 'undefined') {
                lucide.createIcons();
            }

            const topBottomSizes = @json($topBottomSizes);
            const categorySizeSchemes = @json(($categorySizeSchemes ?? collect())->toArray());
            function sizeSchemeFor(category) { return categorySizeSchemes[category] || 'letter'; } // All sizes (for existing selects)
            const shoeSizes = @json($shoeSizes); // All sizes (for existing selects)
            const availableTopBottomSizes = @json($availableTopBottomSizes ?? []); // Filtered sizes (for new selects)
            const availableShoeSizes = @json($availableShoeSizes ?? []); // Filtered sizes (for new selects)
            const existingSizes = @json($existingSizes ?? []); // Existing sizes to exclude
            const productCard = $('.product-card');
            const categorySelect = $('.product-category-select');
            const sizesContainer = $('.product-sizes-container');

            // Function to get currently selected sizes from existing rows
            function getCurrentlySelectedSizes() {
                const selectedSizes = [];
                sizesContainer.find('.size-row').each(function() {
                    const sizeValue = $(this).find('.size-select, input[type="number"][name*="size"]').val();
                    if (sizeValue && sizeValue.trim() !== '') {
                        selectedSizes.push(sizeValue.trim());
                    }
                });
                return selectedSizes;
            }
            
            // Function to get available sizes (all sizes minus currently selected ones)
            function getAvailableSizes(category) {
                const selectedSizes = getCurrentlySelectedSizes();
                if (sizeSchemeFor(category) === 'shoe') {
                    // For shoes, check if there are any available sizes (shoe sizes are numeric inputs, so we check if all standard sizes are used)
                    const allShoeSizes = availableShoeSizes.length > 0 ? availableShoeSizes : shoeSizes;
                    return allShoeSizes.filter(size => !selectedSizes.includes(size));
                } else {
                    return (availableTopBottomSizes.length > 0 ? availableTopBottomSizes : topBottomSizes).filter(size => !selectedSizes.includes(size));
                }
            }
            
            // Function to update add size button state
            function updateAddSizeButtonState() {
                const addSizeBtn = $('#add-size-btn');
                const category = categorySelect.val() || '{{ $product->category }}';
                
                // For shoe category, always allow adding (numeric input, unlimited options)
                if (sizeSchemeFor(category) === 'shoe') {
                    addSizeBtn.prop('disabled', false);
                    addSizeBtn.removeClass('opacity-50 cursor-not-allowed');
                    return;
                }
                
                // For top/bottom categories, check if there are available sizes
                const availableSizes = getAvailableSizes(category);
                
                if (availableSizes.length === 0) {
                    // No available sizes - disable button
                    addSizeBtn.prop('disabled', true);
                    addSizeBtn.addClass('opacity-50 cursor-not-allowed');
                } else {
                    // Has available sizes - enable button
                    addSizeBtn.prop('disabled', false);
                    addSizeBtn.removeClass('opacity-50 cursor-not-allowed');
                }
            }
            
            // Add size row
            $('.add-size-btn').on('click', function() {
                if ($(this).prop('disabled')) {
                    return; // Don't proceed if button is disabled
                }
                const template = document.getElementById('size-row-template');
                const clone = template.content.cloneNode(true);
                const sizeRow = clone.querySelector('.size-row');
                const existingRows = sizesContainer.find('.size-row');
                const sizeIndex = existingRows.length;
                
                // Replace SIZE_INDEX
                sizeRow.querySelectorAll('[name*="SIZE_INDEX"]').forEach(el => {
                    const originalName = el.name;
                    const newName = originalName.replace(/SIZE_INDEX/g, sizeIndex);
                    el.setAttribute('name', newName);
                });
                
                const category = categorySelect.val() || '{{ $product->category }}';
                const sizeSelect = sizeRow.querySelector('.size-select');
                
                if (sizeSchemeFor(category) === 'shoe') {
                    // Replace select with number input
                    const input = document.createElement('input');
                    input.type = 'number';
                    input.step = '0.5';
                    input.min = '20';
                    input.max = '30';
                    input.setAttribute('name', `sizes[${sizeIndex}][size]`);
                    input.required = true;
                    input.className = sizeSelect.className;
                    input.placeholder = '{{ __('pages.example_shoe_size') }}';
                    sizeSelect.replaceWith(input);
                } else {
                    // Populate select options with only available sizes (excluding currently selected ones)
                    sizeSelect.setAttribute('name', `sizes[${sizeIndex}][size]`);
                    const availableSizes = getAvailableSizes(category);
                    availableSizes.forEach(size => {
                        const option = document.createElement('option');
                        option.value = size;
                        option.textContent = size;
                        sizeSelect.appendChild(option);
                    });
                }
                
                // Remove size button
                sizeRow.querySelector('.remove-size-btn').addEventListener('click', function() {
                    sizeRow.remove();
                    updateSizeIndices();
                });
                
                sizesContainer.append(sizeRow);
                updateSizeIndices();
                updateAddSizeButtonState(); // Update button state after adding
                
                if (typeof lucide !== 'undefined') {
                    lucide.createIcons();
                }
            });

            // Remove size row
            $('.remove-size-btn').on('click', function() {
                $(this).closest('.size-row').remove();
                updateSizeIndices();
                updateAddSizeButtonState(); // Update button state after removing
            });

            function updateSizeIndices() {
                sizesContainer.find('.size-row').each(function(index) {
                    const $row = $(this);
                    $row.find('.size-select, input[type="number"][name*="size"]').attr('name', `sizes[${index}][size]`);
                    $row.find('.stock-quantity-input').attr('name', `sizes[${index}][stock_quantity]`);
                });
            }

            // Category change handler - update size input type
            categorySelect.on('change', function() {
                const category = $(this).val();
                productCard.attr('data-category', category);
                updateSizesForCategory(category);
                updateAddSizeButtonState(); // Update button state when category changes
            });

            function updateSizesForCategory(category) {
                sizesContainer.find('.size-row').each(function() {
                    const $row = $(this);
                    const sizeContainer = $row.find('.flex-1');
                    const currentSize = $row.find('.size-select, input[type="number"]').val();
                    
                    if (sizeSchemeFor(category) === 'shoe') {
                        // Replace select with number input
                        if (sizeContainer.find('select').length) {
                            const input = $('<input>', {
                                type: 'number',
                                step: '0.5',
                                min: '20',
                                max: '30',
                                name: sizeContainer.find('select').attr('name'),
                                required: true,
                                class: 'size-select w-full px-3 py-2 text-[13px] border border-slate-300 rounded-lg focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20',
                                placeholder: '例: 25.0',
                                value: currentSize || ''
                            });
                            sizeContainer.find('select').replaceWith(input);
                        }
                    } else {
                        // Replace number input with select
                        if (sizeContainer.find('input[type="number"]').length) {
                            const select = $('<select>', {
                                name: sizeContainer.find('input[type="number"]').attr('name'),
                                required: true,
                                class: 'size-select w-full px-3 py-2 text-[13px] border border-slate-300 rounded-lg focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20'
                            });
                            select.append($('<option>', { value: '', text: '{{ __('pages.select_size') }}' }));
                            topBottomSizes.forEach(size => {
                                const option = $('<option>', { value: size, text: size });
                                if (currentSize === size) option.prop('selected', true);
                                select.append(option);
                            });
                            sizeContainer.find('input[type="number"]').replaceWith(select);
                        }
                    }
                });
                updateAddSizeButtonState(); // Update button state after category conversion
            }

            // Listen for size select changes to update button state
            $(document).on('change', '.size-select, input[type="number"][name*="size"]', function() {
                updateAddSizeButtonState();
            });
            
            // Initialize button state on page load
            updateAddSizeButtonState();
            
            // Image upload handler
            const imageInput = $('#product-image-input');
            const imagePlaceholder = $('.product-image-placeholder');
            
            imageInput.on('change', function(e) {
                const file = e.target.files?.[0];
                if (file) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        imagePlaceholder.html(`<img src="${e.target.result}" alt="Preview" class="w-full h-full object-cover">`);
                    };
                    reader.readAsDataURL(file);
                }
            });
        });
    </script>
@endsection
