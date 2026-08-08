<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApplicationDocument extends Model
{
    public const TYPE_ORDER_SLIP = 'order_slip';
    public const TYPE_DELIVERY_NOTICE = 'delivery_notice';
    public const TYPE_SHIPPING_INSTRUCTION = 'shipping_instruction';
    public const TYPE_WORK_SLIP = 'work_slip';

    /** @deprecated Kept so historical documents still label correctly */
    public const TYPE_SHIPPING_SALES = 'shipping_sales';
    /** @deprecated Kept so historical documents still label correctly */
    public const TYPE_SHIPPING_WAREHOUSE = 'shipping_warehouse';

    /** Client msg 7/31: 計4種類 — 受注伝票 / 納入予定 / 出荷指示 / 作業用 */
    public const TYPES = [
        self::TYPE_ORDER_SLIP,
        self::TYPE_DELIVERY_NOTICE,
        self::TYPE_SHIPPING_INSTRUCTION,
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
            self::TYPE_ORDER_SLIP => __('pages.doc_order_slip'),
            self::TYPE_DELIVERY_NOTICE => __('pages.doc_delivery_notice'),
            self::TYPE_SHIPPING_INSTRUCTION => __('pages.doc_shipping_instruction'),
            self::TYPE_SHIPPING_SALES => __('pages.doc_shipping_sales'),
            self::TYPE_SHIPPING_WAREHOUSE => __('pages.doc_shipping_warehouse'),
            self::TYPE_WORK_SLIP => __('pages.doc_work_slip'),
            default => $this->form_type,
        };
    }
}
