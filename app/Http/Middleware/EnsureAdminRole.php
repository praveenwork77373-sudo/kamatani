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
            abort(403, __('pages.flash_716094971b'));
        }

        if (!empty($roles) && !in_array($admin->role, $roles, true)) {
            abort(403, __('pages.flash_d58df33ab9'));
        }

        return $next($request);
    }
}
