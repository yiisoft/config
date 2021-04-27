<?php

declare(strict_types=1);

namespace Yiisoft\Config\Tests\Integration;

use Symfony\Component\Console\Helper\Helper;
use Symfony\Component\Console\Output\ConsoleOutput;

final class MessagesTest extends ComposerTest
{
    public function testAddConfigsOnInstall(): void
    {
        $this->initComposer([
            'require' => [
                'yiisoft/config' => '*',
                'test/a' => '*',
            ],
        ]);

        $this->assertMessage(
            "\n= Yii Config =\n" .
            "\n" . 'Config files has been added:' . "\n" .
            ' - config/packages/test/a/config/params.php' . "\n" .
            ' - config/packages/test/a/config/web.php' . "\n"
        );
    }

    public function testAddConfigsOnUpdate(): void
    {
        $this->initComposer([
            'require' => [
                'yiisoft/config' => '*',
                'test/k' => '*',
            ],
        ]);

        $this->changeTestPackageDir('k', 'k-2-add-web');
        $this->execComposer('require test/k');

        $this->assertMessage(
            "\n= Yii Config =\n" .
            "\n" . 'Config files has been added:' . "\n" .
            ' - config/packages/test/k/config/web.php' . "\n"
        );
    }

    public function testUpdateConfigsOnUpdate(): void
    {
        $this->initComposer([
            'require' => [
                'yiisoft/config' => '*',
                'test/a' => '*',
            ],
        ]);

        $this->changeTestPackageDir('a', 'a-2-update-params-and-web');
        $this->execComposer('require test/a');

        $this->assertMessage(
            "\n= Yii Config =\n" .
            "\n" . 'Changes in the config files were ignored:' . "\n" .
            ' - config/packages/test/a/config/params.php' . "\n" .
            ' - config/packages/test/a/config/web.php' . "\n" .
            'Please review the files above and change them yourself if necessary.' . "\n"
        );
    }

    public function testUpdateAndAddConfigsOnUpdate(): void
    {
        $this->initComposer([
            'require' => [
                'yiisoft/config' => '*',
                'test/k' => '*',
            ],
        ]);

        $this->changeTestPackageDir('k', 'k-2-add-common-update-params');
        $this->execComposer('require test/k');

        $this->assertMessage(
            "\n= Yii Config =\n" .
            "\n" . 'Config files has been added:' . "\n" .
            ' - config/packages/test/k/config/common.php' . "\n" .
            "\n" . 'Changes in the config files were ignored:' . "\n" .
            ' - config/packages/test/k/config/params.php' . "\n" .
            "Please review the files above and change them yourself if necessary." . "\n"
        );
    }

    private function assertMessage(string $message): void
    {
        $stdout = strtr(
            Helper::removeDecoration((new ConsoleOutput())->getFormatter(), $this->getStdout()),
            [
                "\r\n" => "\n",
                "\r" => "\n",
            ]
        );

        $this->assertSame($message, $stdout);
    }
}
