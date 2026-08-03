<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Maker;
use App\Support\MasterCodeGenerator;
use App\Support\Pagination;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class MakerController extends Controller
{
    public function index(Request $request): View
    {
        $makers = Maker::orderBy('code')->paginate(Pagination::perPage($request))->withQueryString();

        return view('admin.makers.index', compact('makers'));
    }

    public function create(): View
    {
        return view('admin.makers.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:50'],
            'tel' => ['nullable', 'string', 'max:50'],
            'fax' => ['nullable', 'string', 'max:50'],
            'notes' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]);
        $data['code'] = MasterCodeGenerator::next('maker');
        $data['is_active'] = $request->boolean('is_active', true);

        Maker::create($data);

        return redirect()->route('admin.makers.index')->with('status', 'メーカーを登録しました。');
    }

    public function edit(Maker $maker): View
    {
        return view('admin.makers.edit', compact('maker'));
    }

    public function update(Request $request, Maker $maker): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:50', Rule::unique('makers', 'code')->ignore($maker->id)],
            'tel' => ['nullable', 'string', 'max:50'],
            'fax' => ['nullable', 'string', 'max:50'],
            'notes' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]);
        unset($data['code']);
        $data['is_active'] = $request->boolean('is_active');

        $maker->update($data);

        return redirect()->route('admin.makers.index')->with('status', 'メーカーを更新しました。');
    }

    public function destroy(Maker $maker): RedirectResponse
    {
        $maker->update(['is_active' => false]);

        return redirect()->route('admin.makers.index')->with('status', 'メーカーを無効化しました。');
    }
}
