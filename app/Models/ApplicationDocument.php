<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApplicationDocument extends Model
{
    public const TYPE_ORDER_SLIP = 'order_slip';
    public const TYPE_DELIVERY_NOTICE = 'delivery_notice';
    public const TYPE_SHIPPING_SALES = 'shipping_sales';
    public const TYPE_SHIPPING_WAREHOUSE = 'shipping_warehouse';
    public const TYPE_WORK_SLIP = 'work_slip';

    public const TYPES = [
        self::TYPE_ORDER_SLIP,
        self::TYPE_DELIVERY_NOTICE,
        self::TYPE_SHIPPING_SALES,
        self::TYPE_SHIPPING_WAREHOUSE,
        self::TYPE_WORK_SLIP,
    ];

    protected $fillable = [
        'application_id',
        'form_type',
        'version',
        'is_latest',
        'file_path',
        'original_name',
        'issued_by_user_id',
        'issued_at',
    ];

    protected $casts = [
        'is_latest' => 'boolean',
        'issued_at' => 'datetime',
        'version' => 'integer',
    ];

    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    public function issuedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by_user_id');
    }

    public function label(): string
    {
        return match ($this->form_type) {
            self::TYPE_ORDER_SLIP => '受注伝票',
            self::TYPE_DELIVERY_NOTICE => '納入予定ご連絡書',
            self::TYPE_SHIPPING_SALES => '出荷指示書（営業所用）',
            self::TYPE_SHIPPING_WAREHOUSE => '出荷指示書（倉庫用）',
            self::TYPE_WORK_SLIP => '作業用伝票',
            default => $this->form_type,
        };
    }
}
