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
            self::STATUS_PENDING => __('pages.application_status_pending'),
            self::STATUS_WAITING_SPECIAL => __('pages.special_approval_pending'),
            self::STATUS_IN_PROGRESS => __('pages.in_progress'),
            self::STATUS_COMPLETED => __('pages.completed'),
            self::STATUS_REJECTED => __('pages.status_rejected'),
            'approved' => __('pages.in_progress'),
            self::STATUS_CANCEL_REQUEST => __('pages.cancel_request_status'),
            self::STATUS_CANCEL_APPROVED => __('pages.cancel_approved'),
            self::STATUS_CANCEL_REJECTED => __('pages.cancel_rejected'),
            default => $this->status,
        };
    }

    public function typeLabel(): string
    {
        return match ($this->type) {
            'new_loan' => __('pages.new_loan_type'),
            'resize' => __('pages.resize_type'),
            'return' => __('pages.return_type'),
            'exchange' => __('pages.exchange_type'),
            'transfer' => __('pages.transfer_type'),
            default => (string) $this->type,
        };
    }

    /** Pill tone for `.ui-status-pill--*` */
    public function statusPillTone(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING, self::STATUS_WAITING_SPECIAL, self::STATUS_CANCEL_REQUEST => 'warning',
            self::STATUS_IN_PROGRESS, 'approved' => 'brand',
            self::STATUS_COMPLETED => 'success',
            self::STATUS_REJECTED, self::STATUS_CANCEL_REJECTED => 'danger',
            self::STATUS_CANCEL_APPROVED => 'muted',
            default => 'muted',
        };
    }

    /** Tailwind classes for colored status badges on detail pages */
    public function statusBadgeClass(): string
    {
        return match ($this->status) {
            self::STATUS_WAITING_SPECIAL => 'bg-amber-100 text-amber-700',
            self::STATUS_IN_PROGRESS, 'approved' => 'bg-blue-100 text-blue-700',
            self::STATUS_COMPLETED => 'bg-emerald-100 text-emerald-700',
            self::STATUS_REJECTED, self::STATUS_CANCEL_REJECTED => 'bg-red-100 text-red-700',
            self::STATUS_CANCEL_REQUEST => 'bg-orange-100 text-orange-700',
            default => 'bg-slate-100 text-slate-700',
        };
    }

    public function typePillTone(): string
    {
        return match ($this->type) {
            'new_loan' => 'brand',
            'transfer' => 'info',
            default => 'muted',
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


