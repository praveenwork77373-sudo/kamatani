    {{-- Product Template (Hidden) --}}
    <template id="product-card-template">
        <div class="product-card group border border-slate-200 rounded-xl overflow-hidden hover:shadow-lg transition-all duration-300 min-w-0" data-category="">
            <div class="relative h-40 bg-gradient-to-br from-slate-50 to-slate-100 overflow-hidden">
                <div class="w-full h-full flex items-center justify-center product-image-placeholder uniform-placeholder">
                    <img src="{{ asset('images/brand/logo-mark-64.png') }}" alt="" class="w-14 h-14 object-contain opacity-80">
                </div>
                <input type="file" name="products[INDEX][image]" accept="image/*" class="product-image-input hidden">
                <label class="absolute inset-0 cursor-pointer flex items-center justify-center bg-black/0 hover:bg-black/10 transition-all product-image-label">
                    <div class="opacity-0 group-hover:opacity-100 transition-opacity bg-white/90 rounded-lg px-4 py-2 text-[13px] font-medium text-slate-700">
                        <i data-lucide="image" class="w-4 h-4 inline-block mr-1"></i>
                        {{ __('pages.select_image_file') }}
                    </div>
                </label>
                <button type="button" class="product-remove-btn absolute top-2 right-2 bg-red-500 hover:bg-red-600 text-white rounded-full p-1.5 shadow-lg opacity-0 group-hover:opacity-100 transition-opacity">
                    <i data-lucide="x" class="w-4 h-4"></i>
                </button>
            </div>
            
            <div class="p-4 space-y-3 min-w-0">
                <div>
                    <input type="hidden" name="products[INDEX][product_code]" class="product-code-input" value="">
                </div>
                
                <div>
                    <label class="block text-[13px] font-semibold text-slate-700 mb-1.5">
                        {{ __('pages.product_name') }} <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="products[INDEX][name]" required
                           class="product-name-input w-full min-w-0 px-3 py-2.5 text-[14px] border border-slate-300 rounded-lg focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 transition-all bg-white"
                           placeholder="{{ __('pages.example_product_name') }}">
                </div>
                
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div class="min-w-0">
                        <label class="block text-[13px] font-semibold text-slate-700 mb-1.5">
                            {{ __('pages.category') }} <span class="text-red-500">*</span>
                        </label>
                        <select name="products[INDEX][category]" required
                                class="product-category-select w-full min-w-0 px-3 py-2.5 text-[14px] border border-slate-300 rounded-lg focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 transition-all bg-white">
                            <option value="">{{ __('pages.select_please') }}</option>
                            @foreach(($categories ?? []) as $cat)
                                <option value="{{ $cat->code }}" data-size-scheme="{{ $cat->size_scheme }}">{{ $cat->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="min-w-0">
                        <label class="block text-[13px] font-semibold text-slate-700 mb-1.5">
                            {{ __('pages.gender') }} <span class="text-red-500">*</span>
                        </label>
                        <select name="products[INDEX][gender]" required
                                class="product-gender-select w-full min-w-0 px-3 py-2.5 text-[14px] border border-slate-300 rounded-lg focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 transition-all bg-white">
                            <option value="">{{ __('pages.select_please') }}</option>
                            <option value="male">{{ __('pages.male') }}</option>
                            <option value="female">{{ __('pages.female') }}</option>
                            <option value="unisex">{{ __('pages.unisex') }}</option>
                        </select>
                    </div>
                </div>
                
                {{-- Sizes: stacked rows, grow with content (no horizontal scroll) --}}
                <div class="min-w-0">
                    <label class="block text-[13px] font-semibold text-slate-700 mb-1.5 flex items-center gap-1.5">
                        <i data-lucide="ruler" class="w-4 h-4 text-primary-600"></i>
                        {{ __('pages.sizes') }} <span class="text-red-500">*</span>
                    </label>
                    <div class="product-sizes-container space-y-2 p-2 border border-slate-200 rounded-lg bg-slate-50 min-h-[3rem] max-h-72 overflow-y-auto overflow-x-hidden">
                    </div>
                    <button type="button" class="add-size-btn mt-2 w-full px-3 py-2 rounded-lg border border-dashed border-slate-300 hover:border-primary-400 hover:bg-primary-50 text-[13px] font-medium text-slate-600 hover:text-primary-700 transition-all">
                        <i data-lucide="plus" class="w-4 h-4 inline-block mr-1"></i>
                        {{ __('pages.add_size') }}
                    </button>
                </div>
            </div>
        </div>
    </template>

    <template id="size-row-template">
        <div class="size-row grid grid-cols-[1fr_5.5rem_auto] gap-2 items-end p-2 bg-white rounded-lg border border-slate-200 min-w-0">
            <div class="min-w-0">
                <label class="block text-[11px] font-medium text-slate-500 mb-1">{{ __('pages.size') }}</label>
                <select name="products[INDEX][sizes][SIZE_INDEX][size]" required
                        class="size-select w-full min-w-0 px-2.5 py-2 text-[13px] border border-slate-300 rounded-lg focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 bg-white">
                    <option value="">{{ __('pages.select_size') }}</option>
                </select>
            </div>
            <div class="min-w-0">
                <label class="block text-[11px] font-medium text-slate-500 mb-1">{{ __('pages.stock') }}</label>
                <input type="number" name="products[INDEX][sizes][SIZE_INDEX][stock_quantity]" required min="0" value="0"
                       class="stock-quantity-input w-full min-w-0 px-2 py-2 text-[13px] border border-slate-300 rounded-lg focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 bg-white text-center"
                       placeholder="0">
            </div>
            <button type="button" class="remove-size-btn text-red-500 hover:text-red-700 p-2 mb-0.5 flex items-center justify-center rounded-lg hover:bg-red-50" title="{{ __('common.delete') }}">
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
            const categorySizeSchemes = @json(($categorySizeSchemes ?? collect())->toArray());
            let productIndex = 0;
            const productsContainer = document.getElementById('products-container');
            const addProductBtn = document.getElementById('add-product-btn');
            let currentCategory = '';

            function sizeSchemeFor(category) {
                return categorySizeSchemes[category] || 'letter';
            }

            function schemeForCard(productCard) {
                const select = productCard.querySelector('.product-category-select');
                const selected = select?.selectedOptions?.[0];
                if (selected?.dataset?.sizeScheme) {
                    return selected.dataset.sizeScheme;
                }
                return sizeSchemeFor(select?.value || productCard.dataset.category || '');
            }

            function sizeFieldInRow(row) {
                return row.querySelector('.size-select, input[data-size-input="true"]');
            }

            // Update current category when category select changes
            document.addEventListener('change', function(e) {
                if (e.target.classList.contains('product-category-select')) {
                    currentCategory = e.target.value || '';
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
                card.dataset.sizeScheme = schemeForCard(card);
                
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
                const scheme = schemeForCard(productCard);
                
                const allCards = productsContainer.querySelectorAll('.product-card');
                const actualProductIndex = Array.from(allCards).indexOf(productCard);
                const finalProductIndex = actualProductIndex >= 0 ? actualProductIndex : productIndex;
                
                const existingRows = productCard.querySelectorAll('.size-row');
                const sizeIndex = existingRows.length;
                
                const stockInput = sizeRow.querySelector('.stock-quantity-input');
                if (stockInput) {
                    stockInput.setAttribute('name', `products[${finalProductIndex}][sizes][${sizeIndex}][stock_quantity]`);
                }
                
                if (scheme === 'shoe') {
                    const input = document.createElement('input');
                    input.type = 'number';
                    input.step = '0.5';
                    input.min = '20';
                    input.max = '30';
                    input.setAttribute('name', `products[${finalProductIndex}][sizes][${sizeIndex}][size]`);
                    input.required = true;
                    input.className = 'size-select w-full min-w-0 px-2.5 py-2 text-[13px] border border-slate-300 rounded-lg focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 bg-white';
                    input.placeholder = '{{ __('pages.example_shoe_size') }}';
                    input.setAttribute('data-size-input', 'true');
                    sizeSelect.replaceWith(input);
                } else {
                    sizeSelect.setAttribute('name', `products[${finalProductIndex}][sizes][${sizeIndex}][size]`);
                    topBottomSizes.forEach(size => {
                        const option = document.createElement('option');
                        option.value = size;
                        option.textContent = size;
                        sizeSelect.appendChild(option);
                    });
                }
                
                sizeRow.querySelector('.remove-size-btn').addEventListener('click', function() {
                    sizeRow.remove();
                    updateProductIndices();
                });
                
                productCard.querySelector('.product-sizes-container').appendChild(sizeRow);
                updateProductIndices();
                
                if (typeof lucide !== 'undefined') {
                    lucide.createIcons();
                }
            }

            function updateSizesForProduct(productCard) {
                const scheme = schemeForCard(productCard);
                const prevScheme = productCard.dataset.sizeScheme || '';
                productCard.dataset.category = productCard.querySelector('.product-category-select')?.value || '';

                const sizesContainer = productCard.querySelector('.product-sizes-container');
                const rows = [...sizesContainer.querySelectorAll('.size-row')];

                // Switching between letter and shoe: rebuild so letter sizes (S/M/L)
                // never stay on a shoe product (and vice versa).
                if (prevScheme && prevScheme !== scheme) {
                    sizesContainer.innerHTML = '';
                    const cards = productsContainer.querySelectorAll('.product-card');
                    const currentIndex = Array.from(cards).indexOf(productCard);
                    productCard.dataset.sizeScheme = scheme;
                    addSizeRow(productCard, currentIndex);
                    return;
                }

                productCard.dataset.sizeScheme = scheme;

                // First category pick (or same scheme): convert each row in place
                rows.forEach(row => {
                    const field = sizeFieldInRow(row);
                    if (!field) return;
                    const name = field.getAttribute('name') || '';
                    const oldVal = field.value;

                    if (scheme === 'shoe') {
                        if (field.tagName === 'INPUT' && field.type === 'number') {
                            return;
                        }
                        const input = document.createElement('input');
                        input.type = 'number';
                        input.step = '0.5';
                        input.min = '20';
                        input.max = '30';
                        input.setAttribute('name', name);
                        input.required = true;
                        input.className = 'size-select w-full min-w-0 px-2.5 py-2 text-[13px] border border-slate-300 rounded-lg focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 bg-white';
                        input.placeholder = '{{ __('pages.example_shoe_size') }}';
                        input.setAttribute('data-size-input', 'true');
                        if (oldVal && !Number.isNaN(parseFloat(oldVal))) {
                            input.value = oldVal;
                        }
                        field.replaceWith(input);
                    } else {
                        if (field.tagName === 'SELECT') {
                            const current = field.value;
                            field.innerHTML = '<option value="">{{ __('pages.select_size') }}</option>';
                            topBottomSizes.forEach(size => {
                                const option = document.createElement('option');
                                option.value = size;
                                option.textContent = size;
                                if (size === current) option.selected = true;
                                field.appendChild(option);
                            });
                            return;
                        }
                        const select = document.createElement('select');
                        select.setAttribute('name', name);
                        select.required = true;
                        select.className = 'size-select w-full min-w-0 px-2.5 py-2 text-[13px] border border-slate-300 rounded-lg focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 bg-white';
                        select.innerHTML = '<option value="">{{ __('pages.select_size') }}</option>';
                        topBottomSizes.forEach(size => {
                            const option = document.createElement('option');
                            option.value = size;
                            option.textContent = size;
                            if (size === oldVal) option.selected = true;
                            select.appendChild(option);
                        });
                        field.replaceWith(select);
                    }
                });

                updateProductIndices();
            }


            // Update product indices using bracket notation (Laravel format)
            function updateProductIndices() {
                $('.product-card').each(function(productIndex) {
                    const $card = $(this);
                    
                    $card.find('.product-code-input').attr('name', `products[${productIndex}][product_code]`).prop('disabled', false);
                    $card.find('.product-name-input').attr('name', `products[${productIndex}][name]`).prop('disabled', false);
                    $card.find('.product-category-select').attr('name', `products[${productIndex}][category]`).prop('disabled', false);
                    $card.find('.product-gender-select').attr('name', `products[${productIndex}][gender]`).prop('disabled', false);
                    $card.find('.product-image-input').attr('name', `products[${productIndex}][image]`);
                    
                    $card.find('.size-row').each(function(sizeIndex) {
                        const $sizeRow = $(this);
                        $sizeRow.find('.size-select, input[data-size-input="true"]').attr('name', `products[${productIndex}][sizes][${sizeIndex}][size]`).prop('disabled', false);
                        $sizeRow.find('.stock-quantity-input').attr('name', `products[${productIndex}][sizes][${sizeIndex}][stock_quantity]`).prop('disabled', false);
                    });
                });
            }

            // Form submission handler
            $('#product-form').on('submit', function(e) {
                e.preventDefault();
                
                const $form = $(this);
                const $cards = $('.product-card');
                
                updateProductIndices();
                
                if ($cards.length === 0) {
                    alert('{{ __('pages.add_at_least_one_product') ?? '少なくとも1つの商品を追加してください。' }}');
                    return false;
                }
                
                const missingFields = [];
                $cards.each(function(index) {
                    const $card = $(this);
                    const $nameInput = $card.find('.product-name-input');
                    const $categorySelect = $card.find('.product-category-select');
                    const $genderSelect = $card.find('.product-gender-select');
                    
                    $card.find('.product-code-input').attr('name', `products[${index}][product_code]`).prop('disabled', false);
                    $nameInput.attr('name', `products[${index}][name]`).prop('disabled', false);
                    $categorySelect.attr('name', `products[${index}][category]`).prop('disabled', false);
                    $genderSelect.attr('name', `products[${index}][gender]`).prop('disabled', false);
                    $card.find('.product-image-input').attr('name', `products[${index}][image]`);
                    
                    if (!$nameInput.val() || !$nameInput.val().trim()) {
                        missingFields.push(`{{ __('pages.product_missing_fields') }} ${index + 1}: {{ __('pages.product_name_missing') }}`);
                    }
                    if (!$categorySelect.val()) {
                        missingFields.push(`{{ __('pages.product_missing_fields') }} ${index + 1}: {{ __('pages.category_missing') }}`);
                    }
                    if (!$genderSelect.val()) {
                        missingFields.push(`{{ __('pages.product_missing_fields') }} ${index + 1}: {{ __('pages.gender_missing') }}`);
                    }

                    const $sizeRows = $card.find('.size-row');
                    if ($sizeRows.length === 0) {
                        missingFields.push(`{{ __('pages.product_missing_fields') }} ${index + 1}: {{ __('pages.sizes') }}`);
                    }

                    $sizeRows.each(function(sizeIdx) {
                        const $sizeRow = $(this);
                        const $sizeInput = $sizeRow.find('.size-select, input[data-size-input="true"]');
                        const $stockInput = $sizeRow.find('.stock-quantity-input');
                        
                        if ($sizeInput.length) {
                            $sizeInput.attr('name', `products[${index}][sizes][${sizeIdx}][size]`).prop('disabled', false);
                            if ($sizeInput.val() === null || String($sizeInput.val()).trim() === '') {
                                missingFields.push(`{{ __('pages.product_missing_fields') }} ${index + 1}: {{ __('pages.size') }}`);
                            }
                        }
                        if ($stockInput.length) {
                            $stockInput.attr('name', `products[${index}][sizes][${sizeIdx}][stock_quantity]`).prop('disabled', false);
                            if ($stockInput.val() === null || String($stockInput.val()).trim() === '') {
                                missingFields.push(`{{ __('pages.product_missing_fields') }} ${index + 1}: {{ __('pages.stock') }}`);
                            }
                        }
                    });
                });
                
                if (missingFields.length > 0) {
                    alert('{{ __('pages.please_fill_fields') }}:\n\n' + [...new Set(missingFields)].join('\n'));
                    return false;
                }
                
                $form.find('[name*="INDEX"]').remove();
                $form.find('input, select').each(function() {
                    const name = $(this).attr('name');
                    if (name && /^products\[\d+\]$/.test(name)) {
                        $(this).remove();
                    }
                });
                
                const formData = new FormData(this);
                const simpleFields = [];
                const nestedFields = [];
                
                for (let [key, value] of formData.entries()) {
                    if (key.startsWith('products[')) {
                        if (/^products\[\d+\]$/.test(key)) {
                            simpleFields.push({key, value});
                        } else if (key.includes('[') && key.includes(']')) {
                            nestedFields.push(key);
                        }
                    }
                }
                
                if (simpleFields.length > 0) {
                    simpleFields.forEach(field => {
                        $form.find(`[name="${field.key}"]`).remove();
                    });
                }
                
                if (nestedFields.length === 0) {
                    alert('{{ __('pages.error_product_data') }}');
                    return false;
                }
                
                this.submit();
            });
        });
    </script>
