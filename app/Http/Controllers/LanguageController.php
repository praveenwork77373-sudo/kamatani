<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LanguageController extends Controller
{
    public function switch(Request $request, string $locale): RedirectResponse
    {
        if (in_array($locale, ['ja', 'en'])) {
            session(['locale' => $locale]);
        } else {
            // Default to Japanese if invalid locale
            session(['locale' => 'ja']);
        }

        return redirect()->back();
    }
}
