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
                    const sizeContainer = $row.children('.min-w-0').first();
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
                                class: 'size-select w-full min-w-0 px-2.5 py-2 text-[13px] border border-slate-300 rounded-lg focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 bg-white',
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
                                class: 'size-select w-full min-w-0 px-2.5 py-2 text-[13px] border border-slate-300 rounded-lg focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 bg-white'
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
