<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WelcomeEmailSendHistory extends Model
{
    protected $fillable = [
        'medical_institution_id',
        'user_id',
        'sent_by_user_id',
        'destination_email',
        'status',
        'error_message',
        'sent_at',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
    ];

    public function medicalInstitution(): BelongsTo
    {
        return $this->belongsTo(MedicalInstitution::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function sentBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sent_by_user_id');
    }
}
