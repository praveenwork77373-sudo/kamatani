<?php

namespace App\Services;

use App\Mail\WelcomeStartUseMail;
use App\Models\MedicalInstitution;
use App\Models\User;
use App\Models\WelcomeEmailSendHistory;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class WelcomeEmailService
{
    /**
     * Generate temp password, send welcome mail, record history.
     * Returns the temporary password on success (for UI flash only).
     */
    public function send(MedicalInstitution $institution, User $recipient, ?User $sender = null, ?string $temporaryPassword = null): string
    {
        $temporaryPassword = $temporaryPassword ?: Str::password(12);
        $loginUrl = route('login');

        $recipient->forceFill([
            'password' => $temporaryPassword,
            'must_change_password' => true,
        ])->save();

        $history = WelcomeEmailSendHistory::create([
            'medical_institution_id' => $institution->id,
            'user_id' => $recipient->id,
            'sent_by_user_id' => $sender?->id,
            'destination_email' => $recipient->email,
            'status' => 'sent',
            'sent_at' => now(),
        ]);

        try {
            Mail::to($recipient->email)->send(new WelcomeStartUseMail(
                $recipient,
                $institution,
                $temporaryPassword,
                $loginUrl,
            ));
        } catch (\Throwable $e) {
            $history->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);
            Log::error('Welcome email failed', [
                'institution_id' => $institution->id,
                'user_id' => $recipient->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }

        return $temporaryPassword;
    }
}
