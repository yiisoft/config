<?php

declare(strict_types=1);

// Do not edit. Content will be replaced.
return [
    '/' => [
        'params' => [
            'package/a' => [
                'params.php',
            ],
            'package/b' => [
                'params.php',
            ],
            '/' => [
                'params/*.php',
            ],
        ],
    ],
    'environment' => [
        'params' => [
            '/' => [
                'environment/params/*.php',
            ],
        ],
    ],
];
