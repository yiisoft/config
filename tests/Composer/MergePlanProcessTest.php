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

    public function testProcessWithoutMergePlanBuild(): void
    {
        new MergePlanProcess($this->createComposerMock(['alfa' => ['params' => 'alfa/params.php']], false));
        $this->assertFileDoesNotExist($this->getTempPath(Options::MERGE_PLAN_FILENAME));
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

    public function testProcessWithIgnoreAdditionalDefaultEnvironment(): void
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
            Options::DEFAULT_ENVIRONMENT => [
                'params' => 'params.php',
                'web' => 'web.php',
                'main' => [
                    '$web',
                    'main.php',
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

    public function testProcessWithSpecifyConfigurationFiles(): void
    {
        $generateContent = static function (array $configuration): string {
            return "<?php\n\nreturn " . VarDumper::create($configuration)->export(true) . ";\n";
        };

        file_put_contents($this->getTempPath('root.php'), $generateContent([
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
        ]));

        file_put_contents($this->getTempPath('env.php'), $generateContent([
            'env' => [
                'main' => [
                    '$web',
                    'main.php',
                ],
            ],
        ]));

        new MergePlanProcess($this->createComposerMock([], true, 'root.php', 'env.php'));

        $this->assertMergePlan(
            [
                'env' => [
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
