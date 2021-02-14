<?php

declare(strict_types=1);

return [
    'first-vendor/first-package' => true,
    'constant_from_vendor' => TEST_CONSTANT_FROM_VENDOR,
    'array parameter' => [
        'changed value' => 'first-vendor/first-package',
        'first-vendor/first-package' => true,
    ],
    'array parameter with UnsetArrayValue' => [
        'first-vendor/first-package' => true,
    ],
    'array parameter with ReplaceArrayValue' => [
        'first-vendor/first-package' => true,
    ],
    'array parameter with RemoveArrayKeys' => [
        'first-vendor/first-package' => 'first-vendor/first-package',
    ],
    'array parameter with ReverseValues' => [
        'first-vendor/first-package' => 'first-vendor/first-package',
    ],
];
