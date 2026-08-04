<?php

namespace Tests\Feature;

use App\Mail\AccountLockedMail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class AuthLockoutAndPasswordTest extends TestCase
{
    use RefreshDatabase;

    public function test_account_locks_after_five_failed_logins_and_sends_mail(): void
    {
        Mail::fake();

        $user = User::factory()->create([
            'email' => 'locktest@example.com',
            'password' => Hash::make('correct-password'),
        ]);

        for ($i = 0; $i < 5; $i++) {
            $this->post(route('login.post'), [
                'email' => 'locktest@example.com',
                'password' => 'wrong-password',
            ]);
        }

        $user->refresh();
        $this->assertNotNull($user->locked_until);
        $this->assertTrue($user->locked_until->isFuture());

        Mail::assertQueued(AccountLockedMail::class, function (AccountLockedMail $mail) use ($user) {
            return $mail->user->id === $user->id;
        });

        $this->post(route('login.post'), [
            'email' => 'locktest@example.com',
            'password' => 'correct-password',
        ])->assertSessionHasErrors('email');
    }

    public function test_password_reset_expire_config_is_24_hours(): void
    {
        $this->assertSame(60 * 24, config('auth.passwords.users.expire'));
    }
}
