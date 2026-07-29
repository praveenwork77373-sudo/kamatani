<?php

namespace App\Http\Controllers;

use App\Models\LoginAttempt;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AuthController extends Controller
{
    private const MAX_FAILED_ATTEMPTS = 5;
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

        if ($user && $user->locked_until && $user->locked_until->isFuture()) {
            throw ValidationException::withMessages([
                'email' => 'このアカウントは一時的にロックされています。しばらくしてから再度お試しください。',
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
                $recentFails = LoginAttempt::where('user_id', $user->id)
                    ->where('is_success', false)
                    ->where('attempted_at', '>=', now()->subMinutes(self::LOCK_MINUTES))
                    ->orderByDesc('attempted_at')
                    ->take(self::MAX_FAILED_ATTEMPTS)
                    ->get();

                if ($recentFails->count() >= self::MAX_FAILED_ATTEMPTS) {
                    $user->locked_until = Carbon::now()->addMinutes(self::LOCK_MINUTES);
                    $user->save();
                }
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

        $request->session()->regenerate();

        return redirect()->intended('/dashboard');
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

        $status = Password::sendResetLink(
            $request->only('email')
        );

        return $status === Password::RESET_LINK_SENT
            ? back()->with(['status' => __('パスワード再設定用のメールを送信しました。')])
            : back()->withErrors(['email' => __('メールアドレスが登録されていません。')]);
    }

    public function showResetForm(string $token): View
    {
        return view('auth.reset-password', ['token' => $token]);
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
            function ($user, $password) {
                $user->forceFill([
                    'password' => Hash::make($password),
                ])->save();
            }
        );

        return $status === Password::PASSWORD_RESET
            ? redirect()->route('login')->with('status', __('パスワードを変更しました。ログインしてください。'))
            : back()->withErrors(['email' => __('パスワード再設定に失敗しました。')]);
    }
}


