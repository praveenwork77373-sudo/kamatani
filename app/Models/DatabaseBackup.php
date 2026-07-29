<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DatabaseBackup extends Model
{
    protected $fillable = [
        'filename',
        'path',
        'size_bytes',
        'type',
        'status',
        'error_message',
        'created_by_user_id',
    ];

    protected $casts = [
        'size_bytes' => 'integer',
    ];

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function absolutePath(): string
    {
        return storage_path('app/' . ltrim(str_replace('\\', '/', $this->path), '/'));
    }

    public function fileExists(): bool
    {
        return is_file($this->absolutePath());
    }

    public function humanSize(): string
    {
        $bytes = (int) $this->size_bytes;
        if ($bytes < 1024) {
            return $bytes . ' B';
        }
        if ($bytes < 1048576) {
            return round($bytes / 1024, 1) . ' KB';
        }
        if ($bytes < 1073741824) {
            return round($bytes / 1048576, 1) . ' MB';
        }

        return round($bytes / 1073741824, 2) . ' GB';
    }
}
