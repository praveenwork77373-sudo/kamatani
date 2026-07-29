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
     * アクティブな管理者ユーザーを取得（ブロック・削除済み除外）
     */
    private static function activeAdminUsers(array $roles)
    {
        return User::whereHas('adminUser', function ($q) use ($roles) {
            $q->whereIn('role', $roles)
              ->whereNull('blocked_at')
              ->whereNull('deleted_at');
        })->get();
    }

    /**
     * 通知を作成
     */
    public static function create(User $user, string $type, string $title, string $message, ?string $link = null, ?array $data = null): Notification
    {
        return Notification::create([
            'user_id' => $user->id,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'link' => $link,
            'data' => $data,
            'is_read' => false,
        ]);
    }

    /**
     * 申請作成通知
     */
    public static function notifyApplicationCreated($application): void
    {
        $application->loadMissing('medicalInstitution', 'employee');

        $adminUsers = self::activeAdminUsers(['system_admin', 'operation']);

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

        $recipients = $adminEmails;
        $institutionEmail = $application->medicalInstitution?->notification_email;
        if ($institutionEmail && !in_array($institutionEmail, $recipients, true)) {
            $recipients[] = $institutionEmail;
        }

        if (!empty($recipients)) {
            try {
                Mail::to($recipients)->send(new ApplicationCreatedMail($application));
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
        $application->loadMissing('requestedBy');

        if ($application->requestedBy) {
            self::create(
                $application->requestedBy,
                'application_approved',
                '申請が承認されました',
                "申請ID: #APP-{$application->id} が承認されました",
                route('applications.show', $application),
                ['application_id' => $application->id]
            );

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
        $application->loadMissing('requestedBy');

        if ($application->requestedBy) {
            self::create(
                $application->requestedBy,
                'application_rejected',
                '申請が却下されました',
                "申請ID: #APP-{$application->id} が却下されました",
                route('applications.show', $application),
                ['application_id' => $application->id]
            );

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
        $adminUsers = self::activeAdminUsers(['system_admin', 'operation']);

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
        $adminUsers = self::activeAdminUsers(['system_admin', 'operation']);

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
