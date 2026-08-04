<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdminUser
{
    /**
     * 鎌谷側（admin_users）のみ管理画面へアクセス可能。
     * ブロック済み・ソフトデリート済みは拒否する。
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            abort(403, 'ログインが必要です。');
        }

        $admin = $user->adminUser()->withTrashed()->first();

        if (!$admin || $admin->trashed() || $admin->isBlocked()) {
            abort(403, '管理者権限がありません。');
        }

        return $next($request);
    }
}
