<?php

return [
    'required' => ':attribute は必須です。',
    'email' => ':attribute は有効なメールアドレスである必要があります。',
    'exists' => '選択された :attribute は無効です。',
    'min' => [
        'string' => ':attribute は :min 文字以上である必要があります。',
        'numeric' => ':attribute は :min 以上である必要があります。',
    ],
    'max' => [
        'string' => ':attribute は :max 文字以下である必要があります。',
        'numeric' => ':attribute は :max 以下である必要があります。',
    ],
    'attributes' => [
        'email' => 'メールアドレス',
        'password' => 'パスワード',
        'name' => '名前',
        'employee_id' => '職員',
        'department_id' => '部署',
        'type' => '申請種別',
        'start_date' => '開始日',
        'delivery_location_id' => '納品場所',
        'items' => '申請アイテム',
    ],
];

