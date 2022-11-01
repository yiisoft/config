<?php

declare(strict_types=1);

// Do not edit. Content will be replaced.
return [
    'common' => [
        'params' => [
            '/' => [
                'params.php',
            ],
        ],
        'definitions' => [
            '/' => [
                'definitions.php',
            ],
        ],
        'definitions-backend' => [
            '/' => [
                '$definitions',
                'definitions-backend.php',
            ],
        ],
    ],
    'environment' => [
        'definitions' => [
            '/' => [
                'environment/definitions.php',
            ],
        ],
    ],
];
