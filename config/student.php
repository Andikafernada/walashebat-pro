<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Student Authentication Guards
    |--------------------------------------------------------------------------
    */
    'defaults' => [
        'guard' => 'web',
        'passwords' => 'students',
    ],

    'guards' => [
        'student' => [
            'driver' => 'session',
            'provider' => 'students',
        ],
    ],

    'providers' => [
        'students' => [
            'driver' => 'eloquent',
            'model' => App\Models\Student::class,
            'table' => 'students',
        ],
    ],

    'password_timeout' => 10800, // 3 hours

    'password' => [
        'hash' => 'bcrypt',
        'min' => 8,
    ],
];
