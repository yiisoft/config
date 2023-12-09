<?php

declare(strict_types=1);

namespace Yiisoft\Config\Tests\Integration\ProcessWithoutMergePlanBuild;

use Yiisoft\Config\Options;
use Yiisoft\Config\Tests\Integration\IntegrationTestCase;

final class ProcessWithoutMergePlanBuildTest extends IntegrationTestCase
{
    public function testBase(): void
    {
        $this->runComposerUpdate(
            rootPath: __DIR__,
            extra: [
                'config-plugin' => [
                    'params' => [],
                ],
                'config-plugin-options' => [
                    'build-merge-plan' => false,
                ],
            ],
        );

        $this->assertFileDoesNotExist(__DIR__ . '/' . Options::DEFAULT_MERGE_PLAN_FILE);
    }
}
