<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdminRole
{
    /**
     * 指定ロールのいずれかを持つ管理者のみ許可。
     * 使用例: middleware('admin.role:system_admin,operation')
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $admin = $request->user()?->adminUser()->withTrashed()->first();

        if (!$admin || $admin->trashed() || $admin->isBlocked()) {
            abort(403, '管理者権限がありません。');
        }

        if (!empty($roles) && !in_array($admin->role, $roles, true)) {
            abort(403, 'この操作を行う権限がありません。');
        }

        return $next($request);
    }
}
