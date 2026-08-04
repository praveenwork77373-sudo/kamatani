<?php

namespace App\Mail;

use App\Models\Application;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class CancelRequestedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public Application $application
    ) {}

    public function build()
    {
        return $this->subject(__('pages.mail_subject_cancel_req'))
            ->view('emails.cancel-requested')
            ->with([
                'application' => $this->application,
            ]);
    }
}
