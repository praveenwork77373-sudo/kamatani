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
    /** 連続失敗回数の上限 */
    private const MAX_FAILED_ATTEMPTS = 5;

    /** 一時ロック時間（分） */
    private const LOCK_MINUTES = 15;

    public function showLoginForm(): View
    {
        return view('auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        /** @var User|null $user */
        $user = User::where('email', $credentials['email'])->first();

        // ロック期限切れなら自動解除
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

        // ブロック／削除済み管理者はログイン不可
        $authUser = Auth::user();
        $adminProfile = $authUser?->adminUser()->withTrashed()->first();
        if ($adminProfile && ($adminProfile->trashed() || $adminProfile->isBlocked())) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            throw ValidationException::withMessages([
                'email' => 'このアカウントは利用停止されています。管理者にお問い合わせください。',
            ]);
        }

        // 成功時は一時ロックをクリア
        if ($authUser && $authUser->locked_until) {
            $authUser->forceFill(['locked_until' => null])->save();
        }

        $request->session()->regenerate();

        return redirect()->intended('/dashboard');
    }

    /**
     * 連続失敗による一時ロック
     */
    private function handleFailedLogin(User $user): void
    {
        $recentFails = LoginAttempt::where('user_id', $user->id)
            ->where('is_success', false)
            ->where('attempted_at', '>=', now()->subMinutes(self::LOCK_MINUTES))
            ->orderByDesc('attempted_at')
            ->take(self::MAX_FAILED_ATTEMPTS)
            ->get();

        if ($recentFails->count() < self::MAX_FAILED_ATTEMPTS) {
            return;
        }

        // 既にロック中なら再通知しない
        $wasAlreadyLocked = $user->locked_until && $user->locked_until->isFuture();

        $user->locked_until = Carbon::now()->addMinutes(self::LOCK_MINUTES);
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
                self::LOCK_MINUTES,
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
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    public function showForgotPasswordForm(): View
    {
        return view('auth.forgot-password');
    }

    public function sendResetLink(Request $request): RedirectResponse
    {
        $request->validate(['email' => ['required', 'email']]);

        // 登録有無を推測させないため、応答メッセージは共通にする
        Password::sendResetLink($request->only('email'));

        return back()->with([
            'status' => '登録されているメールアドレスの場合、パスワード再設定用のURLを送信しました。（有効期限: 24時間・1回限り）',
        ]);
    }

    public function showResetForm(Request $request, string $token): View
    {
        return view('auth.reset-password', [
            'token' => $token,
            'email' => $request->query('email', old('email')),
        ]);
    }

    public function resetPassword(Request $request): RedirectResponse
    {
        $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', 'min:8'],
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                // User モデルの hashed キャストに任せる（二重ハッシュ防止）
                $user->forceFill([
                    'password' => $password,
                    'locked_until' => null,
                ])->save();

                // 既存セッション／remember を無効化
                $user->setRememberToken(\Illuminate\Support\Str::random(60));
                $user->save();
            }
        );

        return $status === Password::PASSWORD_RESET
            ? redirect()->route('login')->with('status', 'パスワードを変更しました。ログインしてください。')
            : back()->withErrors([
                'email' => match ($status) {
                    Password::INVALID_TOKEN => '再設定リンクが無効、期限切れ、または使用済みです。再度お手続きください。',
                    Password::INVALID_USER => 'メールアドレスが登録されていません。',
                    default => 'パスワード再設定に失敗しました。',
                },
            ]);
    }
}
