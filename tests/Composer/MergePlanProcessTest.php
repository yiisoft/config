<?php

declare(strict_types=1);

namespace Yiisoft\Config\Tests\Composer;

use Yiisoft\Config\Composer\MergePlanProcess;
use Yiisoft\Config\Options;

final class MergePlanProcessTest extends TestCase
{
    public function testProcess(): void
    {
        new MergePlanProcess($this->createComposerMock());
        $this->assertMergePlan();
    }

    public function testProcessWithEnvironment(): void
    {
        new MergePlanProcess($this->createComposerMock([
            'alfa' => [
                'params' => 'alfa/params.php',
                'web' => 'alfa/web.php',
                'main' => [
                    '$web',
                    'alfa/main.php',
                ],
            ],
            'beta' => [
                'params' => 'beta/params.php',
                'web' => 'beta/web.php',
                'main' => [
                    '$web',
                    'beta/main.php',
                ],
            ],
        ]));

        $this->assertMergePlan([
            'alfa' => [
                'params' => [
                    Options::ROOT_PACKAGE_NAME => [
                        'alfa/params.php',
                    ],
                ],
                'web' => [
                    Options::ROOT_PACKAGE_NAME => [
                        'alfa/web.php',
                    ],
                ],
                'main' => [
                    Options::ROOT_PACKAGE_NAME => [
                        '$web',
                        'alfa/main.php',
                    ],
                ],
            ],
            'beta' => [
                'params' => [
                    Options::ROOT_PACKAGE_NAME => [
                        'beta/params.php',
                    ],
                ],
                'web' => [
                    Options::ROOT_PACKAGE_NAME => [
                        'beta/web.php',
                    ],
                ],
                'main' => [
                    Options::ROOT_PACKAGE_NAME => [
                        '$web',
                        'beta/main.php',
                    ],
                ],
            ],
        ]);
    }

    public function testProcessWithIgnoreAdditionalDefaultEnvironment(): void
    {
        new MergePlanProcess($this->createComposerMock([
            'alfa' => [
                'params' => 'alfa/params.php',
                'web' => 'alfa/web.php',
                'main' => [
                    '$web',
                    'alfa/main.php'
                ],
            ],
            Options::DEFAULT_ENVIRONMENT=> [
                'params' => 'params.php',
                'web' => 'web.php',
                'main' => [
                    '$web',
                    'main.php'
                ],
            ],
        ]));

        $this->assertMergePlan([
            'alfa' => [
                'params' => [
                    Options::ROOT_PACKAGE_NAME => [
                        'alfa/params.php',
                    ],
                ],
                'web' => [
                    Options::ROOT_PACKAGE_NAME => [
                        'alfa/web.php',
                    ],
                ],
                'main' => [
                    Options::ROOT_PACKAGE_NAME => [
                        '$web',
                        'alfa/main.php',
                    ],
                ],
            ],
        ]);
    }
}
