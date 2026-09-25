<?php

declare(strict_types=1);

return [
    'providers' => [
        'users' => [
            'driver' => 'eloquent',
            'model' => \Spine\Models\User::class,
        ],
    ],
    'passwords' => [
        'users' => [
            'provider' => 'users',
            'table' => 'password_reset_tokens',
            'expire' => 60,
        ],
    ],
];
