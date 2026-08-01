<x-mail::message>
# 契約プラン本登録のお知らせ

{{ $institution->name }} 様

以下の契約プランが本登録されました。職員へのプラン割当とユニフォーム申請が可能になります。

**プラン:** {{ $customerContractPlan->contractPlan?->name ?? '-' }}（{{ $customerContractPlan->contractPlan?->code ?? '-' }}）  
**契約口数:** {{ $customerContractPlan->contract_count }}  
**開始日:** {{ optional($customerContractPlan->start_date)->format('Y-m-d') ?? '-' }}  
**終了日:** {{ optional($customerContractPlan->end_date)->format('Y-m-d') ?? '-' }}

<x-mail::button :url="route('login')">
病院ログイン
</x-mail::button>

ありがとうございます。<br>
{{ config('app.name') }}
</x-mail::message>
