<?php

declare(strict_types=1);

namespace Yiisoft\Config\Tests\Composer;

use Yiisoft\Config\Composer\MergePlanProcess;
use Yiisoft\Config\Options;
use Yiisoft\VarDumper\VarDumper;

use function file_put_contents;

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
            'empty' => [],
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
            'empty' => [],
        ]);
    }

    public function testProcessWithSpecifyPhpConfigurationFile(): void
    {
        $configuration = [
            'config-plugin-options' => [
                'source-directory' => 'config',
                'vendor-override-layer' => 'test/over',
            ],
            'config-plugin' => [
                'empty' => [],
                'common' => 'common/*.php',
                'params' => [
                    'params.php',
                    '?params-local.php',
                ],
                'web' => [
                    '$common',
                    'web.php',
                ],
            ],
            'config-plugin-environments' => [
                'environment' => [
                    'main' => [
                        '$web',
                        'main.php',
                    ],
                ],
            ],
        ];

        file_put_contents(
            $this->getTempPath('configuration-file.php'),
            "<?php\n\nreturn " . VarDumper::create($configuration)->export(true) . ";\n",
        );

        new MergePlanProcess($this->createComposerMock([], null, true, 'config/configuration-file.php'));

        $this->assertMergePlan(
            [
                'environment' => [
                    'main' => [
                        Options::ROOT_PACKAGE_NAME => [
                            '$web',
                            'main.php',
                        ],
                    ],
                ],
            ],
        );
    }
}
