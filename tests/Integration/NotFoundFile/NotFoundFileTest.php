<?php

declare(strict_types=1);

namespace Yiisoft\Config\Tests\Integration\NotFoundFile;

use ErrorException;
use Yiisoft\Config\Tests\Integration\IntegrationTestCase;

final class NotFoundFileTest extends IntegrationTestCase
{
    public function testBase(): void
    {
        $config = $this->runComposerUpdateAndCreateConfig(
            rootPath: __DIR__,
            extra: [
                'config-plugin' => [
                    'params' => [
                        'params.php',
                        'params2.php',
                    ],
                ],
            ],
        );

        $this->expectException(ErrorException::class);
        $this->expectExceptionMessageMatches('~^The ".*/params2\.php" file does not found\.$~');
        $config->get('params');
    }
}
