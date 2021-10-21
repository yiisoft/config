<?php

declare(strict_types=1);

// Do not edit. Content will be replaced.
return [
    '/' => [
        'params' => [
            '/' => [
                'params.php',
            ],
        ],
        'web1' => [
            'package/a' => [
                'web1.php',
            ],
            '/' => [
                'web1.php',
            ],
        ],
        'web2' => [
            'package/a' => [
                'web2.php',
            ],
            '/' => [
                'web2.php',
            ],
        ],
        'web' => [
            '/' => [
                '$web1',
                '$web2',
            ],
        ],
    ],
];
