<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DatabaseBackupSetting extends Model
{
    protected $fillable = [
        'enabled',
        'frequency',
        'time',
        'day_of_week',
        'day_of_month',
        'retain_days',
        'last_run_at',
        'updated_by_user_id',
    ];

    protected $casts = [
        'enabled' => 'boolean',
        'day_of_week' => 'integer',
        'day_of_month' => 'integer',
        'retain_days' => 'integer',
        'last_run_at' => 'datetime',
    ];

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by_user_id');
    }
}
