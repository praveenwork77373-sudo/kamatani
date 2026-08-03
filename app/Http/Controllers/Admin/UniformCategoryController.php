<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\UniformCategory;
use App\Models\UniformProduct;
use App\Support\MasterCodeGenerator;
use App\Support\Pagination;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UniformCategoryController extends Controller
{
    public function index(Request $request): View
    {
        $categories = UniformCategory::query()
            ->ordered()
            ->paginate(Pagination::perPage($request))
            ->withQueryString();

        return view('admin.uniform-categories.index', compact('categories'));
    }

    public function create(): View
    {
        return view('admin.uniform-categories.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:50'],
            'size_scheme' => ['required', Rule::in([UniformCategory::SIZE_LETTER, UniformCategory::SIZE_SHOE, UniformCategory::SIZE_NONE])],
            'employee_size_field' => ['nullable', Rule::in(['top_size', 'bottom_size', 'shoe_size'])],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $data['code'] = MasterCodeGenerator::next('uniform_category');
        $data['sort_order'] = (int) ($data['sort_order'] ?? 100);
        $data['is_active'] = $request->boolean('is_active', true);
        $data['employee_size_field'] = $data['employee_size_field'] ?: null;

        UniformCategory::create($data);
        UniformCategory::forgetCache();

        return redirect()
            ->route('admin.uniform-categories.index')
            ->with('status', 'カテゴリを登録しました。');
    }

    public function edit(UniformCategory $uniformCategory): View
    {
        return view('admin.uniform-categories.edit', [
            'category' => $uniformCategory,
        ]);
    }

    public function update(Request $request, UniformCategory $uniformCategory): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:50'],
            'size_scheme' => ['required', Rule::in([UniformCategory::SIZE_LETTER, UniformCategory::SIZE_SHOE, UniformCategory::SIZE_NONE])],
            'employee_size_field' => ['nullable', Rule::in(['top_size', 'bottom_size', 'shoe_size'])],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        unset($data['code']);
        $data['sort_order'] = (int) ($data['sort_order'] ?? $uniformCategory->sort_order);
        $data['is_active'] = $request->boolean('is_active');
        $data['employee_size_field'] = $data['employee_size_field'] ?: null;

        $uniformCategory->update($data);
        UniformCategory::forgetCache();

        return redirect()
            ->route('admin.uniform-categories.index')
            ->with('status', 'カテゴリを更新しました。');
    }

    public function destroy(UniformCategory $uniformCategory): RedirectResponse
    {
        $inUse = UniformProduct::where('category', $uniformCategory->code)->exists();
        if ($inUse) {
            $uniformCategory->update(['is_active' => false]);
            UniformCategory::forgetCache();

            return redirect()
                ->route('admin.uniform-categories.index')
                ->with('status', '商品で使用中のため無効化しました（削除していません）。');
        }

        $uniformCategory->delete();
        UniformCategory::forgetCache();

        return redirect()
            ->route('admin.uniform-categories.index')
            ->with('status', 'カテゴリを削除しました。');
    }
}
