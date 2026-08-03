@extends('layouts.app')

@section('title', __('pages.new_uniform_product') . ' | ' . config('app.name'))
@section('page_title', __('pages.new_uniform_product'))

@section('content')
    <div class="mb-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-slate-900 tracking-tight">{{ __('pages.new_uniform_product') }}</h1>
                <p class="text-[14px] text-slate-600 mt-1.5">{{ __('pages.new_uniform_product_description') }}</p>
                <p class="text-[13px] text-slate-500 mt-2">商品はカタログです。適用は「契約プラン」画面で商品をチェックして紐付けます。医療機関・部署は不要です。</p>
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

    <form method="POST" action="{{ route('admin.uniform-products.store') }}" enctype="multipart/form-data" id="product-form">
        @csrf

        {{-- Maker (SPEC) --}}
        <div class="bg-white border border-slate-200 rounded-xl shadow-sm p-6 mb-6">
            <h2 class="text-lg font-semibold text-slate-900 mb-4">メーカー情報</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-[13px] font-semibold text-slate-700 mb-2">メーカー</label>
                    <select name="maker_id" class="w-full px-4 py-2.5 rounded-lg border border-slate-300 text-[14px] bg-white">
                        <option value="">未設定</option>
                        @foreach(($makers ?? []) as $maker)
                            <option value="{{ $maker->id }}" @selected(old('maker_id')==$maker->id)>{{ $maker->name }}（{{ $maker->code }}）</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-[13px] font-semibold text-slate-700 mb-2">メーカー品番</label>
                    <input type="text" name="maker_product_code" value="{{ old('maker_product_code') }}"
                           class="w-full px-4 py-2.5 rounded-lg border border-slate-300 text-[14px]" placeholder="メーカー側コード">
                </div>
                <div>
                    <label class="block text-[13px] font-semibold text-slate-700 mb-2">色名</label>
                    <input type="text" name="color_name" value="{{ old('color_name') }}"
                           class="w-full px-4 py-2.5 rounded-lg border border-slate-300 text-[14px]" placeholder="例: ネイビー">
                </div>
            </div>
        </div>

        {{-- Product Cards Section --}}
        <div class="bg-white border border-slate-200 rounded-xl shadow-sm overflow-hidden">
            <div class="p-6 border-b border-slate-200 bg-gradient-to-r from-primary-50 to-blue-50">
                <div class="flex items-center justify-between">
                    <h2 class="text-lg font-semibold text-slate-900 flex items-center gap-2">
                        <i data-lucide="shopping-cart" class="w-5 h-5 text-primary-600"></i>
                        {{ __('pages.product_selection') }}
                    </h2>
                    <button type="button" id="add-product-btn" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-lg bg-primary-600 hover:bg-primary-700 text-white text-[14px] font-medium shadow-md transition-all">
                        <i data-lucide="plus" class="w-4 h-4"></i>
                        {{ __('pages.add_product') }}
                    </button>
                </div>
            </div>

            <div class="p-6">
                {{-- Products Grid --}}
                <div id="products-container" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                    {{-- Products will be added here dynamically --}}
                    <div class="col-span-full text-center py-12 text-slate-500">
                        <i data-lucide="shopping-bag" class="w-12 h-12 mx-auto mb-3 text-slate-300"></i>
                        <p class="text-[14px]">{{ __('pages.click_add_product_to_start') }}</p>
                    </div>
                </div>
            </div>

            <div class="p-6 bg-slate-50 flex items-center justify-end gap-3 border-t border-slate-200">
                <a href="{{ route('admin.uniform-products.index') }}" class="px-5 py-2.5 rounded-lg border border-slate-300 text-slate-700 hover:bg-white text-[14px] font-medium transition-all flex items-center gap-2">
                    <i data-lucide="x" class="w-4 h-4"></i>
                    {{ __('common.cancel') }}
                </a>
                <button type="submit" class="px-6 py-2.5 rounded-lg bg-primary-600 hover:bg-primary-700 text-white text-[14px] font-semibold shadow-md transition-all flex items-center gap-2">
                    <i data-lucide="check" class="w-4 h-4"></i>
                    {{ __('pages.register') }}
                </button>
            </div>
        </div>
    </form>

    {{-- Product Template (Hidden) --}}
    <template id="product-card-template">
        <div class="product-card group border border-slate-200 rounded-xl overflow-hidden hover:shadow-lg transition-all duration-300" data-category="">
            <div class="relative h-48 bg-gradient-to-br from-slate-50 to-slate-100 overflow-hidden">
                <div class="w-full h-full flex items-center justify-center product-image-placeholder">
                    <i data-lucide="shirt" class="w-16 h-16 text-slate-300"></i>
                </div>
                <input type="file" name="products[INDEX][image]" accept="image/*" class="product-image-input hidden">
                <label class="absolute inset-0 cursor-pointer flex items-center justify-center bg-black/0 hover:bg-black/10 transition-all product-image-label">
                    <div class="opacity-0 hover:opacity-100 transition-opacity bg-white/90 rounded-lg px-4 py-2 text-[13px] font-medium text-slate-700">
                        <i data-lucide="image" class="w-4 h-4 inline-block mr-1"></i>
                        {{ __('pages.select_image_file') }}
                    </div>
                </label>
                <button type="button" class="product-remove-btn absolute top-2 right-2 bg-red-500 hover:bg-red-600 text-white rounded-full p-1.5 shadow-lg opacity-0 group-hover:opacity-100 transition-opacity">
                    <i data-lucide="x" class="w-4 h-4"></i>
                </button>
            </div>
            
            <div class="p-5 space-y-4">
                <div>
                    <label class="block text-[13px] font-semibold text-slate-700 mb-2">
                        {{ __('pages.product_code') }} <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="products[INDEX][product_code]" required
                           class="product-code-input w-full px-4 py-2.5 text-[14px] border-2 border-slate-300 rounded-lg focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 transition-all bg-white font-medium"
                           placeholder="{{ __('pages.example_product_code') }}">
                </div>
                
                <div>
                    <label class="block text-[13px] font-semibold text-slate-700 mb-2">
                        {{ __('pages.product_name') }} <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="products[INDEX][name]" required
                           class="product-name-input w-full px-4 py-2.5 text-[14px] border-2 border-slate-300 rounded-lg focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 transition-all bg-white"
                           placeholder="{{ __('pages.example_product_name') }}">
                </div>
                
                <div>
                    <label class="block text-[13px] font-semibold text-slate-700 mb-2">
                        {{ __('pages.category') }} <span class="text-red-500">*</span>
                    </label>
                    <div class="relative">
