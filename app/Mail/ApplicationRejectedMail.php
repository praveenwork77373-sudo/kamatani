<?php

namespace App\Mail;

use App\Models\Application;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ApplicationRejectedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Application $application
    ) {}

    public function build()
    {
        return $this->subject('【ユニフォーム管理システム】申請が却下されました')
            ->view('emails.application-rejected')
            ->with([
                'application' => $this->application,
            ]);
    }
}
