<x-mail::message>
# アカウント一時ロックのお知らせ

{{ $user->name }} 様

ユニフォーム管理システムへのログインに連続して失敗したため、アカウントが一時的にロックされました。

**ロック時間:** 約 {{ $lockMinutes }} 分  
**解除予定時刻:** {{ $lockedUntilFormatted }}

解除予定時刻を過ぎると、自動的にログインできるようになります。  
お心当たりがない場合は、パスワードの再設定をご検討ください。

<x-mail::button :url="route('password.request')">
パスワードを再設定する
</x-mail::button>

急ぎ解除が必要な場合は、システム管理者にお問い合わせください。

ありがとうございます。<br>
{{ config('app.name') }}
</x-mail::message>