<select name="products[INDEX][category]" required
                                class="product-category-select w-full px-4 py-2.5 text-[14px] border-2 border-slate-300 rounded-lg focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 transition-all bg-white font-medium">
                            <option value="">{{ __('pages.select_please') }}</option>
                            <option value="top">{{ __('pages.category_top') }}</option>
                            <option value="bottom">{{ __('pages.category_bottom') }}</option>
                            <option value="shoe">{{ __('pages.category_shoe') }}</option>
                            <option value="other">{{ __('pages.category_other') }}</option>
                        </select>
                    </div>
                </div>
                
                <div>
                    <label class="block text-[13px] font-semibold text-slate-700 mb-2">
                        {{ __('pages.gender') }} <span class="text-red-500">*</span>
                    </label>
                    <div class="relative">
<select name="products[INDEX][gender]" required
                                class="w-full px-4 py-2.5 text-[14px] border-2 border-slate-300 rounded-lg focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 transition-all bg-white font-medium">
                            <option value="">{{ __('pages.select_please') }}</option>
                            <option value="male">{{ __('pages.male') }}</option>
                            <option value="female">{{ __('pages.female') }}</option>
                            <option value="unisex">{{ __('pages.unisex') }}</option>
                        </select>
                    </div>
                </div>
                
                {{-- Sizes Section --}}
                <div>
                    <label class="block text-[13px] font-semibold text-slate-700 mb-2 flex items-center gap-1.5">
                        <i data-lucide="ruler" class="w-4 h-4 text-primary-600"></i>
                        {{ __('pages.sizes') }} <span class="text-red-500">*</span>
                    </label>
                    <div class="product-sizes-container space-y-2 max-h-48 overflow-y-auto p-2 border border-slate-200 rounded-lg bg-slate-50">
                        {{-- Sizes will be added here dynamically --}}
                    </div>
                    <button type="button" class="add-size-btn mt-2 w-full px-3 py-2 rounded-lg border border-dashed border-slate-300 hover:border-primary-400 hover:bg-primary-50 text-[13px] font-medium text-slate-600 hover:text-primary-700 transition-all">
                        <i data-lucide="plus" class="w-4 h-4 inline-block mr-1"></i>
                        {{ __('pages.add_size') }}
                    </button>
                </div>
            </div>
        </div>
    </template>

    {{-- Size Template (Hidden) --}}
    <template id="size-row-template">
        <div class="size-row flex items-center gap-2 p-2 bg-white rounded-lg border border-slate-200">
            <div class="flex-1 relative">
