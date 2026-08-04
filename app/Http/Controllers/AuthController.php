<?php

namespace App\Http\Controllers;

use App\Mail\AccountLockedMail;
use App\Models\LoginAttempt;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AuthController extends Controller
{
    public const PORTAL_HOSPITAL = 'hospital';
    public const PORTAL_ADMIN = 'admin';

    private const MAX_FAILED_ATTEMPTS = 5;
    private const LOCK_MINUTES = 15;

    private function maxFailedAttempts(): int
    {
        return (int) config('auth.lockout.max_attempts', self::MAX_FAILED_ATTEMPTS);
    }

    private function lockMinutes(): int
    {
        return (int) config('auth.lockout.decay_minutes', self::LOCK_MINUTES);
    }

    public function showLoginForm(): View
    {
        return view('auth.login', ['portal' => self::PORTAL_HOSPITAL]);
    }

    public function showAdminLoginForm(): View
    {
        return view('auth.admin-login', ['portal' => self::PORTAL_ADMIN]);
    }

    public function login(Request $request): RedirectResponse
    {
        return $this->attemptLogin($request, self::PORTAL_HOSPITAL);
    }

    public function adminLogin(Request $request): RedirectResponse
    {
        return $this->attemptLogin($request, self::PORTAL_ADMIN);
    }

    private function attemptLogin(Request $request, string $portal): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        /** @var User|null $user */
        $user = User::where('email', $credentials['email'])->first();

        if ($user && $user->locked_until && $user->locked_until->isPast()) {
            $user->forceFill(['locked_until' => null])->save();
            $user->refresh();
        }

        if ($user && $user->locked_until && $user->locked_until->isFuture()) {
            $until = $user->locked_until
                ->timezone(config('app.timezone'))
                ->format('Y年n月j日 H:i');
            throw ValidationException::withMessages([
                'email' => "このアカウントは一時的にロックされています。解除予定: {$until}",
            ]);
        }

        $successful = Auth::attempt($credentials, $request->boolean('remember'));

        LoginAttempt::create([
            'user_id' => $user?->id,
            'email' => $credentials['email'],
            'is_success' => $successful,
            'attempted_at' => now(),
        ]);

        if (! $successful) {
            if ($user) {
                $this->handleFailedLogin($user);
            }

            throw ValidationException::withMessages([
                'email' => 'ログイン情報が正しくありません。',
            ]);
        }

        $authUser = Auth::user();

        try {
            $this->assertPortalAllowed($authUser, $portal);
        } catch (ValidationException $e) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            throw $e;
        }

        $adminProfile = $authUser?->adminUser()->withTrashed()->first();

        if ($portal === self::PORTAL_ADMIN) {
            if (!$adminProfile || $adminProfile->trashed() || $adminProfile->isBlocked()) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                throw ValidationException::withMessages([
                    'email' => $adminProfile && ($adminProfile->trashed() || $adminProfile->isBlocked())
                        ? 'このアカウントは利用停止されています。管理者にお問い合わせください。'
                        : 'カマタニ管理者アカウントでログインしてください。医療機関の方は病院ログインをご利用ください。',
                ]);
            }
        }

        if ($portal === self::PORTAL_HOSPITAL) {
            if (!$authUser?->customerUser) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                throw ValidationException::withMessages([
                    'email' => '医療機関アカウントでログインしてください。カマタニ管理者の方は管理者ログインをご利用ください。',
                ]);
            }
        }

        if ($authUser && $authUser->locked_until) {
            $authUser->forceFill(['locked_until' => null])->save();
        }

        $request->session()->regenerate();
        $request->session()->put('auth_portal', $portal);

        if ($authUser && $authUser->must_change_password) {
            return redirect()->route('password.force-change');
        }

        return redirect()->intended(route('dashboard'));
    }

    public function showForceChangePasswordForm(): View
    {
        return view('auth.force-change-password');
    }

    public function updateForcedPassword(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = $request->user();
        $user->forceFill([
            'password' => $request->input('password'),
            'must_change_password' => false,
        ])->save();

        return redirect()->route('dashboard')->with('status', 'パスワードを変更しました。');
    }

    private function assertPortalAllowed(User $user, string $portal): void
    {
        $user->loadMissing(['customerUser', 'adminUser']);

        if ($portal === self::PORTAL_HOSPITAL && !$user->customerUser) {
            throw ValidationException::withMessages([
                'email' => '医療機関アカウントでログインしてください。カマタニ管理者の方は管理者ログインをご利用ください。',
            ]);
        }

        if ($portal === self::PORTAL_ADMIN) {
            $admin = $user->adminUser()->withTrashed()->first();
            if (!$admin) {
                throw ValidationException::withMessages([
                    'email' => 'カマタニ管理者アカウントでログインしてください。医療機関の方は病院ログインをご利用ください。',
                ]);
            }
            if ($admin->trashed() || $admin->isBlocked()) {
                throw ValidationException::withMessages([
                    'email' => 'このアカウントは利用停止されています。管理者にお問い合わせください。',
                ]);
            }
        }
    }

    private function handleFailedLogin(User $user): void
    {
        $lockMinutes = $this->lockMinutes();
        $maxAttempts = $this->maxFailedAttempts();

        $recentFails = LoginAttempt::where('user_id', $user->id)
            ->where('is_success', false)
            ->where('attempted_at', '>=', now()->subMinutes($lockMinutes))
            ->orderByDesc('attempted_at')
            ->take($maxAttempts)
            ->get();

        if ($recentFails->count() < $maxAttempts) {
            return;
        }

        $wasAlreadyLocked = $user->locked_until && $user->locked_until->isFuture();

        $user->locked_until = Carbon::now()->addMinutes($lockMinutes);
        $user->save();

        if (! $wasAlreadyLocked) {
            $this->sendLockNotification($user);
        }
    }

    private function sendLockNotification(User $user): void
    {
        try {
            Mail::to($user->email)->send(new AccountLockedMail(
                $user,
                $this->lockMinutes(),
                $user->locked_until->timezone(config('app.timezone'))->format('Y年n月j日 H:i')
            ));
        } catch (\Throwable $e) {
            Log::error('アカウントロック通知メール送信失敗', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function logout(Request $request): RedirectResponse
    {
        $portal = $request->session()->get('auth_portal');
        $user = $request->user();
        if (!$portal && $user) {
            $portal = $user->adminUser ? self::PORTAL_ADMIN : self::PORTAL_HOSPITAL;
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route(
            $portal === self::PORTAL_ADMIN ? 'admin.login' : 'login'
        );
    }

    public function showForgotPasswordForm(Request $request): View
    {
        $portal = $request->query('portal', self::PORTAL_HOSPITAL);
        if (! in_array($portal, [self::PORTAL_HOSPITAL, self::PORTAL_ADMIN], true)) {
            $portal = self::PORTAL_HOSPITAL;
        }

        return view('auth.forgot-password', ['portal' => $portal]);
    }

    public function sendResetLink(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
            'portal' => ['nullable', 'in:hospital,admin'],
        ]);

        try {
            Password::sendResetLink($request->only('email'));
        } catch (\Throwable $e) {
            Log::error('Password reset email failed', [
                'email' => $request->input('email'),
                'error' => $e->getMessage(),
            ]);
        }

        // Always generic success — do not leak whether the email exists
        return back()->with([
            'status' => '登録されているメールアドレスの場合、パスワード再設定用のURLを送信しました。（有効期限: 24時間・1回限り）',
        ]);
    }

    public function showResetForm(Request $request, string $token): View
    {
        return view('auth.reset-password', [
            'token' => $token,
            'email' => $request->query('email', old('email')),
            'portal' => $request->query('portal', old('portal', self::PORTAL_HOSPITAL)),
        ]);
    }

    public function resetPassword(Request $request): RedirectResponse
    {
        $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', 'min:8'],
            'portal' => ['nullable', 'in:hospital,admin'],
        ]);

        $portal = $request->input('portal', self::PORTAL_HOSPITAL);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                $user->forceFill([
                    'password' => $password,
                    'locked_until' => null,
                ])->save();

                $user->setRememberToken(\Illuminate\Support\Str::random(60));
                $user->save();
            }
        );

        $loginRoute = $portal === self::PORTAL_ADMIN ? 'admin.login' : 'login';

        // If portal omitted, pick by account type
        if ($status === Password::PASSWORD_RESET) {
            $user = User::where('email', $request->input('email'))->first();
            if ($user?->adminUser && !$user->customerUser) {
                $loginRoute = 'admin.login';
            } elseif ($user?->customerUser && !$user->adminUser) {
                $loginRoute = 'login';
            }
        }

        return $status === Password::PASSWORD_RESET
            ? redirect()->route($loginRoute)->with('status', 'パスワードを変更しました。ログインしてください。')
            : back()->withErrors([
                'email' => match ($status) {
                    Password::INVALID_TOKEN => '再設定リンクが無効、期限切れ、または使用済みです。再度お手続きください。',
                    Password::INVALID_USER => 'メールアドレスが登録されていません。',
                    default => 'パスワード再設定に失敗しました。',
                },
            ]);
    }
}
