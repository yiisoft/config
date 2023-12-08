<?php

declare(strict_types=1);

namespace Yiisoft\Config\Tests\Integration\CopyCommand;

use Yiisoft\Config\Tests\Integration\IntegrationTestCase;

final class CopyCommandTest extends IntegrationTestCase
{
    public static function dataBase(): array
    {
        return [
            'all-files' => [
                [
                    __DIR__ . '/config/test-custom-source-common-a.php' => __DIR__ . '/packages/custom-source/custom-dir/common/a.php',
                    __DIR__ . '/config/test-custom-source-common-b.php' => __DIR__ . '/packages/custom-source/custom-dir/common/b.php',
                    __DIR__ . '/config/test-custom-source-events.php' => __DIR__ . '/packages/custom-source/custom-dir/events.php',
                    __DIR__ . '/config/test-custom-source-events-web.php' => __DIR__ . '/packages/custom-source/custom-dir/events-web.php',
                    __DIR__ . '/config/test-custom-source-params.php' => __DIR__ . '/packages/custom-source/custom-dir/params.php',
                    __DIR__ . '/config/test-custom-source-web.php' => __DIR__ . '/packages/custom-source/custom-dir/web.php',
                ],
                ['package' => 'test/custom-source'],
            ],
            'one-file' => [
                [
                    __DIR__ . '/config/test-custom-source-common-a.php' => null,
                    __DIR__ . '/config/test-custom-source-common-b.php' => null,
                    __DIR__ . '/config/test-custom-source-events.php' => null,
                    __DIR__ . '/config/test-custom-source-events-web.php' => __DIR__ . '/packages/custom-source/custom-dir/events-web.php',
                    __DIR__ . '/config/test-custom-source-params.php' => null,
                    __DIR__ . '/config/test-custom-source-web.php' => null,
                ],
                ['package' => 'test/custom-source', 'files' => ['events-web']],
            ],
            'several-files' => [
                [
                    __DIR__ . '/config/test-custom-source-common-a.php' => __DIR__ . '/packages/custom-source/custom-dir/common/a.php',
                    __DIR__ . '/config/test-custom-source-common-b.php' => null,
                    __DIR__ . '/config/test-custom-source-events.php' => __DIR__ . '/packages/custom-source/custom-dir/events.php',
                    __DIR__ . '/config/test-custom-source-events-web.php' => null,
                    __DIR__ . '/config/test-custom-source-params.php' => __DIR__ . '/packages/custom-source/custom-dir/params.php',
                    __DIR__ . '/config/test-custom-source-web.php' => null,
                ],
                ['package' => 'test/custom-source', 'files' => ['params.php', 'events', 'common/a']],
            ],
            'default-source' => [
                [
                    __DIR__ . '/config/test-a-params.php' => __DIR__ . '/packages/a/params.php',
                    __DIR__ . '/config/test-a-web.php' =>  __DIR__ . '/packages/a/web.php',
                ],
                ['package' => 'test/a'],
            ]
        ];
    }

    /**
     * @dataProvider dataBase
     */
    public function testBase(array $expectedFiles, array $arguments): void
    {
        $this->removeDirectories[] = __DIR__ . '/config';

        $this->runComposerYiiConfigCopy(
            rootPath: __DIR__,
            arguments: $arguments,
            packages: [
                'test/custom-source' => __DIR__ . '/packages/custom-source',
                'test/a' => __DIR__ . '/packages/a',
            ],
            extra: [
                'config-plugin-options' => [
                    'source-directory' => 'config',
                ],
            ],
            configDirectory: 'config',
        );

        foreach ($expectedFiles as $source => $target) {
            if ($target === null) {
                $this->assertFileDoesNotExist($source);
            } else {
                $this->assertFileExists($source);
                $this->assertFileExists($target);
                $this->assertFileEquals($source, $target);
            }
        }
    }
}
