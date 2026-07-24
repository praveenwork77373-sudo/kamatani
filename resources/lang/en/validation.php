<?php

return [
    'required' => 'The :attribute field is required.',
    'email' => 'The :attribute must be a valid email address.',
    'exists' => 'The selected :attribute is invalid.',
    'min' => [
        'string' => 'The :attribute must be at least :min characters.',
        'numeric' => 'The :attribute must be at least :min.',
    ],
    'max' => [
        'string' => 'The :attribute must not exceed :max characters.',
        'numeric' => 'The :attribute must not exceed :max.',
    ],
    'attributes' => [
        'email' => 'email',
        'password' => 'password',
        'name' => 'name',
        'employee_id' => 'employee',
        'department_id' => 'department',
        'type' => 'application type',
        'start_date' => 'start date',
        'delivery_location_id' => 'delivery location',
        'items' => 'application items',
    ],
];

