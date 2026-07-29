<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminUser;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
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
     * パスワード設定用の再設定メールを送信
     */
    private function sendPasswordSetupLink(User $user): string
    {
        return Password::sendResetLink(['email' => $user->email]);
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
     * システム管理者はアカウント（氏名・メール・ロール）のみ発行する。
     * パスワードは本人が再設定メールから設定する。
     */
    public function store(Request $request): RedirectResponse
    {
        $this->checkSystemAdmin();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'role' => ['required', 'in:system_admin,operation,sales,accounting'],
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            // 仮パスワード（本人はログイン不可想定）。hashed キャストで保存。
            'password' => Str::password(32),
            'type' => 'kamatani_admin',
        ]);

        AdminUser::create([
            'user_id' => $user->id,
            'role' => $data['role'],
        ]);

        $resetStatus = $this->sendPasswordSetupLink($user);

        $message = __('pages.admin_user_created_successfully');
        if ($resetStatus === Password::RESET_LINK_SENT) {
            $message .= ' '.__('pages.admin_user_password_setup_email_sent');
        } else {
            $message .= ' '.__('pages.admin_user_password_setup_email_failed');
        }

        return redirect()->route('admin.admin-users.index')
            ->with('status', $message);
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
     * Update an admin user (identity / role only — not password)
     */
    public function update(Request $request, AdminUser $adminUser): RedirectResponse
    {
        $this->checkSystemAdmin();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($adminUser->user_id)],
            'role' => ['required', 'in:system_admin,operation,sales,accounting'],
        ]);

        $this->assertRoleChangeAllowed($adminUser, $data['role']);

        $adminUser->user->update([
            'name' => $data['name'],
            'email' => $data['email'],
        ]);

        $adminUser->update([
            'role' => $data['role'],
        ]);

        return redirect()->route('admin.admin-users.index')
            ->with('status', __('pages.admin_user_updated_successfully'));
    }

    /**
     * 対象管理者へパスワード再設定メールを送信する
     */
    public function sendPasswordReset(AdminUser $adminUser): RedirectResponse
    {
        $this->checkSystemAdmin();

        if ($adminUser->trashed() || $adminUser->isBlocked()) {
            return redirect()->route('admin.admin-users.edit', $adminUser)
                ->with('error', __('pages.cannot_send_password_reset_to_inactive'));
        }

        $status = $this->sendPasswordSetupLink($adminUser->user);

        if ($status === Password::RESET_LINK_SENT) {
            return redirect()->route('admin.admin-users.edit', $adminUser)
                ->with('status', __('pages.admin_user_password_setup_email_sent'));
        }

        return redirect()->route('admin.admin-users.edit', $adminUser)
            ->with('error', __('pages.admin_user_password_setup_email_failed'));
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
