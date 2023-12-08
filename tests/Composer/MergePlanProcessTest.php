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
