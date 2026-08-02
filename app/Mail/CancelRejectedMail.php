<?php

namespace App\Mail;

use App\Models\Application;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class CancelRejectedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Application $application
    ) {}

    public function build()
    {
        return $this->subject('【ユニフォーム管理システム】キャンセル申請が却下されました')
            ->view('emails.cancel-rejected')
            ->with([
                'application' => $this->application,
            ]);
    }
}
