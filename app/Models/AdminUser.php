<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class AdminUser extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'role',
        'receives_app_notifications',
        'blocked_at',
        'blocked_reason',
        'deleted_reason',
    ];

    protected $casts = [
        'blocked_at' => 'datetime',
        'deleted_at' => 'datetime',
        'receives_app_notifications' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isBlocked(): bool
    {
        return $this->blocked_at !== null;
    }

    public function isActive(): bool
    {
        return !$this->isBlocked() && $this->deleted_at === null;
    }
}


