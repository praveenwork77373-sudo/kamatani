<?php

namespace App\Services;

use App\Mail\ApplicationApprovedMail;
use App\Mail\ApplicationCreatedMail;
use App\Mail\ApplicationRejectedMail;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class NotificationService
{
    /**
     * 通知を作成
     */
    public static function create(User $user, string $type, string $title, string $message, ?string $link = null, ?array $data = null): Notification
    {
        $notification = Notification::create([
            'user_id' => $user->id,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'link' => $link,
            'data' => $data,
            'is_read' => false,
        ]);

        // Socket通知を送信（後で実装）
        // broadcast(new NotificationCreated($notification))->toOthers();

        return $notification;
    }

    /**
     * 申請作成通知
     */
    public static function notifyApplicationCreated($application): void
    {
        // 管理者に通知
        $adminUsers = User::whereHas('adminUser', function($q) {
            $q->whereIn('role', ['system_admin', 'operation']);
        })->get();

        $adminEmails = [];
        foreach ($adminUsers as $admin) {
            self::create(
                $admin,
                'application_created',
                '新しい申請が登録されました',
                "申請ID: #APP-{$application->id} ({$application->employee?->name})",
                route('admin.applications.show', $application),
                ['application_id' => $application->id]
            );
            if ($admin->email) {
                $adminEmails[] = $admin->email;
            }
        }

        // メール通知
        if (!empty($adminEmails) && $application->medicalInstitution?->notification_email) {
            try {
                Mail::to($adminEmails)->send(new ApplicationCreatedMail($application));
            } catch (\Exception $e) {
                Log::error('メール送信エラー: ' . $e->getMessage());
            }
        }
    }

    /**
     * 申請承認通知
     */
    public static function notifyApplicationApproved($application): void
    {
        if ($application->requestedBy) {
            self::create(
                $application->requestedBy,
                'application_approved',
                '申請が承認されました',
                "申請ID: #APP-{$application->id} が承認されました",
                route('applications.show', $application),
                ['application_id' => $application->id]
            );

            // メール通知
            if ($application->requestedBy->email) {
                try {
                    Mail::to($application->requestedBy->email)->send(new ApplicationApprovedMail($application));
                } catch (\Exception $e) {
                    Log::error('メール送信エラー: ' . $e->getMessage());
                }
            }
        }
    }

    /**
     * 申請却下通知
     */
    public static function notifyApplicationRejected($application): void
    {
        if ($application->requestedBy) {
            self::create(
                $application->requestedBy,
                'application_rejected',
                '申請が却下されました',
                "申請ID: #APP-{$application->id} が却下されました",
                route('applications.show', $application),
                ['application_id' => $application->id]
            );

            // メール通知
            if ($application->requestedBy->email) {
                try {
                    Mail::to($application->requestedBy->email)->send(new ApplicationRejectedMail($application));
                } catch (\Exception $e) {
                    Log::error('メール送信エラー: ' . $e->getMessage());
                }
            }
        }
    }

    /**
     * キャンセル申請通知
     */
    public static function notifyCancelRequested($application): void
    {
        $adminUsers = User::whereHas('adminUser', function($q) {
            $q->whereIn('role', ['system_admin', 'operation']);
        })->get();

        foreach ($adminUsers as $admin) {
            self::create(
                $admin,
                'cancel_requested',
                'キャンセル申請が送信されました',
                "申請ID: #APP-{$application->id} のキャンセル申請",
                route('admin.applications.show', $application),
                ['application_id' => $application->id]
            );
        }
    }

    /**
     * 特別承認通知
     */
    public static function notifySpecialApprovalRequested($application): void
    {
        $adminUsers = User::whereHas('adminUser', function($q) {
            $q->whereIn('role', ['system_admin', 'operation']);
        })->get();

        foreach ($adminUsers as $admin) {
            self::create(
                $admin,
                'special_approval_requested',
                '特別承認が必要な申請があります',
                "申請ID: #APP-{$application->id} は上限超過のため特別承認が必要です",
                route('admin.applications.show', $application),
                ['application_id' => $application->id]
            );
        }
    }
}

