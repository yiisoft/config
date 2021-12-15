<?php

declare(strict_types=1);

// Do not edit. Content will be replaced.
return [
    '/' => [
        'empty' => [],
        'emptyVariable' => [
            '/' => [
                '$empty',
            ],
        ],
        'events' => [
            '/' => [
                'events.php',
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
            'package/a' => [
                'common.php',
            ],
            'package/b' => [
                'common.php',
            ],
            '//' => [
                'package/c/common.php',
            ],
            '/' => [
                'common/*.php',
                'common/*/*.php',
            ],
        ],
        'params' => [
            'package/a' => [
                'params.php',
            ],
            'package/b' => [
                'params.php',
            ],
            '//' => [
                'package/c/params.php',
            ],
            '/' => [
                'params.php',
                '?params-local.php',
            ],
        ],
        'web' => [
            'package/a' => [
                'web.php',
            ],
            'package/b' => [
                'web.php',
            ],
            '//' => [
                'package/c/web.php',
            ],
            '/' => [
                '$common',
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
                'alfa/main.php',
            ],
        ],
        'params' => [
            '/' => [
                'alfa/params.php',
            ],
        ],
        'web' => [
            '/' => [
                'alfa/web.php',
                'alfa/web2.php',
            ],
        ],
    ],
    'beta' => [
        'events' => [
            '/' => [
                '$common',
                'beta/events.php',
            ],
        ],
        'web' => [
            '/' => [
                'beta/web.php',
            ],
        ],
        'params' => [
            '/' => [
                'beta/params.php',
            ],
        ],
    ],
    'empty' => [],
];
