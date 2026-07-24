<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        // Default to Japanese if no locale is set in session
        $locale = Session::get('locale', 'ja');
        App::setLocale($locale);
        return $next($request);
    }
}
