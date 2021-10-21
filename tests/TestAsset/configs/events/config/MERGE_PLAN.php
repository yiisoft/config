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
        'events' => [
            'package/a' => [
                'events.php',
            ],
            'package/b' => [
                'events.php',
            ],
            '/' => [
                'events.php',
            ],
        ],
        'events-console' => [
            'package/a' => [
                'events-console.php',
            ],
            '/' => [
                '$events',
                'events-console.php',
            ],
        ],
    ],
];
