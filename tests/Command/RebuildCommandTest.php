<?php

declare(strict_types=1);

namespace Yiisoft\Config\Tests\Command;

use Symfony\Component\Console\Tester\CommandTester;
use Yiisoft\Config\Command\RebuildCommand;
use Yiisoft\Config\Options;
use Yiisoft\Config\Tests\Composer\TestCase;

final class RebuildCommandTest extends TestCase
{
    public function testRebuildWithoutMergePlanChanges(): void
    {
        $this->executeCommand();
        $this->assertMergePlan();
    }

    public function testRebuildWithMergePlanChanges(): void
    {
        $this->executeCommand([
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
        ]);

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

    private function executeCommand(array $extraEnvironments = []): void
    {
        $command = new RebuildCommand();
        $command->setComposer($this->createComposerMock($extraEnvironments));
        $command->setIO($this->createIoMock());
        (new CommandTester($command))->execute([]);
    }
}
