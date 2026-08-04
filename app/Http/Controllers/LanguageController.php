<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LanguageController extends Controller
{
    public function switch(Request $request, string $locale): RedirectResponse
    {
        if (in_array($locale, ['ja', 'en'], true)) {
            session(['locale' => $locale]);
        } else {
            session(['locale' => 'ja']);
        }

        $fallback = $request->user()
            ? route('dashboard')
            : (str_contains((string) $request->headers->get('referer'), '/admin')
                ? route('admin.login')
                : route('login'));

        return redirect()->back(fallback: $fallback);
    }
}
