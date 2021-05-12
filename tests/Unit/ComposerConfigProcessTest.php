<?php

declare(strict_types=1);

namespace Yiisoft\Config\Tests\Unit;

use Yiisoft\Config\ComposerConfigProcess;
use Yiisoft\Config\Options;

use function dirname;
use function strtr;

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

    private function assertProcessData(ComposerConfigProcess $process, ?bool $withAssertConfigFiles): void
    {
        if ($withAssertConfigFiles === false) {
            $this->assertSame([], $process->configFiles());
        } else {
            $expectedSourceFilePath = dirname(__DIR__, 2) . '/tests/Packages/custom-source/custom-dir';
            $expectedDestinationFile = 'test/custom-source/custom-dir';

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
        $this->assertSame(strtr($this->getWorkingDirectory(), '\\', '/'),  strtr($process->rootPath(), '\\', '/'));
        $this->assertSame($process->mergePlan(), [
            'common' => [
                '/' => [
                    'custom-dir/subdir/*.php',
                ],
                'test/custom-source' => [
                    'custom-dir/subdir/*.php',
                ],
            ],
            'params' => [
                '/' => [
                    'custom-dir/params.php',
                    '?custom-dir/params-local.php'
                ],
                'test/custom-source' => [
                    'custom-dir/params.php',
                ],
            ],
            'web' => [
                '/' => [
                    '$common',
                    'custom-dir/web.php'
                ],
                'test/custom-source' => [
                    '$common',
                    'custom-dir/web.php'
                ],
            ],
        ]);
    }
}
