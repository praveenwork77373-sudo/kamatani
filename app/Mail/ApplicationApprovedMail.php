<?php

namespace App\Mail;

use App\Models\Application;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ApplicationApprovedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public Application $application
    ) {}

    public function build()
    {
        return $this->subject(__('pages.mail_subject_app_approved'))
            ->view('emails.application-approved')
            ->with([
                'application' => $this->application,
            ]);
    }
}
