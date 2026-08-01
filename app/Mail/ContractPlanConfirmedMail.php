<?php

namespace App\Mail;

use App\Models\CustomerContractPlan;
use App\Models\MedicalInstitution;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ContractPlanConfirmedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public MedicalInstitution $institution,
        public CustomerContractPlan $customerContractPlan,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '【ユニフォーム管理システム】契約プラン本登録のお知らせ',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.contract-plan-confirmed',
        );
    }
}
