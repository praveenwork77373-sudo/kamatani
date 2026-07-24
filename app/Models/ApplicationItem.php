<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApplicationItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'application_id',
        'uniform_product_id',
        'quantity',
        'action',
    ];

    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    public function uniformProduct(): BelongsTo
    {
        return $this->belongsTo(UniformProduct::class);
    }
}


