<?php

declare(strict_types=1);

namespace Yiisoft\Config\Tests\Integration\BrokenFile;

use ErrorException;
use Yiisoft\Config\Tests\Integration\BaseTestCase;

final class BrokenFileTest extends BaseTestCase
{
    public function testBase(): void
    {
        $config = $this->runComposerUpdateAndCreateConfig(
            rootPath: __DIR__,
            extra: [
                'config-plugin' => [
                    'params' => 'params.php',
                ],
            ],
        );

        $this->expectException(ErrorException::class);
        $this->expectExceptionMessage('test-error');
        $config->get('params');
    }
}
