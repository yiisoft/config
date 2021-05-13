<?php

declare(strict_types=1);

// Do not edit. Content will be replaced.
return [
    'common' => [
        '/' => [
            'config/common/*.php',
        ],
        'test/a' => [
            'common.php',
        ],
        'test/b' => [
            'common.php',
        ],
    ],
    'params' => [
        '/' => [
            'config/params.php',
            '?config/params-local.php',
        ],
        'test/a' => [
            'params.php',
        ],
        'test/b' => [
            'params.php',
        ],
    ],
    'web' => [
        '/' => [
            '$common',
            'config/web.php',
        ],
        'test/a' => [
            'web.php',
        ],
        'test/b' => [
            'web.php',
        ],
    ],
];
