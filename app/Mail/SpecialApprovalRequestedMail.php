<?php

namespace App\Mail;

use App\Models\Application;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class SpecialApprovalRequestedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public Application $application
    ) {}

    public function build()
    {
        return $this->subject('【ユニフォーム管理システム】特別承認が必要な申請があります')
            ->view('emails.special-approval-requested')
            ->with([
                'application' => $this->application,
            ]);
    }
}
