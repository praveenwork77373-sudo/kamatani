<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProductUnitPrice;
use App\Models\UniformProduct;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use App\Support\Pagination;

class ProductUnitPriceController extends Controller
{
    public function index(Request $request): View
    {
        $query = ProductUnitPrice::query()->orderBy('product_code')->orderBy('size_name');

        if ($search = $request->string('q')->toString()) {
            $query->where(function ($q) use ($search) {
                $q->where('product_code', 'like', "%{$search}%")
                    ->orWhere('size_name', 'like', "%{$search}%");
            });
        }

        return view('admin.product-unit-prices.index', [
            'prices' => $query->paginate(Pagination::perPage($request))->withQueryString(),
            'searchQuery' => $search ?? '',
        ]);
    }

    public function create(): View
    {
        $productCodes = UniformProduct::query()
            ->select('product_code')
            ->distinct()
            ->orderBy('product_code')
            ->pluck('product_code');

        return view('admin.product-unit-prices.create', compact('productCodes'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'product_code' => ['required', 'string', 'max:50'],
            'size_name' => ['nullable', 'string', 'max:50'],
            'purchase_unit_price' => ['required', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
        ]);

        $data['size_name'] = $data['size_name'] !== null && $data['size_name'] !== ''
            ? $data['size_name']
            : null;

        $exists = ProductUnitPrice::query()
            ->where('product_code', $data['product_code'])
            ->where(function ($q) use ($data) {
                if ($data['size_name'] === null) {
                    $q->whereNull('size_name');
                } else {
                    $q->where('size_name', $data['size_name']);
                }
            })
            ->exists();

        if ($exists) {
            return back()->withInput()->withErrors([
                'product_code' => __('pages.code_already_taken'),
            ]);
        }

        ProductUnitPrice::create($data);

        return redirect()->route('admin.product-unit-prices.index')
            ->with('status', __('common.saved'));
    }

    public function edit(ProductUnitPrice $productUnitPrice): View
    {
        $productCodes = UniformProduct::query()
            ->select('product_code')
            ->distinct()
            ->orderBy('product_code')
            ->pluck('product_code');

        return view('admin.product-unit-prices.edit', [
            'price' => $productUnitPrice,
            'productCodes' => $productCodes,
        ]);
    }

    public function update(Request $request, ProductUnitPrice $productUnitPrice): RedirectResponse
    {
        $data = $request->validate([
            'product_code' => ['required', 'string', 'max:50'],
            'size_name' => ['nullable', 'string', 'max:50'],
            'purchase_unit_price' => ['required', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
        ]);

        $data['size_name'] = $data['size_name'] !== null && $data['size_name'] !== ''
            ? $data['size_name']
            : null;

        $exists = ProductUnitPrice::query()
            ->where('product_code', $data['product_code'])
            ->where('id', '!=', $productUnitPrice->id)
            ->where(function ($q) use ($data) {
                if ($data['size_name'] === null) {
                    $q->whereNull('size_name');
                } else {
                    $q->where('size_name', $data['size_name']);
                }
            })
            ->exists();

        if ($exists) {
            return back()->withInput()->withErrors([
                'product_code' => __('pages.code_already_taken'),
            ]);
        }

        $productUnitPrice->update($data);

        return redirect()->route('admin.product-unit-prices.index')
            ->with('status', __('common.saved'));
    }

    public function destroy(ProductUnitPrice $productUnitPrice): RedirectResponse
    {
        $productUnitPrice->delete();

        return redirect()->route('admin.product-unit-prices.index')
            ->with('status', __('common.deleted'));
    }
}
