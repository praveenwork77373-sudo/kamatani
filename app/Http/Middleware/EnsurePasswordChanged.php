<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePasswordChanged
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->must_change_password) {
            $allowed = [
                'password.force-change',
                'password.force-change.update',
                'logout',
                'language.switch',
            ];

            if (! $request->routeIs(...$allowed)) {
                return redirect()->route('password.force-change');
            }
        }

        return $next($request);
    }
}
