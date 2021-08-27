<?php

declare(strict_types=1);

namespace Yiisoft\Config\Tests\Unit;

use Yiisoft\Config\ComposerConfigProcess;
use Yiisoft\Config\Options;

use function dirname;

final class ComposerConfigProcessTest extends TestCase
{
    public function forceCheckDataProvider(): array
    {
        return [
            'null' => [null],
            'true' => [true],
            'false' => [false],
        ];
    }

    /**
     * @dataProvider forceCheckDataProvider
     *
     * @param bool|null $forceCheck
     */
    public function testProcessWithoutPackagesForCheck(?bool $forceCheck): void
    {
        $process = new ComposerConfigProcess($this->createComposerMock(), [], $forceCheck);

        $this->assertProcessData($process, $forceCheck);
    }

    /**
     * @dataProvider forceCheckDataProvider
     *
     * @param bool|null $forceCheck
     */
    public function testProcessWithPackagesForCheckAndWithoutPresenceControlledPackage(?bool $forceCheck): void
    {
        $process = new ComposerConfigProcess(
            $this->createComposerMock(),
            [
                'test/a',
                'test/ba',
                'test/c',
                'test/d-dev-c',
            ],
            $forceCheck,
        );

        $this->assertProcessData($process, $forceCheck);
    }

    /**
     * @dataProvider forceCheckDataProvider
     *
     * @param bool|null $forceCheck
     */
    public function testProcessWithPackagesForCheckAndWithPresenceControlledPackage(?bool $forceCheck): void
    {
        $process = new ComposerConfigProcess(
            $this->createComposerMock(),
            [
                'test/a',
                'test/ba',
                'test/c',
                'test/custom-source',
                'test/d-dev-c',
            ],
            $forceCheck,
        );

        $this->assertProcessData($process, true);
    }

    /**
     * @dataProvider forceCheckDataProvider
     *
     * @param bool|null $forceCheck
     */
    public function testProcessWithoutPackagesForCheckAndWithEnvironment(?bool $forceCheck): void
    {
        $composer = $this->createComposerMock([
            'config-plugin-options' => [
                'source-directory' => 'custom-dir',
            ],
            'config-plugin' => [
                'common' => 'subdir/*.php',
                'not-exists' => '?not-exists/*.php',
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
                'alfa' => [
                    'params' => 'alfa/params.php',
                    'web' => 'alfa/web.php',
                    'main' => [
                        '$web',
                        'alfa/main.php'
                    ],
                ],
                Options::ROOT_PACKAGE_NAME => [
                    'params' => 'alfa/params.php',
                    'web' => 'alfa/web.php',
                    'main' => [
                        '$web',
                        'alfa/main.php'
                    ],
                ],
            ],
        ]);

        $process = new ComposerConfigProcess($composer, [], $forceCheck);

        $this->assertProcessData($process, $forceCheck, [
            Options::DEFAULT_ENVIRONMENT => [
                'common' => [
                    '/' => [
                        'custom-dir/subdir/*.php',
                    ],
                    'test/custom-source' => [
                        'subdir/*.php',
                    ],
                ],
                'not-exists' => [
                    '/' => [
                        '?custom-dir/not-exists/*.php',
                    ],
                ],
                'params' => [
                    '/' => [
                        'custom-dir/params.php',
                        '?custom-dir/params-local.php',
                    ],
                    'test/custom-source' => [
                        'params.php',
                    ],
                ],
                'web' => [
                    '/' => [
                        '$common',
                        'custom-dir/web.php',
                    ],
                    'test/custom-source' => [
                        '$common',
                        'web.php',
                    ],
                ],
            ],
            'alfa' => [
                'main' => [
                    Options::ROOT_PACKAGE_NAME => [
                        '$web',
                        'custom-dir/alfa/main.php',
                    ],
                ],
                'params' => [
                    Options::ROOT_PACKAGE_NAME => [
                        'custom-dir/alfa/params.php',
                    ],
                ],
                'web' => [
                    Options::ROOT_PACKAGE_NAME => [
                        'custom-dir/alfa/web.php',
                    ],
                ],
            ],
        ]);
    }

    private function assertProcessData(
        ComposerConfigProcess $process,
        ?bool $withAssertConfigFiles,
        array $expectedMergePlan = null
    ): void {
        if ($withAssertConfigFiles === false) {
            $this->assertSame([], $process->configFiles());
        } else {
            $expectedSourceFilePath = dirname(__DIR__, 2) . '/tests/Packages/custom-source/custom-dir';
            $expectedDestinationFile = 'test/custom-source';

            $this->assertCount(3, $process->configFiles());

            $this->assertSame("$expectedSourceFilePath/subdir/a.php", $process->configFiles()[0]->sourceFilePath());
            $this->assertSame("$expectedDestinationFile/subdir/a.php", $process->configFiles()[0]->destinationFile());
            $this->assertFalse($process->configFiles()[0]->silentOverride());

            $this->assertSame("$expectedSourceFilePath/params.php", $process->configFiles()[1]->sourceFilePath());
            $this->assertSame("$expectedDestinationFile/params.php", $process->configFiles()[1]->destinationFile());
            $this->assertFalse($process->configFiles()[1]->silentOverride());

            $this->assertSame("$expectedSourceFilePath/web.php", $process->configFiles()[2]->sourceFilePath());
            $this->assertSame("$expectedDestinationFile/web.php", $process->configFiles()[2]->destinationFile());
            $this->assertFalse($process->configFiles()[2]->silentOverride());
        }

        $this->assertSame(Options::DEFAULT_CONFIGS_DIRECTORY, $process->configsDirectory());
        $this->assertSame($process->mergePlan(), $expectedMergePlan ?? [
            Options::DEFAULT_ENVIRONMENT => [
                'common' => [
                    '/' => [
                        'custom-dir/subdir/*.php',
                    ],
                    'test/custom-source' => [
                        'subdir/*.php',
                    ],
                ],
                'not-exists' => [
                    '/' => [
                        '?custom-dir/not-exists/*.php',
                    ],
                ],
                'params' => [
                    '/' => [
                        'custom-dir/params.php',
                        '?custom-dir/params-local.php',
                    ],
                    'test/custom-source' => [
                        'params.php',
                    ],
                ],
                'web' => [
                    '/' => [
                        '$common',
                        'custom-dir/web.php',
                    ],
                    'test/custom-source' => [
                        '$common',
                        'web.php',
                    ],
                ],
            ],
        ]);
    }
}
