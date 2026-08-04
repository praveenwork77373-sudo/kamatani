<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Application extends Model
{
    use HasFactory;

    /** 申請 */
    public const STATUS_PENDING = 'pending';
    /** 特別承認待ち */
    public const STATUS_WAITING_SPECIAL = 'waiting_special_approval';
    /** 対応中（社内のみ） */
    public const STATUS_IN_PROGRESS = 'in_progress';
    /** 完了 */
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_CANCEL_REQUEST = 'cancel_request';
    public const STATUS_CANCEL_APPROVED = 'cancel_approved';
    public const STATUS_CANCEL_REJECTED = 'cancel_rejected';

    protected $fillable = [
        'medical_institution_id',
        'department_id',
        'employee_id',
        'transfer_to_employee_id',
        'type',
        'status',
        'start_date',
        'end_date',
        'return_date',
        'delivery_location_id',
        'requested_by_user_id',
        'special_approval_reason',
        'memo1',
        'memo2',
        'rejection_reason',
        'approved_at',
        'approved_by_admin_user_id',
        'accepted_at',
        'shipped_at',
        'completed_at',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'return_date' => 'date',
        'approved_at' => 'datetime',
        'accepted_at' => 'datetime',
        'shipped_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function isCancellableByHospital(): bool
    {
        return in_array($this->status, [
            self::STATUS_PENDING,
            self::STATUS_WAITING_SPECIAL,
            self::STATUS_IN_PROGRESS,
        ], true);
    }

    public function isAcceptable(): bool
    {
        return in_array($this->status, [
            self::STATUS_PENDING,
            self::STATUS_WAITING_SPECIAL,
        ], true);
    }

    public function isCompletable(): bool
    {
        // cancel_rejected kept for legacy stuck rows created before restore-on-reject fix
        return in_array($this->status, [
            self::STATUS_IN_PROGRESS,
            self::STATUS_CANCEL_REJECTED,
        ], true);
    }

    /**
     * Status to resume after a cancel request is rejected.
     */
    public function statusAfterCancelReject(): string
    {
        if ($this->accepted_at) {
            return self::STATUS_IN_PROGRESS;
        }

        if (! empty($this->special_approval_reason)) {
            return self::STATUS_WAITING_SPECIAL;
        }

        return self::STATUS_PENDING;
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => '申請',
            self::STATUS_WAITING_SPECIAL => '特別承認待ち',
            self::STATUS_IN_PROGRESS => '対応中',
            self::STATUS_COMPLETED => '完了',
            self::STATUS_REJECTED => '却下',
            self::STATUS_CANCEL_REQUEST => 'キャンセル依頼中',
            self::STATUS_CANCEL_APPROVED => 'キャンセル承認',
            self::STATUS_CANCEL_REJECTED => 'キャンセル却下',
            default => $this->status,
        };
    }

    public function medicalInstitution(): BelongsTo
    {
        return $this->belongsTo(MedicalInstitution::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function transferToEmployee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'transfer_to_employee_id');
    }

    public function deliveryLocation(): BelongsTo
    {
        return $this->belongsTo(DeliveryLocation::class);
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by_user_id');
    }
    
    public function requester(): BelongsTo
    {
        return $this->requestedBy();
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by_admin_user_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(ApplicationItem::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(ApplicationDocument::class);
    }
}


