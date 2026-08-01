<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Maker;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MakerController extends Controller
{
    public function index(): View
    {
        $makers = Maker::orderBy('code')->paginate(30);

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
            'code' => ['required', 'string', 'max:50', 'unique:makers,code'],
            'is_active' => ['nullable', 'boolean'],
        ]);
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
            'code' => ['required', 'string', 'max:50', 'unique:makers,code,' . $maker->id],
            'is_active' => ['nullable', 'boolean'],
        ]);
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
