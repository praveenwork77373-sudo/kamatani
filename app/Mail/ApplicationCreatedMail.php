<?php

namespace App\Mail;

use App\Models\Application;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ApplicationCreatedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public Application $application
    ) {}

    public function build()
    {
        return $this->subject('【ユニフォーム管理システム】新しい申請が登録されました')
            ->view('emails.application-created')
            ->with([
                'application' => $this->application,
            ]);
    }
}
