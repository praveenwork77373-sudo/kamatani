<?php

namespace App\Mail;

use App\Models\Application;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class CancelRequestedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Application $application
    ) {}

    public function build()
    {
        return $this->subject('【ユニフォーム管理システム】キャンセル申請が送信されました')
            ->view('emails.cancel-requested')
            ->with([
                'application' => $this->application,
            ]);
    }
}
