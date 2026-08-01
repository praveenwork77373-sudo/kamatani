<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>{{ $formTitle }} — {{ $applicationNo }}</title>
    <style>
        @font-face {
            font-family: 'ipaexg';
            font-style: normal;
            font-weight: normal;
            src: url('{{ str_replace('\\', '/', $fontPath) }}') format('truetype');
        }
        * { box-sizing: border-box; }
        body {
            font-family: 'ipaexg', DejaVu Sans, sans-serif;
            font-size: 11px;
            color: #111;
            margin: 24px;
        }
        h1 {
            font-size: 18px;
            margin: 0 0 4px;
            letter-spacing: 0.08em;
        }
        .sub {
            font-size: 10px;
            color: #444;
            margin-bottom: 14px;
        }
        .meta {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 14px;
        }
        .meta th, .meta td {
            border: 1px solid #333;
            padding: 5px 7px;
            vertical-align: top;
        }
        .meta th {
            background: #f0f0f0;
            width: 18%;
            text-align: left;
            font-weight: normal;
        }
        .items {
            width: 100%;
            border-collapse: collapse;
            margin-top: 8px;
        }
        .items th, .items td {
            border: 1px solid #333;
            padding: 5px 6px;
        }
        .items th {
            background: #e8e8e8;
            font-weight: normal;
        }
        .right { text-align: right; }
        .center { text-align: center; }
        .footer {
            margin-top: 18px;
            font-size: 10px;
            color: #555;
        }
        .badge {
            display: inline-block;
            border: 1px solid #333;
            padding: 2px 8px;
            margin-left: 8px;
            font-size: 10px;
        }
    </style>
</head>
<body>
    <h1>{{ $formTitle }} <span class="badge">{{ $audienceNote }}</span></h1>
    <div class="sub">申請番号: {{ $applicationNo }}　／　種別: {{ $applicationTypeLabel }}　／　発行: {{ $issuedAt }}</div>

    <table class="meta">
        <tr>
            <th>受注日</th>
            <td>{{ $orderDate ?? '-' }}</td>
            <th>納期（開始日）</th>
            <td>{{ $dueDate ?? '-' }}</td>
        </tr>
        <tr>
            <th>医療機関</th>
            <td colspan="3">{{ $institution?->name ?? '-' }}（{{ $institution?->code ?? '-' }}）</td>
        </tr>
        <tr>
            <th>部署</th>
            <td>{{ $department?->name ?? '-' }}</td>
            <th>配送先</th>
            <td>{{ $deliveryLocation?->name ?? '-' }}</td>
        </tr>
        <tr>
            <th>個人名</th>
            <td>{{ $employee?->name ?? '-' }}</td>
            <th>職員番号</th>
            <td>{{ $employee?->staff_no ?? '-' }}</td>
        </tr>
        <tr>
            <th>股下</th>
            <td>{{ $employee?->inseam_cm !== null ? $employee->inseam_cm.' cm' : '-' }}</td>
            <th>体型</th>
            <td>{{ $employee?->body_type ?? '-' }}</td>
        </tr>
        <tr>
            <th>契約プラン</th>
            <td>{{ $planNames }}</td>
            <th>プランコード</th>
            <td>{{ $planCodes }}</td>
        </tr>
        <tr>
            <th>担当セールス</th>
            <td>{{ $salesPerson }}</td>
            <th>性別</th>
            <td>
                @if(($employee?->gender ?? '') === 'male')男性
                @elseif(($employee?->gender ?? '') === 'female')女性
                @else{{ $employee?->gender ?? '-' }}@endif
            </td>
        </tr>
        <tr>
            <th>備考1</th>
            <td colspan="3">{{ $memo1 !== '' ? $memo1 : '-' }}</td>
        </tr>
        <tr>
            <th>備考2</th>
            <td colspan="3">{{ $memo2 !== '' ? $memo2 : '-' }}</td>
        </tr>
    </table>

    <table class="items">
        <thead>
            <tr>
                <th style="width:6%">#</th>
                <th style="width:14%">メーカー</th>
                <th style="width:16%">商品コード</th>
                <th>商品名</th>
                <th style="width:10%">サイズ</th>
                <th style="width:10%">区分</th>
                <th style="width:8%">数量</th>
            </tr>
        </thead>
        <tbody>
            @forelse($items as $i => $item)
                @php $p = $item->uniformProduct; @endphp
                <tr>
                    <td class="center">{{ $i + 1 }}</td>
                    <td>{{ $p?->maker?->name ?? '-' }}</td>
                    <td>{{ $p?->product_code ?? '-' }}</td>
                    <td>{{ $p?->name ?? '-' }}</td>
                    <td class="center">{{ $p?->size ?? '-' }}</td>
                    <td class="center">{{ $item->action ?: '-' }}</td>
                    <td class="right">{{ $item->quantity }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="center">明細なし</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        本帳票はユニフォーム管理システムにより発行されました。契約プランに紐づく商品のみ申請対象です。<br>
        @if($formType === 'order_slip')
            受注伝票：準備・発注手配の基準帳票です。
        @elseif($formType === 'delivery_notice')
            納入予定ご連絡書：納入スケジュール共有用です。
        @elseif($formType === 'shipping_sales')
            出荷指示書（営業所用）：営業所での出荷確認用です。
        @elseif($formType === 'shipping_warehouse')
            出荷指示書（倉庫用）：倉庫ピッキング・出荷用です。
        @else
            作業用伝票：社内作業指示用です。
        @endif
    </div>
</body>
</html>
