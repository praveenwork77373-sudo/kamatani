@extends('layouts.app')

@section('title', __('pages.uniform_management') . ' | ' . __('auth.system_title'))
@section('page_title', __('pages.uniform_management'))

@section('content')
    {{-- Low Stock Alert Banner --}}
    @if(isset($lowStockWarnings) && count($lowStockWarnings) > 0)
        @php
            $veryLowStock = array_filter($lowStockWarnings, fn($w) => $w['is_very_low']);
            $lowStock = array_filter($lowStockWarnings, fn($w) => !$w['is_very_low']);
        @endphp
        
        @if(count($veryLowStock) > 0)
            <div class="mb-6 p-4 bg-red-50 border-l-4 border-red-500 rounded-lg shadow-sm">
                <div class="flex items-start gap-3">
                    <i data-lucide="alert-triangle" class="w-5 h-5 text-red-600 flex-shrink-0 mt-0.5"></i>
                    <div class="flex-1">
                        <h3 class="text-[14px] font-semibold text-red-900 mb-2">{{ __('pages.urgent_restock_needed') }}</h3>
                        <div class="space-y-1.5">
                            @foreach(array_slice($veryLowStock, 0, 5) as $warning)
                                <p class="text-[13px] text-red-800">
                                    <span class="font-semibold">{{ $warning['product_name'] }}</span> 
                                    (<span class="font-mono text-[12px]">{{ $warning['product_code'] }}</span>): 
                                    <span class="font-bold">{{ $warning['size'] }}</span> {{ __('pages.stock_is_needed') }}! 
                                    <span class="text-red-600">({{ $warning['stock'] }} {{ __('pages.items') }})</span>
                                </p>
                            @endforeach
                            @if(count($veryLowStock) > 5)
                                <p class="text-[12px] text-red-700 italic">
                                    + {{ count($veryLowStock) - 5 }} {{ __('pages.more_items') }}
                                </p>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        @elseif(count($lowStock) > 0)
            <div class="mb-6 p-4 bg-amber-50 border-l-4 border-amber-500 rounded-lg shadow-sm">
                <div class="flex items-start gap-3">
                    <i data-lucide="alert-circle" class="w-5 h-5 text-amber-600 flex-shrink-0 mt-0.5"></i>
                    <div class="flex-1">
                        <h3 class="text-[14px] font-semibold text-amber-900 mb-2">{{ __('pages.restock_recommended') }}</h3>
                        <div class="space-y-1.5">
                            @foreach(array_slice($lowStock, 0, 5) as $warning)
                                <p class="text-[13px] text-amber-800">
                                    <span class="font-semibold">{{ $warning['product_name'] }}</span> 
                                    (<span class="font-mono text-[12px]">{{ $warning['product_code'] }}</span>): 
                                    <span class="font-bold">{{ $warning['size'] }}</span> {{ __('pages.stock_is_needed') }}! 
                                    <span class="text-amber-600">({{ $warning['stock'] }} {{ __('pages.items') }})</span>
                                </p>
                            @endforeach
                            @if(count($lowStock) > 5)
                                <p class="text-[12px] text-amber-700 italic">
                                    + {{ count($lowStock) - 5 }} {{ __('pages.more_items') }}
                                </p>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        @endif
    @endif

    {{-- Header --}}
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 tracking-tight">{{ __('pages.uniform_product_list') }}</h1>
            <p class="text-[14px] text-slate-600 mt-1.5">{{ __('pages.uniform_management_description') }}</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.uniform-products.export', request()->query()) }}" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-lg border border-slate-300 text-slate-700 hover:bg-slate-50 text-[14px] font-medium transition-all">
                {{ __('pages.csv_export') }}
            </a>
            <a href="{{ route('admin.uniform-products.create') }}" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-lg bg-primary-600 hover:bg-primary-700 text-white text-[14px] font-medium shadow-md transition-all">
                <i class="ph ph-plus text-base"></i>
                {{ __('pages.new_registration') }}
            </a>
        </div>
    </div>

    {{-- Statistics Cards --}}
    <x-stat-grid :cols="4">
        <x-stat-card tone="blue" icon="package" :value="$stats['total_products'] ?? 0" :label="__('pages.total_products')" :badge="__('pages.total')" />
        <x-stat-card tone="emerald" icon="layers" :value="$stats['total_stock'] ?? 0" :label="__('pages.total_stock')" badge="在庫" />
        <x-stat-card tone="green" icon="check-circle" :value="$stats['in_stock_products'] ?? 0" :label="__('pages.in_stock_products')" :badge="__('pages.in_stock')" />
        <x-stat-card tone="red" icon="x-circle" :value="$stats['out_of_stock_products'] ?? 0" :label="__('pages.out_of_stock_products')" :badge="__('pages.out_of_stock')" />
    </x-stat-grid>

    <div class="space-y-6">
        @php
            $productFiltersActive = filled($searchQuery) || filled($selectedStatus) || filled($selectedGender) || filled($selectedCategory);
        @endphp
        <x-filter-bar :action="route('admin.uniform-products.index')" form-id="filter-form" :active="$productFiltersActive">
            <x-slot:before>
                <input type="hidden" name="category" id="category-filter" value="{{ $selectedCategory }}">
            </x-slot:before>
            <div class="ui-filter-field ui-filter-field--grow">
                <label class="ui-filter-field__label">{{ __('pages.search') }}</label>
                <input type="text" name="search" value="{{ $searchQuery }}"
                       placeholder="{{ __('pages.search_product') }}"
                       class="ui-input">
            </div>
            <div class="ui-filter-field ui-filter-field--sm">
                <label class="ui-filter-field__label">{{ __('pages.status') }}</label>
                <select name="status" class="ui-select">
                    <option value="">{{ __('pages.all') }}</option>
                    <option value="in_stock" @selected($selectedStatus === 'in_stock')>{{ __('pages.in_stock') }}</option>
                    <option value="out_of_stock" @selected($selectedStatus === 'out_of_stock')>{{ __('pages.out_of_stock') }}</option>
                </select>
            </div>
            <div class="ui-filter-field ui-filter-field--sm">
                <label class="ui-filter-field__label">{{ __('pages.gender') }}</label>
                <select name="gender" class="ui-select">
                    <option value="">{{ __('pages.all') }}</option>
                    <option value="male" @selected($selectedGender === 'male')>{{ __('pages.male') }}</option>
                    <option value="female" @selected($selectedGender === 'female')>{{ __('pages.female') }}</option>
                    <option value="unisex" @selected($selectedGender === 'unisex')>{{ __('pages.unisex') }}</option>
                </select>
            </div>
            <x-slot:actions>
                <button type="submit" class="ui-btn ui-btn-primary">
                    <i data-lucide="search" class="w-4 h-4"></i>
                    {{ __('pages.apply_filters') }}
                </button>
                <a href="{{ route('admin.uniform-products.index') }}" class="ui-btn ui-btn-secondary" title="{{ __('common.reset') }}">
                    <i data-lucide="x" class="w-4 h-4"></i>
                </a>
            </x-slot:actions>
        </x-filter-bar>

        {{-- Full-width products content --}}
        <div>
            {{-- Category Tabs --}}
            <div class="sticky top-0 z-10 bg-white mb-6 pt-3 pb-3 border-b-2 border-slate-200 shadow-sm">
                <div class="flex items-center gap-2 overflow-x-auto scrollbar-thin scrollbar-thumb-slate-300 scrollbar-track-transparent">
                    <button type="button" class="category-tab flex-shrink-0 px-4 py-2.5 text-[14px] font-medium border-b-2 transition-all flex items-center gap-1.5 {{ !$selectedCategory ? 'border-primary-600 text-primary-600' : 'border-transparent text-slate-700 hover:text-primary-600' }}"
                            data-category="">
                        <i data-lucide="grid" class="w-4 h-4"></i>
                        {{ __('pages.all_categories') }}
                    </button>
                    @foreach(($categories ?? []) as $cat)
                        <button type="button" class="category-tab flex-shrink-0 px-4 py-2.5 text-[14px] font-medium border-b-2 transition-all flex items-center gap-1.5 {{ $selectedCategory === $cat->code ? 'border-primary-600 text-primary-600' : 'border-transparent text-slate-700 hover:text-primary-600' }}"
                                data-category="{{ $cat->code }}">
                            <i data-lucide="tag" class="w-4 h-4"></i>
                            {{ $cat->name }}
                        </button>
                    @endforeach
                </div>
            </div>

            {{-- Products Grid --}}
            <div id="products-container">
                @if($products->count() > 0)
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                        @foreach($products as $product)
                            @php
                                $isOutOfStock = !$product['has_stock'];
                                $isLowStock = $product['total_stock'] > 0 && $product['total_stock'] <= 10;
                                $isVeryLowStock = $product['total_stock'] > 0 && $product['total_stock'] <= 5;
                            @endphp
                            <div class="group border rounded-xl overflow-hidden hover:shadow-lg transition-all duration-300 {{ $isOutOfStock ? 'border-red-300 opacity-60' : ($isVeryLowStock ? 'border-amber-400' : ($isLowStock ? 'border-amber-300' : 'border-slate-200 hover:border-primary-300')) }}" data-category="{{ $product['category'] }}">
                                {{-- Product Image --}}
                                <div class="relative h-48 bg-gradient-to-br from-slate-50 to-slate-100 overflow-hidden">
                                    @if($product['image_path'])
                                        <img src="{{ $product['image_path'] }}" alt="{{ $product['name'] }}"
                                             class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
                                    @else
                                        <div class="w-full h-full flex items-center justify-center">
                                            <i data-lucide="shirt" class="w-16 h-16 text-slate-300"></i>
                                        </div>
                                    @endif

                                    {{-- Stock Status Badge --}}
                                    @if($isOutOfStock)
                                        <div class="absolute top-2 right-2 px-2 py-1 bg-red-500 text-white text-[11px] font-semibold rounded-md shadow-md">
                                            <i data-lucide="x-circle" class="w-3 h-3 inline-block mr-1"></i>
                                            {{ __('pages.out_of_stock') }}
                                        </div>
                                    @elseif($isVeryLowStock)
                                        <div class="absolute top-2 right-2 px-2 py-1 bg-red-500 text-white text-[11px] font-semibold rounded-md shadow-md">
                                            <i data-lucide="alert-triangle" class="w-3 h-3 inline-block mr-1"></i>
                                            {{ __('pages.very_low_stock') }}
                                        </div>
                                    @elseif($isLowStock)
                                        <div class="absolute top-2 right-2 px-2 py-1 bg-amber-500 text-white text-[11px] font-semibold rounded-md shadow-md">
                                            <i data-lucide="alert-circle" class="w-3 h-3 inline-block mr-1"></i>
                                            {{ __('pages.low_stock') }}
                                        </div>
                                    @endif
                                </div>

                                {{-- Product Info --}}
                                <div class="p-5 space-y-3">
                                    <div>
                                        <h3 class="font-semibold text-slate-900 text-[15px] mb-1 line-clamp-2 leading-tight">{{ $product['name'] }}</h3>
                                        <p class="text-[11px] text-slate-500 font-mono">{{ $product['base_product_code'] }}</p>
                                    </div>

                                    {{-- Sizes List --}}
                                    <div>
                                        <label class="block text-[12px] font-semibold text-slate-700 mb-2 flex items-center gap-1.5">
                                            <i data-lucide="ruler" class="w-3.5 h-3.5 text-primary-600"></i>
                                            {{ __('pages.available_sizes') }}
                                        </label>
                                        <div class="flex flex-wrap gap-1.5">
                                            @foreach($product['sizes'] as $sizeInfo)
                                                @php
                                                    $sizeStock = $sizeInfo['stock_quantity'];
                                                    $sizeIsLow = $sizeStock > 0 && $sizeStock <= 5;
                                                    $sizeIsVeryLow = $sizeStock > 0 && $sizeStock <= 2;
                                                @endphp
                                                <span class="px-2 py-1 text-[11px] rounded-md font-medium {{ $sizeStock <= 0 ? 'bg-slate-100 text-slate-500 border border-slate-300' : ($sizeIsVeryLow ? 'bg-red-100 text-red-700 border border-red-300' : ($sizeIsLow ? 'bg-amber-100 text-amber-700 border border-amber-300' : 'bg-primary-100 text-primary-700 border border-primary-300')) }}">
                                                    {{ $sizeInfo['size'] }} ({{ $sizeStock }})
                                                </span>
                                            @endforeach
                                        </div>
                                    </div>

                                    {{-- Product Meta --}}
                                    <div class="flex items-center justify-between text-[12px] text-slate-600 pt-2 border-t border-slate-200">
                                        <div class="flex items-center gap-1.5">
                                            <i data-lucide="tag" class="w-3.5 h-3.5"></i>
                                            <span>
                                                @if($product['gender'] === 'male')
                                                    {{ __('pages.male') }}
                                                @elseif($product['gender'] === 'female')
                                                    {{ __('pages.female') }}
                                                @else
                                                    {{ __('pages.unisex') }}
                                                @endif
                                            </span>
                                        </div>
                                        <div class="flex items-center gap-1.5">
                                            <i data-lucide="package" class="w-3.5 h-3.5"></i>
                                            <span class="font-semibold {{ $isVeryLowStock ? 'text-red-600' : ($isLowStock ? 'text-amber-600' : 'text-slate-900') }}">
                                                {{ $product['total_stock'] }}
                                                @if($isVeryLowStock)
                                                    <i data-lucide="alert-triangle" class="w-3.5 h-3.5 inline-block ml-1 text-red-500"></i>
                                                @elseif($isLowStock)
                                                    <i data-lucide="alert-circle" class="w-3.5 h-3.5 inline-block ml-1 text-amber-500"></i>
                                                @endif
                                            </span>
                                        </div>
                                    </div>

                                    {{-- Low Stock Warning Message --}}
                                    @php
                                        $lowStockSizes = [];
                                        $veryLowStockSizes = [];
                                        foreach ($product['sizes'] as $sizeInfo) {
                                            $sizeStock = $sizeInfo['stock_quantity'];
                                            $size = $sizeInfo['size'];
                                            if ($sizeStock > 0 && $sizeStock <= 2) {
                                                $veryLowStockSizes[] = $size;
                                            } elseif ($sizeStock > 0 && $sizeStock <= 5) {
                                                $lowStockSizes[] = $size;
                                            }
                                        }
                                    @endphp

                                    @if(count($veryLowStockSizes) > 0)
                                        <div class="mt-2 p-3 bg-red-50 border-l-4 border-red-500 rounded-lg shadow-sm">
                                            <div class="flex items-start gap-2">
                                                <i data-lucide="alert-triangle" class="w-4 h-4 text-red-600 flex-shrink-0 mt-0.5"></i>
                                                <div class="flex-1">
                                                    <p class="text-[12px] font-semibold text-red-800 mb-1">{{ __('pages.urgent_restock_needed') }}</p>
                                                    <p class="text-[11px] text-red-700 leading-relaxed">
                                                        @foreach($veryLowStockSizes as $size)
                                                            <span class="inline-block mr-2">
                                                                <span class="font-bold">{{ $size }}</span> {{ __('pages.stock_is_needed') }}!
                                                            </span>
                                                        @endforeach
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                    @elseif(count($lowStockSizes) > 0)
                                        <div class="mt-2 p-3 bg-amber-50 border-l-4 border-amber-500 rounded-lg shadow-sm">
                                            <div class="flex items-start gap-2">
                                                <i data-lucide="alert-circle" class="w-4 h-4 text-amber-600 flex-shrink-0 mt-0.5"></i>
                                                <div class="flex-1">
                                                    <p class="text-[12px] font-semibold text-amber-800 mb-1">{{ __('pages.restock_recommended') }}</p>
                                                    <p class="text-[11px] text-amber-700 leading-relaxed">
                                                        @foreach($lowStockSizes as $size)
                                                            <span class="inline-block mr-2">
                                                                <span class="font-bold">{{ $size }}</span> {{ __('pages.stock_is_needed') }}!
                                                            </span>
                                                        @endforeach
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                    @elseif($isVeryLowStock)
                                        <div class="mt-2 p-2 bg-red-50 border border-red-200 rounded-lg">
                                            <p class="text-[11px] text-red-700 font-medium flex items-center gap-1.5">
                                                <i data-lucide="alert-triangle" class="w-3.5 h-3.5 text-red-600"></i>
                                                {{ __('pages.very_low_stock_warning') }}
                                            </p>
                                        </div>
                                    @elseif($isLowStock)
                                        <div class="mt-2 p-2 bg-amber-50 border border-amber-200 rounded-lg">
                                            <p class="text-[11px] text-amber-700 font-medium flex items-center gap-1.5">
                                                <i data-lucide="alert-circle" class="w-3.5 h-3.5 text-amber-600"></i>
                                                {{ __('pages.low_stock_warning') }}
                                            </p>
                                        </div>
                                    @endif

                                    {{-- Actions --}}
                                    @if(count($product['sizes']) > 0)
                                    <div class="flex items-center gap-2 pt-2 border-t border-slate-200">
                                        <a href="{{ route('admin.uniform-products.edit', $product['sizes'][0]['id']) }}"
                                           class="flex-1 text-center px-3 py-2 rounded-lg border border-slate-300 text-slate-700 hover:bg-slate-50 hover:border-slate-400 text-[12px] font-medium transition-all flex items-center justify-center gap-1.5">
                                            <i data-lucide="edit" class="w-3.5 h-3.5"></i>
                                            {{ __('pages.edit') }}
                                        </a>
                                        <form method="POST" action="{{ route('admin.uniform-products.destroy', $product['sizes'][0]['id']) }}"
                                              class="inline"
                                              data-ui-confirm="{{ __('pages.confirm_delete_product') }}"
                                              data-ui-confirm-title="{{ __('pages.confirm_action_title_delete') }}"
                                              data-ui-confirm-tone="danger"
                                              data-ui-confirm-ok="{{ __('common.delete') }}">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="px-3 py-2 rounded-lg border border-red-300 text-red-700 hover:bg-red-50 hover:border-red-400 text-[12px] font-medium transition-all flex items-center justify-center">
                                                <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                                            </button>
                                        </form>
                                    </div>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-12 bg-white border border-slate-200 rounded-xl">
                        <i data-lucide="package" class="w-16 h-16 mx-auto mb-4 text-slate-300"></i>
                        <p class="text-slate-500 text-[14px]">{{ __('pages.no_products_found') }}</p>
                    </div>
                @endif
            </div>
            <x-table-pagination :paginator="$products" class="mt-4 bg-white border border-slate-200 rounded-xl shadow-sm overflow-hidden" />
        </div>
    </div>

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize Lucide icons
            if (typeof lucide !== 'undefined') {
                lucide.createIcons();
            }

            // Category tabs
            const categoryTabs = document.querySelectorAll('.category-tab');
            const categoryFilter = document.getElementById('category-filter');
            
            categoryTabs.forEach(tab => {
                tab.addEventListener('click', function() {
                    categoryTabs.forEach(t => {
                        t.classList.remove('border-primary-600', 'text-primary-600');
                        t.classList.add('border-transparent', 'text-slate-700');
                    });
                    this.classList.add('border-primary-600', 'text-primary-600');
                    this.classList.remove('border-transparent', 'text-slate-700');
                    
                    const category = this.dataset.category;
                    categoryFilter.value = category;
                    
                    // Submit form to apply filter
                    document.getElementById('filter-form').submit();
                });
            });

            // Filter products by category on page load
            const currentCategory = '{{ $selectedCategory }}';
            if (currentCategory) {
                filterProductsByCategory(currentCategory);
            }

            function filterProductsByCategory(category) {
                // Only select product cards within the products container, not the tab buttons
                const products = document.querySelectorAll('#products-container [data-category]');
                products.forEach(product => {
                    const productCategory = product.dataset.category;
                    if (category === '' || productCategory === category) {
                        product.style.display = '';
                    } else {
                        product.style.display = 'none';
                    }
                });
            }
        });
    </script>
    @endpush
@endsection
