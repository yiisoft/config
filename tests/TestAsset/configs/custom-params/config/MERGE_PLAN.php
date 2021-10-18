<?php

declare(strict_types=1);

// Do not edit. Content will be replaced.
return [
    '/' => [
        'custom-params' => [
            '/' => [
                '$params',
                'my-params.php',
            ],
        ],
        'params' => [
            'package/a' => [
                'params.php',
            ],
            'package/b' => [
                'params.php',
            ],
            '/' => [
                'params.php',
            ],
        ],
        'web' => [
            'package/a' => [
                'web.php',
            ],
            'package/b' => [
                'web.php',
            ],
            '/' => [
                'web.php',
            ],
        ],
    ],
];
