<?php

declare(strict_types=1);

namespace Yiisoft\Config\Tests\Command;

use Composer\Console\Application;
use Symfony\Component\Console\Helper\DebugFormatterHelper;
use Symfony\Component\Console\Helper\FormatterHelper;
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Helper\ProcessHelper;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputDefinition;
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
        $application = $this->createMock(Application::class);
        $inputDefinition = $this->createMock(InputDefinition::class);
        $inputDefinition->method('getOptions')->willReturn([]);
        $inputDefinition->method('getArguments')->willReturn([]);
        $application->method('getDefinition')->willReturn($inputDefinition);
        $application->method('getHelperSet')->willReturn(
            new HelperSet(
                [
                    new FormatterHelper(),
                    new DebugFormatterHelper(),
                    new ProcessHelper(),
                    new QuestionHelper(),
                ]
            )
        );
        $command->setApplication($application);
        (new CommandTester($command))->execute([]);
    }
}
