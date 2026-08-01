<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureCustomerUser
{
    /**
     * Hospital portal: only medical-institution (customer) users.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user || !$user->customerUser) {
            abort(403, '医療機関ユーザーとしてログインしてください。');
        }

        return $next($request);
    }
}
