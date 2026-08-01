<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'type',
        'locked_until',
        'must_change_password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'locked_until' => 'datetime',
            'must_change_password' => 'boolean',
        ];
    }

    /**
     * パスワード再設定通知（有効期限 24 時間を明記）
     */
    public function sendPasswordResetNotification(#[\SensitiveParameter] $token): void
    {
        $this->notify(new class($token) extends ResetPassword {
            public function toMail($notifiable)
            {
                $mail = parent::toMail($notifiable);
                $mail->line('このリンクの有効期限は 24 時間です。期限切れ、または一度使用したリンクは無効になります。');

                return $mail;
            }
        });
    }

    public function customerUser(): HasOne
    {
        return $this->hasOne(CustomerUser::class);
    }

    public function adminUser(): HasOne
    {
        return $this->hasOne(AdminUser::class);
    }

    public function loginAttempts(): HasMany
    {
        return $this->hasMany(LoginAttempt::class);
    }

    public function isTemporarilyLocked(): bool
    {
        return $this->locked_until !== null && $this->locked_until->isFuture();
    }
}
