<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>申請が登録されました</title>
</head>
<body style="font-family: 'Noto Sans JP', sans-serif; line-height: 1.6; color: #333;">
    <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
        <h2 style="color: #0284c7;">新しい申請が登録されました</h2>
        <p>申請ID: #APP-{{ str_pad($application->id, 4, '0', STR_PAD_LEFT) }}</p>
        <p>職員: {{ $application->employee?->name ?? '-' }} ({{ $application->employee?->staff_no ?? '-' }})</p>
        <p>申請種別: {{ $application->type }}</p>
        <p>状態: {{ $application->status === 'waiting_special_approval' ? '特別承認待ち' : '未処理' }}</p>
        <p style="margin-top: 20px;">
            <a href="{{ route('admin.applications.show', $application) }}" style="display: inline-block; padding: 10px 20px; background-color: #0284c7; color: white; text-decoration: none; border-radius: 5px;">
                申請詳細を確認
            </a>
        </p>
    </div>
</body>
</html>





