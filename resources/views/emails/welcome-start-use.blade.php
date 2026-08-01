<x-mail::message>
# ご利用開始のご案内

{{ $user->name }} 様

{{ $institution->name }} 向けユニフォーム管理システムのご利用を開始いただけます。

**ログインURL:** [{{ $loginUrl }}]({{ $loginUrl }})  
**ログインID（メール）:** {{ $user->email }}  
**仮パスワード:** {{ $temporaryPassword }}

初回ログイン後、パスワードの変更が必要です。仮パスワードは第三者に共有しないでください。

<x-mail::button :url="$loginUrl">
ログインする
</x-mail::button>

ご不明点はカマタニ担当者までお問い合わせください。

ありがとうございます。<br>
{{ config('app.name') }}
</x-mail::message>
