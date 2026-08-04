<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use App\Support\Pagination;

/**
 * 一時ログインロックの管理（システム管理者のみ）
 */
class AccountLockController extends Controller
{
    private function ensureSystemAdmin(): void
    {
        $admin = request()->user()?->adminUser;
        if (!$admin || $admin->role !== 'system_admin') {
            abort(403, __('pages.flash_2fb39bebc0'));
        }
    }

    public function index(Request $request): View
    {
        $this->ensureSystemAdmin();

        $query = User::query()
            ->with(['adminUser', 'customerUser.medicalInstitution'])
            ->whereNotNull('locked_until')
            ->where('locked_until', '>', now())
            ->orderByDesc('locked_until');

        if ($search = $request->string('q')->toString()) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $lockedUsers = $query->paginate(Pagination::perPage($request))->withQueryString();

        return view('admin.account-locks.index', [
            'lockedUsers' => $lockedUsers,
            'searchQuery' => $search,
        ]);
    }

    public function unlock(User $user): RedirectResponse
    {
        $this->ensureSystemAdmin();

        $user->forceFill(['locked_until' => null])->save();

        return back()->with('status', "{$user->name}（{$user->email}）の一時ロックを解除しました。");
    }
}
