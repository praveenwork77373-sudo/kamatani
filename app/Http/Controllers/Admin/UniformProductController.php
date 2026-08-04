<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Maker;
use App\Models\UniformCategory;
use App\Models\UniformProduct;
use App\Support\MasterCodeGenerator;
use App\Support\Pagination;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UniformProductController extends Controller
{
    public function index(Request $request): View
    {
        $query = UniformProduct::query();

        // Search filter
        if ($search = $request->string('search')->toString()) {
            $query->where(function ($q) use ($search) {
                $q->where('product_code', 'like', "%{$search}%")
                  ->orWhere('name', 'like', "%{$search}%")
                  ->orWhere('maker_product_code', 'like', "%{$search}%")
                  ->orWhereHas('maker', function ($mq) use ($search) {
                      $mq->where('name', 'like', "%{$search}%")
                         ->orWhere('code', 'like', "%{$search}%");
                  });
            });
        }

        // Category filter
        if ($category = $request->string('category')->toString()) {
            $query->where('category', $category);
        }

        // Status filter (in stock / out of stock)
        if ($status = $request->string('status')->toString()) {
            if ($status === 'in_stock') {
                $query->where('stock_quantity', '>', 0);
            } elseif ($status === 'out_of_stock') {
                $query->where('stock_quantity', '<=', 0);
            }
        }

        // Size filter
        if ($size = $request->string('size')->toString()) {
            $query->where('size', $size);
        }

        // Gender filter
        if ($gender = $request->string('gender')->toString()) {
            $query->where('gender', $gender);
        }

        // Get all products matching filters
        $allProducts = $query->orderBy('product_code')->get();

        // Group products by base product_code (remove size suffix)
        // Products are stored as BASE_CODE-SIZE (e.g., "TOP-M-L" where "TOP-M" is base, "L" is size)
        $groupedProducts = $allProducts->groupBy(function($product) {
            // Extract base product_code by removing size suffix
            // Format: BASE_CODE-SIZE, so remove last dash and everything after
            $code = $product->product_code;
            $lastDashPos = strrpos($code, '-');
            if ($lastDashPos !== false) {
                return substr($code, 0, $lastDashPos);
            }
            return $code;
        })->map(function($group, $baseCode) {
            $first = $group->first();
            $sizes = $group->map(function($p) {
                return [
                    'size' => $p->size,
                    'stock_quantity' => $p->stock_quantity,
                    'id' => $p->id,
                ];
            })->sortBy(function($item) use ($first) {
                // Sort sizes appropriately
                if (UniformCategory::sizeSchemeFor($first->category) === UniformCategory::SIZE_SHOE) {
                    return (float)$item['size'];
                }
                $order = ['S' => 1, 'M' => 2, 'L' => 3, 'XL' => 4, 'XXL' => 5];
                return $order[$item['size']] ?? 99;
            })->values();

            return [
                'base_product_code' => $baseCode, // Use the extracted base code
                'name' => $first->name,
                'category' => $first->category,
                'category_label' => UniformCategory::labelFor($first->category),
                'size_scheme' => UniformCategory::sizeSchemeFor($first->category),
                'gender' => $first->gender,
                'image_path' => $first->image_path ? asset('storage/'.$first->image_path) : null,
                'sizes' => $sizes,
                'total_stock' => $group->sum('stock_quantity'),
                'has_stock' => $group->sum('stock_quantity') > 0,
            ];
        })->values();

        [$topBottomSizes, $shoeSizes] = $this->standardSizeLists();

        // Calculate statistics
        $totalProducts = UniformProduct::count();
        $totalStock = UniformProduct::sum('stock_quantity');
        $inStockProducts = UniformProduct::where('stock_quantity', '>', 0)->count();
        $outOfStockProducts = UniformProduct::where('stock_quantity', '<=', 0)->count();
        
        // Collect low stock warnings (sizes that need restocking)
        $lowStockWarnings = [];
        foreach ($groupedProducts as $product) {
            foreach ($product['sizes'] as $sizeInfo) {
                $sizeStock = $sizeInfo['stock_quantity'];
                $size = $sizeInfo['size'];
                if ($sizeStock > 0 && $sizeStock <= 5) {
                    $lowStockWarnings[] = [
                        'product_name' => $product['name'],
                        'product_code' => $product['base_product_code'],
                        'size' => $size,
                        'stock' => $sizeStock,
                        'is_very_low' => $sizeStock <= 2,
                    ];
                }
            }
        }

        $perPage = Pagination::perPage($request);
        $page = LengthAwarePaginator::resolveCurrentPage();
        $products = new LengthAwarePaginator(
            $groupedProducts->forPage($page, $perPage)->values(),
            $groupedProducts->count(),
            $perPage,
            $page,
            [
                'path' => $request->url(),
                'query' => $request->query(),
            ]
        );

        return view('admin.uniform-products.index', [
            'products' => $products,
            'categories' => UniformCategory::activeOrdered(),
            'selectedCategory' => $category,
            'selectedStatus' => $status,
            'selectedSize' => $size,
            'selectedGender' => $request->input('gender'),
            'searchQuery' => $search,
            'topBottomSizes' => $topBottomSizes,
            'shoeSizes' => $shoeSizes,
            'lowStockWarnings' => $lowStockWarnings,
            'stats' => [
                'total_products' => $totalProducts,
                'total_stock' => $totalStock,
                'in_stock_products' => $inStockProducts,
                'out_of_stock_products' => $outOfStockProducts,
            ],
        ]);
    }

    public function exportCsv(Request $request)
    {
        $query = UniformProduct::with('maker')->orderBy('product_code');

        if ($search = $request->string('search')->toString()) {
            $query->where(function ($q) use ($search) {
                $q->where('product_code', 'like', "%{$search}%")
                  ->orWhere('name', 'like', "%{$search}%")
                  ->orWhere('maker_product_code', 'like', "%{$search}%")
                  ->orWhereHas('maker', function ($mq) use ($search) {
                      $mq->where('name', 'like', "%{$search}%")
                         ->orWhere('code', 'like', "%{$search}%");
                  });
            });
        }

        $rows = $query->get();
        $filename = 'uniform_products_'.now()->format('Ymd_His').'.csv';

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['商品コード', '商品名', 'カテゴリ', 'サイズ', '性別', '在庫', 'メーカーコード', 'メーカー名', 'メーカー品番']);
            foreach ($rows as $p) {
                fputcsv($out, [
                    $p->product_code,
                    $p->name,
                    $p->category,
                    $p->size,
                    $p->gender,
                    $p->stock_quantity,
                    $p->maker?->code,
                    $p->maker?->name,
                    $p->maker_product_code,
                ]);
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function create(Request $request): View
    {
        // Catalog products — institution/department linking is done via Contract Plan checkboxes
        $existingProducts = UniformProduct::select('product_code', 'name', 'category', 'gender', 'image_path')
            ->distinct()
            ->orderBy('product_code')
            ->get()
            ->groupBy('product_code')
            ->map(function($group) {
                $first = $group->first();
                return [
                    'product_code' => $first->product_code,
                    'name' => $first->name,
                    'category' => $first->category,
                    'gender' => $first->gender,
                    'image_path' => $first->image_path ? asset('storage/'.$first->image_path) : null,
                ];
            })
            ->values();
        
        [$topBottomSizes, $shoeSizes] = $this->standardSizeLists();
        
        return view('admin.uniform-products.create', [
            'existingProducts' => $existingProducts,
            'categories' => UniformCategory::activeOrdered(),
            'topBottomSizes' => $topBottomSizes,
            'shoeSizes' => $shoeSizes,
            'categorySizeSchemes' => UniformCategory::activeOrdered()->mapWithKeys(fn ($c) => [$c->code => $c->size_scheme]),
            'makers' => Maker::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        
        // Log raw request data for debugging
        \Log::info('=== UNIFORM PRODUCT STORE REQUEST ===', [
            'raw_all' => $request->all(),
            'raw_products' => $request->input('products'),
            'products_type' => gettype($request->input('products')),
            'products_is_array' => is_array($request->input('products')),
            'products_count' => is_array($request->input('products')) ? count($request->input('products')) : 0,
            'first_product' => is_array($request->input('products')) && isset($request->input('products')[0]) ? $request->input('products')[0] : null,
            'first_product_type' => is_array($request->input('products')) && isset($request->input('products')[0]) ? gettype($request->input('products')[0]) : null,
        ]);
        
        // Check if products is a simple array (wrong format)
        $productsInput = $request->input('products');
        if (is_array($productsInput) && !empty($productsInput) && !isset($productsInput[0]['name'])) {
            \Log::error('INVALID PRODUCTS FORMAT: Products is a simple array, not nested structure', [
                'received' => $productsInput,
                'expected_format' => 'products[0][name], products[0][category], etc.',
            ]);
            
            return redirect()
                ->back()
                ->withInput()
                ->with('error', __('pages.flash_f5bc3567d7'));
        }
        
        try {
            $data = $request->validate([
                'maker_id' => ['nullable', 'exists:makers,id'],
                'maker_product_code' => ['nullable', 'string', 'max:100'],
                'color_name' => ['nullable', 'string', 'max:100'],
                'products' => ['required', 'array', 'min:1'],
                'products.*.product_code' => ['nullable', 'string', 'max:50'],
                'products.*.name' => ['required', 'string', 'max:255'],
                'products.*.category' => ['required', 'string', 'max:50', Rule::in(UniformCategory::activeCodes())],
                'products.*.gender' => ['required', 'in:male,female,unisex'],
                'products.*.sizes' => ['nullable', 'array'],
                'products.*.sizes.*.size' => ['required_with:sizes', 'string', 'max:50'],
                'products.*.sizes.*.stock_quantity' => ['required_with:sizes', 'integer', 'min:0'],
                'products.*.image' => ['nullable', 'image', 'max:5120'],
            ], [
                'products.required' => '少なくとも1つの商品を追加してください。',
                'products.min' => '少なくとも1つの商品を追加してください。',
                'products.*.name.required' => '商品名を入力してください。',
                'products.*.category.required' => 'カテゴリーを選択してください。',
                'products.*.gender.required' => '性別を選択してください。',
                'products.*.sizes.*.size.required_with' => 'サイズを選択/入力してください。',
                'products.*.sizes.*.stock_quantity.required_with' => '在庫数を入力してください。',
            ]);
            
            \Log::info('Validation passed', [
                'validated_products_count' => count($data['products']),
                'first_product' => $data['products'][0] ?? null,
            ]);
            
            // After validation passes, get the validated products
            $products = $data['products'];
        } catch (\Illuminate\Validation\ValidationException $e) {
            \Log::error('Validation failed', [
                'errors' => $e->errors(),
                'products_input' => $request->input('products'),
            ]);
            // Convert validation errors to user-friendly messages - show only unique, relevant errors
            $errors = $e->errors();
            $errorMessages = [];
            
            // Group errors by product index for better readability
            $productErrors = [];
            $generalErrors = [];
            
            foreach ($errors as $field => $messages) {
                foreach ($messages as $message) {
                    // Extract product index if it's a product field
                    if (preg_match('/products\[(\d+)\]/', $field, $matches)) {
                        $productIndex = (int)$matches[1] + 1; // Convert to 1-based for display
                        if (!isset($productErrors[$productIndex])) {
                            $productErrors[$productIndex] = [];
                        }
                        $productErrors[$productIndex][] = $message;
                    } else {
                        $generalErrors[] = $message;
                    }
                }
            }
            
            // Build error message - show product-specific errors first, then general
            if (!empty($productErrors)) {
                foreach ($productErrors as $productIndex => $messages) {
                    $uniqueMessages = array_unique($messages);
                    $errorMessages[] = "商品 {$productIndex}: " . implode(', ', $uniqueMessages);
                }
            }
            
            if (!empty($generalErrors)) {
                $errorMessages = array_merge($errorMessages, array_unique($generalErrors));
            }
            
            return redirect()
                ->back()
                ->withInput()
                ->withErrors($errors)
                ->with('error', !empty($errorMessages) ? implode("\n", $errorMessages) : '入力エラーがあります。');
        }
        
        $createdCount = 0;
        $errors = [];
        $createdProductIds = [];
        
        \Log::info('Processing products', [
            'products_count' => count($data['products']),
        ]);
        
        foreach ($data['products'] as $index => $productData) {
            \Log::info("Processing product {$index}", [
                'product_data' => $productData,
                'has_sizes' => isset($productData['sizes']) && !empty($productData['sizes']),
                'sizes_count' => isset($productData['sizes']) ? count($productData['sizes']) : 0,
            ]);
            
            // Handle image upload for this product
            // Access image directly from request using array notation
            $imagePath = null;
            
            // Try multiple ways to access the file (Laravel handles nested arrays differently)
            $file = null;
            if ($request->hasFile("products.{$index}.image")) {
                $file = $request->file("products.{$index}.image");
            } elseif (isset($request->allFiles()["products"][$index]["image"])) {
                $file = $request->allFiles()["products"][$index]["image"];
            }
            
            \Log::info("Checking image for product {$index}", [
                'has_file_method' => $request->hasFile("products.{$index}.image"),
                'file_found' => $file !== null,
                'file_valid' => $file && $file->isValid(),
            ]);
            
            if ($file && $file->isValid()) {
                try {
                    $imagePath = $file->store('uniforms', 'public');
                    \Log::info("Image uploaded for product {$index}", ['image_path' => $imagePath]);
                } catch (\Exception $e) {
                    \Log::error("Failed to upload image for product {$index}", [
                        'error' => $e->getMessage(),
                    ]);
                }
            }
            
            // Create a product record for each size (if sizes exist)
            $sizes = $productData['sizes'] ?? [];
            $baseProductCode = MasterCodeGenerator::next('product');
            
            if (empty($sizes) || !is_array($sizes)) {
                // If no sizes, create a single product without size suffix
                $productCode = $baseProductCode;
                
                try {
                    $createData = [
                        'product_code' => $productCode,
                        'name' => $productData['name'],
                        'category' => $productData['category'],
                        'size' => '', // Empty size if no sizes provided
                        'gender' => $productData['gender'],
                        'stock_quantity' => 0, // Default to 0 if no sizes
                        'image_path' => $imagePath,
                        'maker_id' => $data['maker_id'] ?? null,
                        'maker_product_code' => $data['maker_product_code'] ?? null,
                        'color_name' => $data['color_name'] ?? null,
                    ];
                    
                    // Clear legacy institution/department binding (catalog products)
                    
                    $product = UniformProduct::create($createData);
                    $createdCount++;
                    $createdProductIds[] = $product->id;
                    \Log::info("Product created successfully: {$productCode}", ['product_id' => $product->id]);
                } catch (\Exception $e) {
                    $errorMsg = "商品コード {$productCode} の登録に失敗しました: " . $e->getMessage();
                    $errors[] = $errorMsg;
                    \Log::error("Failed to create product: {$productCode}", [
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ]);
                }
            } else {
                // Create a product record for each size
                $sizeCreatedCount = 0;
                foreach ($sizes as $sizeIndex => $sizeData) {
                    $productCode = $baseProductCode . '-' . $sizeData['size'];
                    
                    try {
                        $createData = [
                            'product_code' => $productCode,
                            'name' => $productData['name'],
                            'category' => $productData['category'],
                            'size' => (string)trim($sizeData['size']), // Ensure size is stored as string (letter format: XS, S, M, L, XL, XXL)
                            'gender' => $productData['gender'],
                            'stock_quantity' => (int)$sizeData['stock_quantity'],
                            'image_path' => $imagePath, // Same image for all sizes of same product
                            'maker_id' => $data['maker_id'] ?? null,
                            'maker_product_code' => $data['maker_product_code'] ?? null,
                            'color_name' => $data['color_name'] ?? null,
                        ];
                        
                        // Clear legacy institution/department binding (catalog products)
                        
                        $product = UniformProduct::create($createData);
                        $createdCount++;
                        $sizeCreatedCount++;
                        $createdProductIds[] = $product->id;
                        \Log::info("Product size created: {$productCode}", [
                            'product_id' => $product->id,
                            'size' => (string)trim($sizeData['size']), // Ensure size is stored as string (letter format: XS, S, M, L, XL, XXL)
                            'stock' => $sizeData['stock_quantity'],
                        ]);
                    } catch (\Exception $e) {
                        $errorMsg = "商品コード {$productCode} の登録に失敗しました: " . $e->getMessage();
                        $errors[] = $errorMsg;
                        \Log::error("Failed to create product size: {$productCode}", [
                            'error' => $e->getMessage(),
                            'trace' => $e->getTraceAsString(),
                        ]);
                    }
                }
                \Log::info("Product {$index} processing complete", [
                    'sizes_created' => $sizeCreatedCount,
                    'total_sizes' => count($sizes),
                ]);
            }
        }
        
        \Log::info('All products processed', [
            'total_created' => $createdCount,
            'created_ids' => $createdProductIds,
            'errors_count' => count($errors),
            'errors' => $errors,
        ]);
        
        // Build success/error message
        if ($createdCount > 0) {
            $message = "{$createdCount}件のユニフォーム商品を登録しました。";
            if (!empty($errors)) {
                $message .= "\n\nただし、以下の商品でエラーが発生しました:\n" . implode("\n", $errors);
            }
            
            \Log::info('Registration completed', [
                'created_count' => $createdCount,
                'errors_count' => count($errors),
            ]);
            
            return redirect()
                ->route('admin.uniform-products.index')
                ->with('status', $message)
                ->with('errors', !empty($errors) ? $errors : null);
        } else {
            // No products were created
            $errorMessage = !empty($errors) 
                ? 'ユニフォーム商品の登録に失敗しました。\n\n' . implode("\n", $errors)
                : 'ユニフォーム商品の登録に失敗しました。';
            
            \Log::error('No products created', ['errors' => $errors]);
            
            return redirect()
                ->back()
                ->withInput()
                ->withErrors(['products' => $errors])
                ->with('error', $errorMessage);
        }
    }

    public function edit(UniformProduct $uniformProduct): View
    {
        // Extract base product code (remove size suffix)
        $baseProductCode = $uniformProduct->product_code;
        $lastDashPos = strrpos($baseProductCode, '-');
        if ($lastDashPos !== false) {
            $baseProductCode = substr($baseProductCode, 0, $lastDashPos);
        }
        
        // Get all products with the same base product code (all sizes)
        $allSizes = UniformProduct::where('product_code', 'like', $baseProductCode . '-%')
            ->orWhere('product_code', $baseProductCode)
            ->orderBy('size')
            ->get();
        
        [$topBottomSizes, $shoeSizes] = $this->standardSizeLists();
        
        // Get existing sizes for this product (to exclude from dropdown when adding new sizes)
        $existingSizesForProduct = $allSizes->pluck('size')->filter()->map(function($size) {
            return (string)$size; // Ensure string format
        })->toArray();
        
        // Filter out existing sizes from available sizes (for adding new sizes only)
        $availableTopBottomSizes = array_values(array_diff($topBottomSizes, $existingSizesForProduct));
        $availableShoeSizes = array_values(array_diff($shoeSizes, $existingSizesForProduct));
        
        return view('admin.uniform-products.edit', [
            'product' => $uniformProduct,
            'allSizes' => $allSizes,
            'baseProductCode' => $baseProductCode,
            'categories' => UniformCategory::activeOrdered(),
            'topBottomSizes' => $topBottomSizes, // All sizes (for existing size selects)
            'shoeSizes' => $shoeSizes, // All sizes (for existing size selects)
            'availableTopBottomSizes' => $availableTopBottomSizes, // Filtered sizes (for new size selects)
            'availableShoeSizes' => $availableShoeSizes, // Filtered sizes (for new size selects)
            'existingSizes' => $existingSizesForProduct, // Existing sizes for JavaScript filtering
            'categorySizeSchemes' => UniformCategory::activeOrdered()->mapWithKeys(fn ($c) => [$c->code => $c->size_scheme]),
            'makers' => Maker::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, UniformProduct $uniformProduct): RedirectResponse
    {
        \Log::info('=== UNIFORM PRODUCT UPDATE REQUEST ===', [
            'product_id' => $uniformProduct->id,
            'all_input' => $request->all(),
        ]);
        
        // Extract base product code
        $baseProductCode = $uniformProduct->product_code;
        $lastDashPos = strrpos($baseProductCode, '-');
        if ($lastDashPos !== false) {
            $baseProductCode = substr($baseProductCode, 0, $lastDashPos);
        }
        
        // Product codes are system-owned — keep existing base, ignore request
        $newBaseProductCode = $baseProductCode;
        
        // Validate - support multiple sizes like create form
        $data = $request->validate([
            'product_code' => ['nullable', 'string', 'max:50'],
            'name' => ['required', 'string', 'max:255'],
            'category' => ['required', 'string', 'max:50', Rule::in(UniformCategory::activeCodes())],
            'gender' => ['required', 'in:male,female,unisex'],
            'maker_id' => ['nullable', 'exists:makers,id'],
            'maker_product_code' => ['nullable', 'string', 'max:100'],
            'color_name' => ['nullable', 'string', 'max:100'],
            'sizes' => ['nullable', 'array'],
            'sizes.*.size' => ['required_with:sizes', 'string', 'max:50'],
            'sizes.*.stock_quantity' => ['required_with:sizes', 'integer', 'min:0'],
            'image' => ['nullable', 'image', 'max:5120'],
        ]);
        
        $data['product_code'] = $baseProductCode;
        
        // Handle image upload
        $imagePath = $uniformProduct->image_path; // Keep existing by default
        if ($request->hasFile('image')) {
            $file = $request->file('image');
            if ($file && $file->isValid()) {
                $imagePath = $file->store('uniforms', 'public');
            }
        }
        
        $sizes = $data['sizes'] ?? [];
        $updatedCount = 0;
        $createdCount = 0;
        $errors = [];
        
        // Get all existing products with same base code (all sizes)
        $existingProducts = UniformProduct::where(function($q) use ($baseProductCode) {
            $q->where('product_code', 'like', $baseProductCode . '-%')
              ->orWhere('product_code', $baseProductCode);
        })->get();
        
        \Log::info('Existing products found', [
            'base_code' => $baseProductCode,
            'count' => $existingProducts->count(),
            'products' => $existingProducts->pluck('product_code', 'id')->toArray(),
        ]);
        
        $allProductIds = [];
        
        if (empty($sizes) || !is_array($sizes)) {
            // Single product update (backward compatibility)
            $productCode = $baseProductCode;
            
            $updateData = [
                'product_code' => $productCode,
                'name' => $data['name'],
                'category' => $data['category'],
                'gender' => $data['gender'],
                'image_path' => $imagePath,
                'maker_id' => $data['maker_id'] ?? null,
                'maker_product_code' => $data['maker_product_code'] ?? null,
                'color_name' => $data['color_name'] ?? null,
            ];
            
            // Clear legacy institution/department binding (catalog products)
            
            $uniformProduct->update($updateData);
            $updatedCount = 1;
            $allProductIds = [$uniformProduct->id];
        } else {
            // Update multiple sizes - match by size value, not by array key
            foreach ($sizes as $sizeIndex => $sizeData) {
                if (empty($sizeData['size'])) {
                    continue; // Skip empty sizes
                }
                
                $size = (string)trim($sizeData['size']); // Ensure size is stored as string (letter format: XS, S, M, L, XL, XXL)
                $productCode = $newBaseProductCode . '-' . $size;
                
                // Find existing product by matching size value
                $existingProduct = $existingProducts->first(function($product) use ($size, $baseProductCode) {
                    // Extract size from product code
                    $productSize = $product->size;
                    if (empty($productSize)) {
                        // Try to extract from product_code
                        $lastDashPos = strrpos($product->product_code, '-');
                        if ($lastDashPos !== false) {
                            $productSize = substr($product->product_code, $lastDashPos + 1);
                        }
                    }
                    return $productSize === $size;
                });
                
                if ($existingProduct) {
                    // Update existing product
                    try {
                        // Check if product code is changing and if new code already exists
                        if ($existingProduct->product_code !== $productCode) {
                            $codeExists = UniformProduct::where('product_code', $productCode)
                                ->where('id', '!=', $existingProduct->id)
                                ->exists();
                            if ($codeExists) {
                                $errors[] = "商品コード {$productCode} は既に存在します。";
                                \Log::warning("Product code already exists: {$productCode}");
                                continue;
                            }
                        }
                        
                        $updateData = [
                            'product_code' => $productCode,
                            'name' => $data['name'],
                            'category' => $data['category'],
                            'size' => (string)$size, // Ensure size is stored as string (letter format: XS, S, M, L, XL, XXL)
                            'gender' => $data['gender'],
                            'stock_quantity' => (int)($sizeData['stock_quantity'] ?? 0),
                            'image_path' => $imagePath,
                            'maker_id' => $data['maker_id'] ?? null,
                            'maker_product_code' => $data['maker_product_code'] ?? null,
                            'color_name' => $data['color_name'] ?? null,
                        ];
                        
                        // Clear legacy institution/department binding (catalog products)
                        
                        $existingProduct->update($updateData);
                        $updatedCount++;
                        $allProductIds[] = $existingProduct->id;
                        \Log::info("Updated existing product", [
                            'product_id' => $existingProduct->id,
                            'old_code' => $existingProduct->getOriginal('product_code'),
                            'new_code' => $productCode,
                            'size' => $size,
                        ]);
                    } catch (\Exception $e) {
                        $errors[] = "サイズ {$size} の更新に失敗しました: " . $e->getMessage();
                        \Log::error("Failed to update size: {$size}", [
                            'error' => $e->getMessage(),
                            'product_id' => $existingProduct->id,
                        ]);
                    }
                } else {
                    // Create new size variant only if it doesn't exist
                    $codeExists = UniformProduct::where('product_code', $productCode)->exists();
                    if ($codeExists) {
                        $errors[] = "商品コード {$productCode} は既に存在します。";
                        \Log::warning("Product code already exists (create attempt): {$productCode}");
                        continue;
                    }
                    
                    try {
                        $createData = [
                            'product_code' => $productCode,
                            'name' => $data['name'],
                            'category' => $data['category'],
                            'size' => (string)$size, // Ensure size is stored as string (letter format: XS, S, M, L, XL, XXL)
                            'gender' => $data['gender'],
                            'stock_quantity' => (int)($sizeData['stock_quantity'] ?? 0),
                            'image_path' => $imagePath,
                            'maker_id' => $data['maker_id'] ?? null,
                            'maker_product_code' => $data['maker_product_code'] ?? null,
                            'color_name' => $data['color_name'] ?? null,
                        ];
                        
                        // Clear legacy institution/department binding (catalog products)
                        
                        $newProduct = UniformProduct::create($createData);
                        $createdCount++;
                        $allProductIds[] = $newProduct->id;
                        \Log::info("Created new size variant", [
                            'product_id' => $newProduct->id,
                            'product_code' => $productCode,
                            'size' => $size,
                        ]);
                    } catch (\Exception $e) {
                        $errors[] = "サイズ {$size} の作成に失敗しました: " . $e->getMessage();
                        \Log::error("Failed to create size: {$size}", ['error' => $e->getMessage()]);
                    }
                }
            }
            
            // Delete sizes that were removed (only if we have existing products)
            if ($existingProducts->isNotEmpty()) {
                $submittedSizes = collect($sizes)->pluck('size')->filter()->map(function($s) {
                    return trim($s);
                })->toArray();
                
                $existingProducts->each(function($product) use ($submittedSizes, $baseProductCode, $newBaseProductCode) {
                    // Only delete if it belongs to the same base product
                    $productBaseCode = $product->product_code;
                    $lastDashPos = strrpos($productBaseCode, '-');
                    if ($lastDashPos !== false) {
                        $productBaseCode = substr($productBaseCode, 0, $lastDashPos);
                    }
                    
                    // Only delete if it's from the old base code or new base code
                    if ($productBaseCode === $baseProductCode || $productBaseCode === $newBaseProductCode) {
                        $productSize = $product->size;
                        if (empty($productSize)) {
                            // Try to extract from product_code
                            $lastDashPos = strrpos($product->product_code, '-');
                            if ($lastDashPos !== false) {
                                $productSize = substr($product->product_code, $lastDashPos + 1);
                            }
                        }
                        
                        if (!empty($productSize) && !in_array($productSize, $submittedSizes)) {
                            \Log::info("Deleting removed size", [
                                'product_id' => $product->id,
                                'product_code' => $product->product_code,
                                'size' => $productSize,
                            ]);
                            $product->delete();
                        }
                    }
                });
            }
        }
        
        // Note: Product linking is via Contract Plan checkboxes, not institution/department
        if (!empty($allProductIds)) {
            \Log::info('Product update completed', [
                'product_ids' => $allProductIds,
            ]);
        }
        
        $totalProcessed = $updatedCount + $createdCount;
        $message = $totalProcessed > 0
            ? "{$totalProcessed}件のユニフォーム商品を更新しました。" . ($createdCount > 0 ? "（新規: {$createdCount}件）" : "")
            : 'ユニフォーム商品の更新に失敗しました。';
        
        if (!empty($errors)) {
            $message .= "\n\nただし、以下のエラーが発生しました:\n" . implode("\n", $errors);
        }
        
        \Log::info('Update completed', [
            'updated' => $updatedCount,
            'created' => $createdCount,
            'errors' => count($errors),
        ]);
        
        return redirect()
            ->route('admin.uniform-products.index')
            ->with('status', $message);
    }

    public function destroy(UniformProduct $uniformProduct): RedirectResponse
    {
        $uniformProduct->delete();

        return redirect()
            ->route('admin.uniform-products.index')
            ->with('status', __('pages.flash_9544209312'));
    }

    /**
     * @return array{0: list<string>, 1: list<string>}
     */
    private function standardSizeLists(): array
    {
        $standardTopBottomSizes = ['XS', 'S', 'M', 'L', 'XL', 'XXL'];
        $standardShoeSizes = ['23.0', '23.5', '24.0', '24.5', '25.0', '25.5', '26.0', '26.5', '27.0', '27.5', '28.0', '28.5', '29.0', '29.5', '30.0'];

        $letterCodes = UniformCategory::codesWithScheme(UniformCategory::SIZE_LETTER);
        $shoeCodes = UniformCategory::codesWithScheme(UniformCategory::SIZE_SHOE);

        $existingTopBottomSizes = $letterCodes
            ? UniformProduct::whereIn('category', $letterCodes)->select('size')->distinct()->pluck('size')->filter()->values()->all()
            : [];
        $existingShoeSizes = $shoeCodes
            ? UniformProduct::whereIn('category', $shoeCodes)->select('size')->distinct()->pluck('size')->filter()->values()->all()
            : [];

        $topBottomSizes = array_unique(array_merge($standardTopBottomSizes, $existingTopBottomSizes));
        usort($topBottomSizes, function ($a, $b) {
            $order = ['XS' => 1, 'S' => 2, 'M' => 3, 'L' => 4, 'XL' => 5, 'XXL' => 6];
            return ($order[$a] ?? 99) <=> ($order[$b] ?? 99);
        });

        $shoeSizes = array_unique(array_merge($standardShoeSizes, $existingShoeSizes));
        usort($shoeSizes, fn ($a, $b) => (float) $a <=> (float) $b);

        return [array_values($topBottomSizes), array_values($shoeSizes)];
    }
}
