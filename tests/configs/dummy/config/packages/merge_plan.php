<?php

declare(strict_types=1);

// Do not edit. Content will be replaced.
return [
    '/' => [
        'events' => [
            '/' => [
                'config/events.php',
            ],
        ],
        'failVariableGroupEqual' => [
            '/' => [
                '$failVariableGroupEqual',
            ],
        ],
        'failVariableGroupNotExist' => [
            '/' => [
                '$failVariableNotExist',
            ],
        ],
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
    ],
    'alfa' => [
        'failVariableGroupEqual' => [
            '/' => [
                '$failVariableGroupEqual',
            ],
        ],
        'failVariableGroupNotExist' => [
            '/' => [
                '$failVariableNotExist',
            ],
        ],
        'main' => [
            '/' => [
                '$web',
                'config/alfa/main.php',
            ],
        ],
        'params' => [
            '/' => [
                'config/alfa/params.php',
            ],
        ],
        'web' => [
            '/' => [
                'config/alfa/web.php',
                'config/alfa/web2.php',
            ],
        ],
    ],
    'beta' => [
        'events' => [
            '/' => [
                '$common',
                'config/beta/events.php',
            ],
        ],
        'web' => [
            '/' => [
                'config/beta/web.php',
            ],
        ],
        'params' => [
            '/' => [
                'config/beta/params.php',
            ],
        ],
    ],
];