<select name="products[INDEX][sizes][SIZE_INDEX][size]" required
                        class="size-select w-full pl-11 pr-3 py-2 text-[13px] border border-slate-300 rounded-lg focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 bg-white">
                    <option value="">{{ __('pages.select_size') }}</option>
                </select>
            </div>
            <div class="w-24 relative">
<input type="number" name="products[INDEX][sizes][SIZE_INDEX][stock_quantity]" required min="0" value="0"
                       class="stock-quantity-input w-full pl-11 pr-3 py-2 text-[13px] border border-slate-300 rounded-lg focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 bg-white"
                       placeholder="{{ __('pages.stock') }}">
            </div>
            <button type="button" class="remove-size-btn text-red-500 hover:text-red-700 p-1 flex items-center justify-center">
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
            const shoeSizes = @json($shoeSizes);
            let productIndex = 0;
            const productsContainer = document.getElementById('products-container');
            const addProductBtn = document.getElementById('add-product-btn');
            let currentCategory = 'top';

            // Update current category when category select changes
            document.addEventListener('change', function(e) {
                if (e.target.classList.contains('product-category-select')) {
                    currentCategory = e.target.value || 'top';
                }
            });

            // Add product
            addProductBtn?.addEventListener('click', function() {
                addProductCard();
            });

            function addProductCard() {
                const template = document.getElementById('product-card-template');
                const clone = template.content.cloneNode(true);
                const card = clone.querySelector('.product-card');
                
                // Get the product index (current number of cards)
                const existingCards = productsContainer.querySelectorAll('.product-card');
                const productIndex = existingCards.length;
                
                // Replace all INDEX placeholders in the cloned card
                // IMPORTANT: Only replace INDEX in fields that have dot notation structure
                // Replace INDEX placeholders with actual index
                card.querySelectorAll('[name*="INDEX"]').forEach(el => {
                    const originalName = el.name;
                    const newName = originalName.replace(/INDEX/g, productIndex);
                    el.setAttribute('name', newName);
                });
                
                // Set category
                card.dataset.category = currentCategory;
                const categorySelect = card.querySelector('.product-category-select');
                if (categorySelect) {
                    categorySelect.value = currentCategory;
                }
                
                // Image upload handler
                const imageInput = card.querySelector('.product-image-input');
                const imagePlaceholder = card.querySelector('.product-image-placeholder');
                const imageLabel = card.querySelector('.product-image-label');
                
                if (imageInput && imagePlaceholder && imageLabel) {
                    imageInput.addEventListener('change', function(e) {
                        const file = e.target.files?.[0];
                        if (file) {
                            const reader = new FileReader();
                            reader.onload = function(e) {
                                imagePlaceholder.innerHTML = `<img src="${e.target.result}" alt="Preview" class="w-full h-full object-cover">`;
                            };
                            reader.readAsDataURL(file);
                        }
                    });
                    
                    imageLabel.addEventListener('click', function(e) {
                        e.preventDefault();
                        e.stopPropagation();
                        imageInput.click();
                    });
                }
                
                // Remove product
                card.querySelector('.product-remove-btn').addEventListener('click', function() {
                    card.remove();
                    updateProductIndices();
                });
                
                // Category change handler
                card.querySelector('.product-category-select').addEventListener('change', function() {
                    card.dataset.category = this.value;
                    updateSizesForProduct(card);
                });
                
                // Add size button
                card.querySelector('.add-size-btn').addEventListener('click', function() {
                    // Get current product index from card position
                    const cards = productsContainer.querySelectorAll('.product-card');
                    const currentIndex = Array.from(cards).indexOf(card);
                    addSizeRow(card, currentIndex);
                });
                
                productsContainer.querySelector('.col-span-full')?.remove();
                productsContainer.appendChild(card);
                
                // Update all indices after adding the card
                updateProductIndices();
                
                // Add initial size after indices are updated
                const cardsAfterAdd = productsContainer.querySelectorAll('.product-card');
                const currentIndex = Array.from(cardsAfterAdd).indexOf(card);
                addSizeRow(card, currentIndex);
                
                // Final update to ensure all indices are correct
                updateProductIndices();
                
                if (typeof lucide !== 'undefined') {
                    lucide.createIcons();
                }
            }

            function addSizeRow(productCard, productIndex) {
                const template = document.getElementById('size-row-template');
                const clone = template.content.cloneNode(true);
                const sizeRow = clone.querySelector('.size-row');
                const sizeSelect = sizeRow.querySelector('.size-select');
                const category = productCard.dataset.category || productCard.querySelector('.product-category-select')?.value;
                
                // Get current product index from card position (in case it changed)
                const allCards = productsContainer.querySelectorAll('.product-card');
                const actualProductIndex = Array.from(allCards).indexOf(productCard);
                const finalProductIndex = actualProductIndex >= 0 ? actualProductIndex : productIndex;
                
                // Update INDEX placeholders - use sequential index starting from 0
                const existingRows = productCard.querySelectorAll('.size-row');
                const sizeIndex = existingRows.length; // This will be the index for the new row
                
                // Set proper field names directly - use setAttribute to ensure it's set
                const sizeInput = sizeRow.querySelector('.size-select');
                const stockInput = sizeRow.querySelector('.stock-quantity-input');
                
                if (sizeInput) {
                    sizeInput.setAttribute('name', `products[${finalProductIndex}][sizes][${sizeIndex}][size]`);
                }
                if (stockInput) {
                    stockInput.setAttribute('name', `products[${finalProductIndex}][sizes][${sizeIndex}][stock_quantity]`);
                }
                
                // Populate size options based on category
                const sizes = category === 'shoe' ? shoeSizes : topBottomSizes;
                if (category === 'shoe') {
                    // For shoes, replace select with number input
                    const input = document.createElement('input');
                    input.type = 'number';
                    input.step = '0.5';
                    input.min = '20';
                    input.max = '30';
                    input.setAttribute('name', `products[${finalProductIndex}][sizes][${sizeIndex}][size]`);
                    input.required = true;
                    input.className = sizeSelect.className;
                    input.placeholder = '{{ __('pages.example_shoe_size') }}';
                    input.setAttribute('data-size-input', 'true');
                    sizeSelect.replaceWith(input);
                } else {
                    // Ensure select has correct name
                    if (sizeSelect) {
                        sizeSelect.setAttribute('name', `products[${finalProductIndex}][sizes][${sizeIndex}][size]`);
                    }
                    sizes.forEach(size => {
                        const option = document.createElement('option');
                        option.value = size;
                        option.textContent = size;
                        sizeSelect.appendChild(option);
                    });
                }
                
                // Remove size button
                sizeRow.querySelector('.remove-size-btn').addEventListener('click', function() {
                    sizeRow.remove();
                    // Reindex all sizes after removal to ensure sequential indices
                    updateProductIndices();
                });
                
                productCard.querySelector('.product-sizes-container').appendChild(sizeRow);
                
                // Final update to ensure all indices are correct
                updateProductIndices();
                
                if (typeof lucide !== 'undefined') {
                    lucide.createIcons();
                }
            }

            function updateSizesForProduct(productCard) {
                const category = productCard.dataset.category || productCard.querySelector('.product-category-select').value;
                const sizesContainer = productCard.querySelector('.product-sizes-container');
                const existingRows = sizesContainer.querySelectorAll('.size-row');
                
                existingRows.forEach(row => {
                    const sizeSelect = row.querySelector('.size-select');
                    if (sizeSelect) {
                        if (category === 'shoe') {
                            // Replace select with number input
                            const input = document.createElement('input');
                            input.type = 'number';
                            input.step = '0.5';
                            input.min = '20';
                            input.max = '30';
                            input.name = sizeSelect.name;
                            input.required = true;
                            input.className = sizeSelect.className;
                            input.placeholder = '{{ __('pages.example_shoe_size') }}';
                            sizeSelect.replaceWith(input);
                        } else {
                            // Update select options
                            sizeSelect.innerHTML = '<option value="">{{ __('pages.select_size') }}</option>';
                            const sizes = topBottomSizes;
                            sizes.forEach(size => {
                                const option = document.createElement('option');
                                option.value = size;
                                option.textContent = size;
                                sizeSelect.appendChild(option);
                            });
                        }
                    } else {
                        // It's already a number input (shoe), replace with select if needed
                        const input = row.querySelector('input[type="number"]');
                        if (input && category !== 'shoe') {
                            const select = document.createElement('select');
                            select.name = input.name;
                            select.required = true;
                            select.className = input.className.replace('number', 'select');
                            select.innerHTML = '<option value="">{{ __('pages.select_size') }}</option>';
                            topBottomSizes.forEach(size => {
                                const option = document.createElement('option');
                                option.value = size;
                                option.textContent = size;
                                select.appendChild(option);
                            });
                            input.replaceWith(select);
                        }
                    }
                });
            }


            // Update product indices using bracket notation (Laravel format)
            function updateProductIndices() {
                $('.product-card').each(function(productIndex) {
                    const $card = $(this);
                    
                    // Use bracket notation: products[0][product_code] instead of products[0].product_code
                    $card.find('.product-code-input').attr('name', `products[${productIndex}][product_code]`).prop('disabled', false);
                    $card.find('.product-name-input').attr('name', `products[${productIndex}][name]`).prop('disabled', false);
                    $card.find('.product-category-select').attr('name', `products[${productIndex}][category]`).prop('disabled', false);
                    $card.find('select[name*="gender"]').attr('name', `products[${productIndex}][gender]`).prop('disabled', false);
                    $card.find('.product-image-input').attr('name', `products[${productIndex}][image]`);
                    
                    // Update sizes with bracket notation
                    $card.find('.size-row').each(function(sizeIndex) {
                        const $sizeRow = $(this);
                        $sizeRow.find('.size-select, input[type="number"]').attr('name', `products[${productIndex}][sizes][${sizeIndex}][size]`).prop('disabled', false);
                        $sizeRow.find('.stock-quantity-input').attr('name', `products[${productIndex}][sizes][${sizeIndex}][stock_quantity]`).prop('disabled', false);
                    });
                });
            }

            // Form submission handler
            $('#product-form').on('submit', function(e) {
                e.preventDefault();
                
                const $form = $(this);
                const $cards = $('.product-card');
                
                // Update all product indices first
                updateProductIndices();
                
                if ($cards.length === 0) {
                    alert('{{ __('pages.add_at_least_one_product') ?? '少なくとも1つの商品を追加してください。' }}');
                    return false;
                }
                
                // Validate and set field names
                const missingFields = [];
                $cards.each(function(index) {
                    const $card = $(this);
                    const $codeInput = $card.find('.product-code-input');
                    const $nameInput = $card.find('.product-name-input');
                    const $categorySelect = $card.find('.product-category-select');
                    const $genderSelect = $card.find('select[name*="gender"]');
                    
                    // Set names - use dot notation for nested structure
                    $codeInput.attr('name', `products[${index}][product_code]`).prop('disabled', false);
                    $nameInput.attr('name', `products[${index}][name]`).prop('disabled', false);
                    $categorySelect.attr('name', `products[${index}][category]`).prop('disabled', false);
                    $genderSelect.attr('name', `products[${index}][gender]`).prop('disabled', false);
                    $card.find('.product-image-input').attr('name', `products[${index}][image]`);
                    
                    // Validate required fields
                    if (!$codeInput.val() || !$codeInput.val().trim()) {
                        missingFields.push(`{{ __('pages.product_missing_fields') }} ${index + 1}: {{ __('pages.product_code_missing') }}`);
                    }
                    if (!$nameInput.val() || !$nameInput.val().trim()) {
                        missingFields.push(`{{ __('pages.product_missing_fields') }} ${index + 1}: {{ __('pages.product_name_missing') }}`);
                    }
                    if (!$categorySelect.val()) {
                        missingFields.push(`{{ __('pages.product_missing_fields') }} ${index + 1}: {{ __('pages.category_missing') }}`);
                    }
                    if (!$genderSelect.val()) {
                        missingFields.push(`{{ __('pages.product_missing_fields') }} ${index + 1}: {{ __('pages.gender_missing') }}`);
                    }
                    
                    // Update size fields with bracket notation
                    $card.find('.size-row').each(function(sizeIdx) {
                        const $sizeRow = $(this);
                        const $sizeInput = $sizeRow.find('.size-select, input[type="number"]');
                        const $stockInput = $sizeRow.find('.stock-quantity-input');
                        
                        if ($sizeInput.length) {
                            $sizeInput.attr('name', `products[${index}][sizes][${sizeIdx}][size]`).prop('disabled', false);
                        }
                        if ($stockInput.length) {
                            $stockInput.attr('name', `products[${index}][sizes][${sizeIdx}][stock_quantity]`).prop('disabled', false);
                        }
                    });
                });
                
                if (missingFields.length > 0) {
                    alert('{{ __('pages.please_fill_fields') }}:\n\n' + missingFields.join('\n'));
                    return false;
                }
                
                // Remove any INDEX placeholders and conflicting simple array fields
                $form.find('[name*="INDEX"]').remove();
                $form.find('input, select').each(function() {
                    const name = $(this).attr('name');
                    if (name && /^products\[\d+\]$/.test(name)) {
                        console.warn('Removing conflicting field:', name, $(this));
                        $(this).remove();
                    }
                });
                
                // Verify FormData structure before submission
                const formData = new FormData(this);
                const formEntries = [];
                const simpleFields = [];
                const nestedFields = [];
                
                for (let [key, value] of formData.entries()) {
                    formEntries.push(`${key} = ${value}`);
                    if (key.startsWith('products[')) {
                        if (/^products\[\d+\]$/.test(key)) {
                            simpleFields.push({key, value});
                        } else if (key.includes('[') && key.includes(']')) {
                            nestedFields.push(key);
                        }
                    }
                }
                
                console.log('Form entries:', formEntries.filter(e => e.startsWith('products[')));
                
                // If simple fields exist, remove them
                if (simpleFields.length > 0) {
                    console.error('Found simple array fields:', simpleFields);
                    simpleFields.forEach(field => {
                        $form.find(`[name="${field.key}"]`).remove();
                    });
                }
                
                // Verify nested structure exists
                if (nestedFields.length === 0) {
                    console.error('ERROR: No nested product fields found');
                    alert('{{ __('pages.error_product_data') }}');
                    return false;
                }
                
                console.log('Submitting form with', nestedFields.length, 'nested fields');
                this.submit();
            });
        });
    </script>
@endsection
