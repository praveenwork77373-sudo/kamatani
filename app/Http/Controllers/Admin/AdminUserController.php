<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminUser;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AdminUserController extends Controller
{
    /**
     * Check if user is system admin
     */
    private function checkSystemAdmin(): void
    {
        $user = request()->user();
        $admin = $user->adminUser;

        if (! $admin || $admin->role !== 'system_admin') {
            abort(403, 'システム管理者のみアクセス可能です。');
        }
    }

    /**
     * システム管理者ロールを外す操作のガード
     */
    private function assertRoleChangeAllowed(AdminUser $adminUser, string $newRole): void
    {
        if ($adminUser->role !== 'system_admin' || $newRole === 'system_admin') {
            return;
        }

        $currentAdmin = request()->user()->adminUser;

        // 自分自身のシステム管理者権限は外せない
        if ($currentAdmin && (int) $adminUser->id === (int) $currentAdmin->id) {
            throw ValidationException::withMessages([
                'role' => __('pages.cannot_demote_yourself'),
            ]);
        }

        // 最後のシステム管理者は外せない
        $otherSystemAdmins = AdminUser::query()
            ->where('role', 'system_admin')
            ->whereNull('deleted_at')
            ->where('id', '!=', $adminUser->id)
            ->count();

        if ($otherSystemAdmins === 0) {
            throw ValidationException::withMessages([
                'role' => __('pages.cannot_demote_last_system_admin'),
            ]);
        }
    }

    /**
     * Display a listing of admin users
     */
    public function index(Request $request): View
    {
        $this->checkSystemAdmin();

        // Filter by status first to determine if we need trashed records
        $includeTrashed = false;
        if ($request->filled('status') && $request->status === 'deleted') {
            $includeTrashed = true;
        } elseif (! $request->filled('status')) {
            // If no status filter, show all including trashed
            $includeTrashed = true;
        }

        $query = AdminUser::with('user');

        if ($includeTrashed) {
            $query->withTrashed();
        }

        // Filter by role
        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }

        // Filter by status
        if ($request->filled('status')) {
            if ($request->status === 'active') {
                $query->whereNull('blocked_at')->whereNull('deleted_at');
            } elseif ($request->status === 'blocked') {
                $query->whereNotNull('blocked_at')->whereNull('deleted_at');
            } elseif ($request->status === 'deleted') {
                $query->onlyTrashed();
            }
        }

        // Search by name or email
        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $adminUsers = $query->orderBy('created_at', 'desc')
            ->paginate(20)
            ->withQueryString();

        // Calculate statistics (without filters)
        $statsQuery = AdminUser::withTrashed();
        $totalAdmins = (clone $statsQuery)->count();
        $activeAdmins = (clone $statsQuery)->whereNull('blocked_at')->whereNull('deleted_at')->count();
        $blockedAdmins = (clone $statsQuery)->whereNotNull('blocked_at')->whereNull('deleted_at')->count();
        $deletedAdmins = (clone $statsQuery)->onlyTrashed()->count();
        $systemAdmins = (clone $statsQuery)->where('role', 'system_admin')->whereNull('deleted_at')->count();
        $operationAdmins = (clone $statsQuery)->where('role', 'operation')->whereNull('deleted_at')->count();
        $salesAdmins = (clone $statsQuery)->where('role', 'sales')->whereNull('deleted_at')->count();
        $accountingAdmins = (clone $statsQuery)->where('role', 'accounting')->whereNull('deleted_at')->count();

        return view('admin.admin-users.index', [
            'adminUsers' => $adminUsers,
            'selectedRole' => $request->role,
            'selectedStatus' => $request->status,
            'searchQuery' => $request->search,
            'stats' => [
                'total_admins' => $totalAdmins,
                'active_admins' => $activeAdmins,
                'blocked_admins' => $blockedAdmins,
                'deleted_admins' => $deletedAdmins,
                'system_admins' => $systemAdmins,
                'operation_admins' => $operationAdmins,
                'sales_admins' => $salesAdmins,
                'accounting_admins' => $accountingAdmins,
            ],
        ]);
    }

    /**
     * Show the form for creating a new admin user
     */
    public function create(): View
    {
        $this->checkSystemAdmin();

        return view('admin.admin-users.create');
    }

    /**
     * Store a newly created admin user
     *
     * システム管理者が氏名・メール・ロール・パスワードを設定して発行する。
     */
    public function store(Request $request): RedirectResponse
    {
        $this->checkSystemAdmin();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'role' => ['required', 'in:system_admin,operation,sales,accounting'],
        ]);

        // password は User の hashed キャストに任せる（二重ハッシュ防止）
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
            'type' => 'kamatani_admin',
        ]);

        AdminUser::create([
            'user_id' => $user->id,
            'role' => $data['role'],
        ]);

        return redirect()->route('admin.admin-users.index')
            ->with('status', __('pages.admin_user_created_successfully'));
    }

    /**
     * Show the form for editing an admin user
     */
    public function edit(AdminUser $adminUser): View
    {
        $this->checkSystemAdmin();
        $adminUser->load('user');

        return view('admin.admin-users.edit', ['adminUser' => $adminUser]);
    }

    /**
     * Update an admin user
     *
     * パスワードは任意。入力時のみシステム管理者が再設定する。
     */
    public function update(Request $request, AdminUser $adminUser): RedirectResponse
    {
        $this->checkSystemAdmin();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($adminUser->user_id)],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'role' => ['required', 'in:system_admin,operation,sales,accounting'],
        ]);

        $this->assertRoleChangeAllowed($adminUser, $data['role']);

        $userData = [
            'name' => $data['name'],
            'email' => $data['email'],
        ];

        if (! empty($data['password'])) {
            $userData['password'] = $data['password'];
            $userData['remember_token'] = Str::random(60);

            // ブロック中でなければ一時ロックも解除（新パスワードで即ログイン可能に）
            if (! $adminUser->isBlocked()) {
                $userData['locked_until'] = null;
            }
        }

        $adminUser->user->update($userData);

        $adminUser->update([
            'role' => $data['role'],
        ]);

        return redirect()->route('admin.admin-users.index')
            ->with('status', __('pages.admin_user_updated_successfully'));
    }

    /**
     * Block an admin user
     */
    public function block(Request $request, AdminUser $adminUser): RedirectResponse
    {
        $this->checkSystemAdmin();

        // Prevent blocking yourself
        if ($adminUser->id === auth()->user()->adminUser->id) {
            return redirect()->route('admin.admin-users.index')
                ->with('error', __('pages.cannot_block_yourself'));
        }

        $data = $request->validate([
            'blocked_reason' => ['required', 'string', 'max:1000'],
        ]);

        $adminUser->update([
            'blocked_at' => now(),
            'blocked_reason' => $data['blocked_reason'],
        ]);

        // Also lock the user account
        $adminUser->user->update([
            'locked_until' => now()->addYears(100), // Effectively permanent
        ]);

        return redirect()->route('admin.admin-users.index')
            ->with('status', __('pages.admin_user_blocked_successfully'));
    }

    /**
     * Unblock an admin user
     */
    public function unblock(AdminUser $adminUser): RedirectResponse
    {
        $this->checkSystemAdmin();

        $adminUser->update([
            'blocked_at' => null,
            'blocked_reason' => null,
        ]);

        // Unlock the user account
        $adminUser->user->update([
            'locked_until' => null,
        ]);

        return redirect()->route('admin.admin-users.index')
            ->with('status', __('pages.admin_user_unblocked_successfully'));
    }

    /**
     * Delete (soft delete) an admin user
     */
    public function destroy(Request $request, AdminUser $adminUser): RedirectResponse
    {
        $this->checkSystemAdmin();

        // Prevent deleting yourself
        if ($adminUser->id === auth()->user()->adminUser->id) {
            return redirect()->route('admin.admin-users.index')
                ->with('error', __('pages.cannot_delete_yourself'));
        }

        $data = $request->validate([
            'deleted_reason' => ['required', 'string', 'max:1000'],
        ]);

        $adminUser->update([
            'deleted_reason' => $data['deleted_reason'],
        ]);

        $adminUser->delete();

        return redirect()->route('admin.admin-users.index')
            ->with('status', __('pages.admin_user_deleted_successfully'));
    }

    /**
     * Restore a deleted admin user
     */
    public function restore($id): RedirectResponse
    {
        $this->checkSystemAdmin();

        // Find the admin user including soft deleted ones
        $adminUser = AdminUser::withTrashed()->findOrFail($id);

        // Check if it's actually deleted
        if (! $adminUser->trashed()) {
            return redirect()->route('admin.admin-users.index')
                ->with('error', __('pages.admin_user_not_deleted'));
        }

        $adminUser->restore();
        $adminUser->update([
            'deleted_reason' => null,
        ]);

        return redirect()->route('admin.admin-users.index')
            ->with('status', __('pages.admin_user_restored_successfully'));
    }
}
