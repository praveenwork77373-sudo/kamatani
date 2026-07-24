<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\UniformProduct;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Facades\Schema;

class UniformProductController extends Controller
{
    public function index(Request $request): View
    {
        $query = UniformProduct::query();

        // Search filter
        if ($search = $request->string('search')->toString()) {
            $query->where(function($q) use ($search) {
                $q->where('product_code', 'like', "%{$search}%")
                  ->orWhere('name', 'like', "%{$search}%");
            });
        }

        // Category filter
        if ($category = $request->string('category')->toString()) {
            $query->where('category', $category);
        }

        // Note: Medical institution and department filters removed
        // Uniform recommendations have been removed, so products are no longer
        // filtered by institution/department. All products are shown.

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
                if ($first->category === 'shoe') {
                    return (float)$item['size'];
                }
                $order = ['S' => 1, 'M' => 2, 'L' => 3, 'XL' => 4, 'XXL' => 5];
                return $order[$item['size']] ?? 99;
            })->values();

            return [
                'base_product_code' => $baseCode, // Use the extracted base code
                'name' => $first->name,
                'category' => $first->category,
                'gender' => $first->gender,
                'image_path' => $first->image_path ? asset('storage/'.$first->image_path) : null,
                'sizes' => $sizes,
                'total_stock' => $group->sum('stock_quantity'),
                'has_stock' => $group->sum('stock_quantity') > 0,
            ];
        })->values();

        // Get filter options
        $medicalInstitutions = \App\Models\MedicalInstitution::orderBy('name')->get();
        $selectedInstitutionId = $request->input('medical_institution_id');
        $departments = collect();
        if ($selectedInstitutionId) {
            $departments = \App\Models\Department::where('medical_institution_id', $selectedInstitutionId)
                ->orderBy('name')
                ->get();
        }

        // Standard sizes that should always be available
        $standardTopBottomSizes = ['XS', 'S', 'M', 'L', 'XL', 'XXL'];
        $standardShoeSizes = ['23.0', '23.5', '24.0', '24.5', '25.0', '25.5', '26.0', '26.5', '27.0', '27.5', '28.0', '28.5', '29.0', '29.5', '30.0'];
        
        // Get sizes from existing products in database
        $existingTopBottomSizes = UniformProduct::whereIn('category', ['top', 'bottom'])
            ->select('size')
            ->distinct()
            ->pluck('size')
            ->filter()
            ->values()
            ->all();
        
        $existingShoeSizes = UniformProduct::where('category', 'shoe')
            ->select('size')
            ->distinct()
            ->pluck('size')
            ->filter()
            ->values()
            ->all();
        
        // Merge standard sizes with existing sizes, ensuring all standard sizes are included
        $topBottomSizes = array_unique(array_merge($standardTopBottomSizes, $existingTopBottomSizes));
        
        // Sort top/bottom sizes in correct order
        usort($topBottomSizes, function($a, $b) {
            $order = ['XS' => 1, 'S' => 2, 'M' => 3, 'L' => 4, 'XL' => 5, 'XXL' => 6];
            $orderA = $order[$a] ?? 99;
            $orderB = $order[$b] ?? 99;
            return $orderA <=> $orderB;
        });
        
        // Merge shoe sizes and sort numerically
        $shoeSizes = array_unique(array_merge($standardShoeSizes, $existingShoeSizes));
        usort($shoeSizes, function($a, $b) {
            return (float)$a <=> (float)$b;
        });

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

        return view('admin.uniform-products.index', [
            'products' => $groupedProducts,
            'medicalInstitutions' => $medicalInstitutions,
            'departments' => $departments,
            'selectedInstitutionId' => $selectedInstitutionId,
            'selectedDepartmentId' => $request->input('department_id'),
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

    public function create(Request $request): View
    {
        $medicalInstitutions = \App\Models\MedicalInstitution::orderBy('name')->get();
        $selectedInstitutionId = $request->input('medical_institution_id');
        $selectedDepartmentId = $request->input('department_id');
        
        $departments = collect();
        if ($selectedInstitutionId) {
            $departments = \App\Models\Department::where('medical_institution_id', $selectedInstitutionId)
                ->orderBy('name')
                ->get();
        }
        
        // Get existing products grouped by product_code for reference
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
        
        // Standard sizes that should always be available
        $standardTopBottomSizes = ['XS', 'S', 'M', 'L', 'XL', 'XXL'];
        $standardShoeSizes = ['23.0', '23.5', '24.0', '24.5', '25.0', '25.5', '26.0', '26.5', '27.0', '27.5', '28.0', '28.5', '29.0', '29.5', '30.0'];
        
        // Get sizes from existing products in database
        $existingTopBottomSizes = UniformProduct::whereIn('category', ['top', 'bottom'])
            ->select('size')
            ->distinct()
            ->pluck('size')
            ->filter()
            ->values()
            ->all();
        
        $existingShoeSizes = UniformProduct::where('category', 'shoe')
            ->select('size')
            ->distinct()
            ->pluck('size')
            ->filter()
            ->values()
            ->all();
        
        // Merge standard sizes with existing sizes, ensuring all standard sizes are included
        $topBottomSizes = array_unique(array_merge($standardTopBottomSizes, $existingTopBottomSizes));
        
        // Sort top/bottom sizes in correct order
        usort($topBottomSizes, function($a, $b) {
            $order = ['XS' => 1, 'S' => 2, 'M' => 3, 'L' => 4, 'XL' => 5, 'XXL' => 6];
            $orderA = $order[$a] ?? 99;
            $orderB = $order[$b] ?? 99;
            return $orderA <=> $orderB;
        });
        
        // Merge shoe sizes and sort numerically
        $shoeSizes = array_unique(array_merge($standardShoeSizes, $existingShoeSizes));
        usort($shoeSizes, function($a, $b) {
            return (float)$a <=> (float)$b;
        });
        
        return view('admin.uniform-products.create', [
            'medicalInstitutions' => $medicalInstitutions,
            'departments' => $departments,
            'selectedInstitutionId' => $selectedInstitutionId,
            'selectedDepartmentId' => $selectedDepartmentId,
            'existingProducts' => $existingProducts,
            'topBottomSizes' => $topBottomSizes,
            'shoeSizes' => $shoeSizes,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        // Check if columns exist, create them if they don't
        $hasMedicalInstitutionColumn = Schema::hasColumn('uniform_products', 'medical_institution_id');
        $hasDepartmentColumn = Schema::hasColumn('uniform_products', 'department_id');
        
        if (!$hasMedicalInstitutionColumn || !$hasDepartmentColumn) {
            try {
                Schema::table('uniform_products', function (\Illuminate\Database\Schema\Blueprint $table) use ($hasMedicalInstitutionColumn, $hasDepartmentColumn) {
                    if (!$hasMedicalInstitutionColumn) {
                        $table->foreignId('medical_institution_id')->nullable()->after('id')->constrained('medical_institutions')->nullOnDelete();
                    }
                    if (!$hasDepartmentColumn) {
                        $table->foreignId('department_id')->nullable()->after('medical_institution_id')->constrained('departments')->nullOnDelete();
                    }
                });
                $hasMedicalInstitutionColumn = true;
                $hasDepartmentColumn = true;
            } catch (\Exception $e) {
                \Log::error('Failed to create columns in store method', ['error' => $e->getMessage()]);
            }
        }
        
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
        if (is_array($productsInput) && !empty($productsInput) && !isset($productsInput[0]['product_code'])) {
            \Log::error('INVALID PRODUCTS FORMAT: Products is a simple array, not nested structure', [
                'received' => $productsInput,
                'expected_format' => 'products[0][product_code], products[0][name], etc.',
            ]);
            
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'フォームデータの形式が正しくありません。ページを再読み込みして再度お試しください。');
        }
        
        try {
            $data = $request->validate([
                'medical_institution_id' => ['required', 'exists:medical_institutions,id'],
                'department_id' => ['required', 'exists:departments,id'],
                'products' => ['required', 'array', 'min:1'],
                'products.*.product_code' => ['required', 'string', 'max:50'],
                'products.*.name' => ['required', 'string', 'max:255'],
                'products.*.category' => ['required', 'in:top,bottom,shoe,other'],
                'products.*.gender' => ['required', 'in:male,female,unisex'],
                'products.*.sizes' => ['nullable', 'array'],
                'products.*.sizes.*.size' => ['required_with:sizes', 'string', 'max:50'],
                'products.*.sizes.*.stock_quantity' => ['required_with:sizes', 'integer', 'min:0'],
                'products.*.image' => ['nullable', 'image', 'max:5120'],
            ], [
                'medical_institution_id.required' => '医療機関を選択してください。',
                'department_id.required' => '部署を選択してください。',
                'products.required' => '少なくとも1つの商品を追加してください。',
                'products.min' => '少なくとも1つの商品を追加してください。',
                'products.*.product_code.required' => '商品コードを入力してください。',
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
            
            if (empty($sizes) || !is_array($sizes)) {
                // If no sizes, create a single product without size suffix
                $productCode = $productData['product_code'];
                
                // Check if product_code already exists
                if (UniformProduct::where('product_code', $productCode)->exists()) {
                    $errorMsg = "商品コード {$productCode} は既に存在します。";
                    $errors[] = $errorMsg;
                    \Log::warning("Product code exists: {$productCode}");
                    continue;
                }
                
                try {
                    $createData = [
                        'product_code' => $productCode,
                        'name' => $productData['name'],
                        'category' => $productData['category'],
                        'size' => '', // Empty size if no sizes provided
                        'gender' => $productData['gender'],
                        'stock_quantity' => 0, // Default to 0 if no sizes
                        'image_path' => $imagePath,
                    ];
                    
                    // Only include medical_institution_id and department_id if columns exist
                    if ($hasMedicalInstitutionColumn) {
                        $createData['medical_institution_id'] = $data['medical_institution_id'];
                    }
                    if ($hasDepartmentColumn) {
                        $createData['department_id'] = $data['department_id'];
                    }
                    
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
                    $productCode = $productData['product_code'] . '-' . $sizeData['size'];
                    
                    // Check if product_code already exists
                    if (UniformProduct::where('product_code', $productCode)->exists()) {
                        $errorMsg = "商品コード {$productCode} は既に存在します。";
                        $errors[] = $errorMsg;
                        \Log::warning("Product code exists: {$productCode}");
                        continue;
                    }
                    
                    try {
                        $createData = [
                            'product_code' => $productCode,
                            'name' => $productData['name'],
                            'category' => $productData['category'],
                            'size' => (string)trim($sizeData['size']), // Ensure size is stored as string (letter format: XS, S, M, L, XL, XXL)
                            'gender' => $productData['gender'],
                            'stock_quantity' => (int)$sizeData['stock_quantity'],
                            'image_path' => $imagePath, // Same image for all sizes of same product
                        ];
                        
                        // Only include medical_institution_id and department_id if columns exist
                        if ($hasMedicalInstitutionColumn) {
                            $createData['medical_institution_id'] = $data['medical_institution_id'];
                        }
                        if ($hasDepartmentColumn) {
                            $createData['department_id'] = $data['department_id'];
                        }
                        
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
        
        // Create uniform recommendations for the selected institution and department
        if ($createdCount > 0 && !empty($createdProductIds)) {
            \Log::info('Creating uniform recommendations', [
                'product_ids' => $createdProductIds,
                'institution_id' => $data['medical_institution_id'],
                'department_id' => $data['department_id'],
            ]);
            
        }
        
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
        
        // Get current institution and department from product's database record
        $hasMedicalInstitutionColumn = Schema::hasColumn('uniform_products', 'medical_institution_id');
        $hasDepartmentColumn = Schema::hasColumn('uniform_products', 'department_id');
        
        $selectedInstitutionId = null;
        $selectedDepartmentId = null;
        
        // Get values from database if columns exist
        if ($hasMedicalInstitutionColumn && $uniformProduct->medical_institution_id) {
            $selectedInstitutionId = $uniformProduct->medical_institution_id;
        }
        if ($hasDepartmentColumn && $uniformProduct->department_id) {
            $selectedDepartmentId = $uniformProduct->department_id;
        }
        
        // Get medical institutions and departments
        $medicalInstitutions = \App\Models\MedicalInstitution::orderBy('name')->get();
        $departments = collect();
        
        // Load departments based on selected institution (or from product if available)
        if ($selectedInstitutionId) {
            $departments = \App\Models\Department::where('medical_institution_id', $selectedInstitutionId)
                ->orderBy('name')
                ->get();
        } elseif ($hasMedicalInstitutionColumn && $uniformProduct->medical_institution_id) {
            // Fallback: try to get departments from product's institution
            $departments = \App\Models\Department::where('medical_institution_id', $uniformProduct->medical_institution_id)
                ->orderBy('name')
                ->get();
        }
        
        // Standard sizes that should always be available
        $standardTopBottomSizes = ['XS', 'S', 'M', 'L', 'XL', 'XXL'];
        $standardShoeSizes = ['23.0', '23.5', '24.0', '24.5', '25.0', '25.5', '26.0', '26.5', '27.0', '27.5', '28.0', '28.5', '29.0', '29.5', '30.0'];
        
        // Get sizes from existing products in database
        $existingTopBottomSizes = UniformProduct::whereIn('category', ['top', 'bottom'])
            ->select('size')
            ->distinct()
            ->pluck('size')
            ->filter()
            ->values()
            ->all();
        
        $existingShoeSizes = UniformProduct::where('category', 'shoe')
            ->select('size')
            ->distinct()
            ->pluck('size')
            ->filter()
            ->values()
            ->all();
        
        // Merge standard sizes with existing sizes, ensuring all standard sizes are included
        $topBottomSizes = array_unique(array_merge($standardTopBottomSizes, $existingTopBottomSizes));
        
        // Sort top/bottom sizes in correct order
        usort($topBottomSizes, function($a, $b) {
            $order = ['XS' => 1, 'S' => 2, 'M' => 3, 'L' => 4, 'XL' => 5, 'XXL' => 6];
            $orderA = $order[$a] ?? 99;
            $orderB = $order[$b] ?? 99;
            return $orderA <=> $orderB;
        });
        
        // Merge shoe sizes and sort numerically
        $shoeSizes = array_unique(array_merge($standardShoeSizes, $existingShoeSizes));
        usort($shoeSizes, function($a, $b) {
            return (float)$a <=> (float)$b;
        });
        
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
            'topBottomSizes' => $topBottomSizes, // All sizes (for existing size selects)
            'shoeSizes' => $shoeSizes, // All sizes (for existing size selects)
            'availableTopBottomSizes' => $availableTopBottomSizes, // Filtered sizes (for new size selects)
            'availableShoeSizes' => $availableShoeSizes, // Filtered sizes (for new size selects)
            'existingSizes' => $existingSizesForProduct, // Existing sizes for JavaScript filtering
            'medicalInstitutions' => $medicalInstitutions,
            'departments' => $departments,
            'selectedInstitutionId' => $selectedInstitutionId,
            'selectedDepartmentId' => $selectedDepartmentId,
        ]);
    }

    public function update(Request $request, UniformProduct $uniformProduct): RedirectResponse
    {
        \Log::info('=== UNIFORM PRODUCT UPDATE REQUEST ===', [
            'product_id' => $uniformProduct->id,
            'all_input' => $request->all(),
        ]);
        
        // Check if columns exist, create them if they don't
        $hasMedicalInstitutionColumn = Schema::hasColumn('uniform_products', 'medical_institution_id');
        $hasDepartmentColumn = Schema::hasColumn('uniform_products', 'department_id');
        
        if (!$hasMedicalInstitutionColumn || !$hasDepartmentColumn) {
            try {
                Schema::table('uniform_products', function (\Illuminate\Database\Schema\Blueprint $table) use ($hasMedicalInstitutionColumn, $hasDepartmentColumn) {
                    if (!$hasMedicalInstitutionColumn) {
                        $table->foreignId('medical_institution_id')->nullable()->after('id')->constrained('medical_institutions')->nullOnDelete();
                    }
                    if (!$hasDepartmentColumn) {
                        $table->foreignId('department_id')->nullable()->after('medical_institution_id')->constrained('departments')->nullOnDelete();
                    }
                });
                $hasMedicalInstitutionColumn = true;
                $hasDepartmentColumn = true;
            } catch (\Exception $e) {
                \Log::error('Failed to create columns in update method', ['error' => $e->getMessage()]);
            }
        }
        
        // Extract base product code
        $baseProductCode = $uniformProduct->product_code;
        $lastDashPos = strrpos($baseProductCode, '-');
        if ($lastDashPos !== false) {
            $baseProductCode = substr($baseProductCode, 0, $lastDashPos);
        }
        
        // Get new base product code from request
        $newBaseProductCode = $request->input('product_code', $baseProductCode);
        
        // Validate - support multiple sizes like create form
        $data = $request->validate([
            'medical_institution_id' => ['required', 'exists:medical_institutions,id'],
            'department_id' => ['required', 'exists:departments,id'],
            'product_code' => ['required', 'string', 'max:50'],
            'name' => ['required', 'string', 'max:255'],
            'category' => ['required', 'in:top,bottom,shoe,other'],
            'gender' => ['required', 'in:male,female,unisex'],
            'sizes' => ['nullable', 'array'],
            'sizes.*.size' => ['required_with:sizes', 'string', 'max:50'],
            'sizes.*.stock_quantity' => ['required_with:sizes', 'integer', 'min:0'],
            'image' => ['nullable', 'image', 'max:5120'],
        ], [
            'medical_institution_id.required' => '医療機関を選択してください。',
            'department_id.required' => '部署を選択してください。',
        ]);
        
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
            $productCode = $data['product_code'];
            if ($uniformProduct->product_code !== $productCode && UniformProduct::where('product_code', $productCode)->where('id', '!=', $uniformProduct->id)->exists()) {
                return redirect()
                    ->back()
                    ->withInput()
                    ->with('error', "商品コード {$productCode} は既に存在します。");
            }
            
            $updateData = [
                'product_code' => $productCode,
                'name' => $data['name'],
                'category' => $data['category'],
                'gender' => $data['gender'],
                'image_path' => $imagePath,
            ];
            
            // Only include medical_institution_id and department_id if columns exist
            if ($hasMedicalInstitutionColumn) {
                $updateData['medical_institution_id'] = $data['medical_institution_id'];
            }
            if ($hasDepartmentColumn) {
                $updateData['department_id'] = $data['department_id'];
            }
            
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
                        ];
                        
                        // Only include medical_institution_id and department_id if columns exist
                        if ($hasMedicalInstitutionColumn) {
                            $updateData['medical_institution_id'] = $data['medical_institution_id'];
                        }
                        if ($hasDepartmentColumn) {
                            $updateData['department_id'] = $data['department_id'];
                        }
                        
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
                        ];
                        
                        // Only include medical_institution_id and department_id if columns exist
                        if ($hasMedicalInstitutionColumn) {
                            $createData['medical_institution_id'] = $data['medical_institution_id'];
                        }
                        if ($hasDepartmentColumn) {
                            $createData['department_id'] = $data['department_id'];
                        }
                        
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
        
        // Note: Uniform recommendations have been removed
        // Product relationships are now handled directly through medical_institution_id and department_id
        if (!empty($allProductIds)) {
            \Log::info('Product update completed', [
                'institution_id' => $data['medical_institution_id'],
                'department_id' => $data['department_id'],
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
            ->with('status', 'ユニフォーム商品を削除しました。');
    }
}



