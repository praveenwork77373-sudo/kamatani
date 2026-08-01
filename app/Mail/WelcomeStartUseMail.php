<?php

namespace App\Mail;

use App\Models\MedicalInstitution;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class WelcomeStartUseMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public MedicalInstitution $institution,
        public string $temporaryPassword,
        public string $loginUrl,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '【ユニフォーム管理システム】ご利用開始のご案内',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.welcome-start-use',
        );
    }
}
