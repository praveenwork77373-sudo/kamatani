<?php

namespace App\Services;

use App\Mail\ApplicationApprovedMail;
use App\Mail\ApplicationCreatedMail;
use App\Mail\ApplicationRejectedMail;
use App\Mail\CancelApprovedMail;
use App\Mail\CancelRejectedMail;
use App\Mail\CancelRequestedMail;
use App\Mail\SpecialApprovalRequestedMail;
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
              ->whereNull('deleted_at')
              ->where(function ($nq) {
                  // Spec: configurable notify destinations; default true for ops/system
                  $nq->where('receives_app_notifications', true)
                     ->orWhereNull('receives_app_notifications');
              });
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
     * キャンセル申請通知（管理者へ in-app + email）
     */
    public static function notifyCancelRequested($application): void
    {
        $application->loadMissing('medicalInstitution', 'employee');

        $adminUsers = self::activeAdminUsers(['system_admin', 'operation']);
        $adminEmails = [];

        foreach ($adminUsers as $admin) {
            self::create(
                $admin,
                'cancel_requested',
                'キャンセル申請が送信されました',
                "申請ID: #APP-{$application->id} のキャンセル申請",
                route('admin.applications.show', $application),
                ['application_id' => $application->id]
            );
            if ($admin->email) {
                $adminEmails[] = $admin->email;
            }
        }

        $recipients = $adminEmails;
        $institutionEmail = $application->medicalInstitution?->notification_email;
        if ($institutionEmail && ! in_array($institutionEmail, $recipients, true)) {
            $recipients[] = $institutionEmail;
        }

        if (! empty($recipients)) {
            try {
                Mail::to($recipients)->send(new CancelRequestedMail($application));
            } catch (\Exception $e) {
                Log::error('メール送信エラー: '.$e->getMessage());
            }
        }
    }

    /**
     * 特別承認通知（管理者へ in-app + email）
     */
    public static function notifySpecialApprovalRequested($application): void
    {
        $application->loadMissing('medicalInstitution', 'employee');

        $adminUsers = self::activeAdminUsers(['system_admin', 'operation']);
        $adminEmails = [];

        foreach ($adminUsers as $admin) {
            self::create(
                $admin,
                'special_approval_requested',
                '特別承認が必要な申請があります',
                "申請ID: #APP-{$application->id} は上限超過のため特別承認が必要です",
                route('admin.applications.show', $application),
                ['application_id' => $application->id]
            );
            if ($admin->email) {
                $adminEmails[] = $admin->email;
            }
        }

        $recipients = $adminEmails;
        $institutionEmail = $application->medicalInstitution?->notification_email;
        if ($institutionEmail && ! in_array($institutionEmail, $recipients, true)) {
            $recipients[] = $institutionEmail;
        }

        if (! empty($recipients)) {
            try {
                Mail::to($recipients)->send(new SpecialApprovalRequestedMail($application));
            } catch (\Exception $e) {
                Log::error('メール送信エラー: '.$e->getMessage());
            }
        }
    }

    /**
     * キャンセル承認通知（申請者へ）
     */
    public static function notifyCancelApproved($application): void
    {
        $application->loadMissing('requestedBy', 'employee');

        if (! $application->requestedBy) {
            return;
        }

        self::create(
            $application->requestedBy,
            'cancel_approved',
            'キャンセル申請が承認されました',
            "申請ID: #APP-{$application->id} のキャンセルが承認されました",
            route('applications.show', $application),
            ['application_id' => $application->id]
        );

        if ($application->requestedBy->email) {
            try {
                Mail::to($application->requestedBy->email)->send(new CancelApprovedMail($application));
            } catch (\Exception $e) {
                Log::error('メール送信エラー: '.$e->getMessage());
            }
        }
    }

    /**
     * キャンセル却下通知（申請者へ）
     */
    public static function notifyCancelRejected($application): void
    {
        $application->loadMissing('requestedBy', 'employee');

        if (! $application->requestedBy) {
            return;
        }

        self::create(
            $application->requestedBy,
            'cancel_rejected',
            'キャンセル申請が却下されました',
            "申請ID: #APP-{$application->id} のキャンセルが却下されました",
            route('applications.show', $application),
            ['application_id' => $application->id]
        );

        if ($application->requestedBy->email) {
            try {
                Mail::to($application->requestedBy->email)->send(new CancelRejectedMail($application));
            } catch (\Exception $e) {
                Log::error('メール送信エラー: '.$e->getMessage());
            }
        }
    }
}
