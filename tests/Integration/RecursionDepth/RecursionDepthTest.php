<?php

declare(strict_types=1);

namespace Yiisoft\Config\Tests\Integration\RecursionDepth;

use PHPUnit\Framework\Attributes\DataProvider;
use Yiisoft\Config\Modifier\RecursiveMerge;
use Yiisoft\Config\Modifier\ReverseMerge;
use Yiisoft\Config\Tests\Integration\IntegrationTestCase;

final class RecursionDepthTest extends IntegrationTestCase
{
    public static function dataBase(): array
    {
        return [
            'unlimited' => [
                [
                    'top1' => [
                        'nestedA' => [
                            'nestedA-3' => 3,
                            'nestedA-4' => 4,
                            'nestedZ' => [3, 4, 1, 2],
                            'nestedA-1' => 1,
                            'nestedA-2' => 2,
                            'only-app' => 42,
                        ],
                        'nestedB' => [
                            'nestedB-3' => 3,
                            'nestedB-4' => 4,
                        ],
                    ],
                    'top2' => [
                        'B' => 2,
                        'A' => 1,
                    ],
                    'top3' => ['Z' => 3],
                    'top0' => ['X' => 7],
                ],
                null,
            ],
            'level0' => [
                [
                    'top1' => [
                        'nestedA' => [
                            'nestedA-1' => 1,
                            'nestedA-2' => 2,
                            'nestedZ' => [1, 2],
                            'only-app' => 42,
                        ],
                    ],
                    'top2' => [
                        'A' => 1,
                    ],
                    'top3' => ['Z' => 3],
                    'top0' => ['X' => 7],
                ],
                0,
            ],
            'level1' => [
                [
                    'top1' => [
                        'nestedA' => [
                            'nestedA-1' => 1,
                            'nestedA-2' => 2,
                            'nestedZ' => [1, 2],
                            'only-app' => 42,
                        ],
                        'nestedB' => [
                            'nestedB-3' => 3,
                            'nestedB-4' => 4,
                        ],
                    ],
                    'top2' => [
                        'B' => 2,
                        'A' => 1,
                    ],
                    'top3' => ['Z' => 3],
                    'top0' => ['X' => 7],
                ],
                1,
            ],
            'level2' => [
                [
                    'top1' => [
                        'nestedA' => [
                            'nestedA-3' => 3,
                            'nestedA-4' => 4,
                            'nestedZ' => [1, 2],
                            'nestedA-1' => 1,
                            'nestedA-2' => 2,
                            'only-app' => 42,
                        ],
                        'nestedB' => [
                            'nestedB-3' => 3,
                            'nestedB-4' => 4,
                        ],
                    ],
                    'top2' => [
                        'B' => 2,
                        'A' => 1,
                    ],
                    'top3' => ['Z' => 3],
                    'top0' => ['X' => 7],
                ],
                2,
            ],
            'level3' => [
                [
                    'top1' => [
                        'nestedA' => [
                            'nestedA-3' => 3,
                            'nestedA-4' => 4,
                            'nestedZ' => [3, 4, 1, 2],
                            'nestedA-1' => 1,
                            'nestedA-2' => 2,
                            'only-app' => 42,
                        ],
                        'nestedB' => [
                            'nestedB-3' => 3,
                            'nestedB-4' => 4,
                        ],
                    ],
                    'top2' => [
                        'B' => 2,
                        'A' => 1,
                    ],
                    'top3' => ['Z' => 3],
                    'top0' => ['X' => 7],
                ],
                3,
            ],
            'unlimited-reverse' => [
                [
                    'top0' => ['X' => 7],
                    'top1' => [
                        'nestedA' => [
                            'nestedA-1' => 1,
                            'nestedA-2' => 2,
                            'nestedZ' => [1, 2, 3, 4],
                            'only-app' => 42,
                            'nestedA-3' => 3,
                            'nestedA-4' => 4,
                        ],
                        'nestedB' => [
                            'nestedB-3' => 3,
                            'nestedB-4' => 4,
                        ],
                    ],
                    'top2' => [
                        'A' => 1,
                        'B' => 2,
                    ],
                    'top3' => ['Z' => 3],
                ],
                null,
                true,
            ],
            'level0-reverse' => [
                [
                    'top0' => ['X' => 7],
                    'top1' => [
                        'nestedA' => [
                            'nestedA-1' => 1,
                            'nestedA-2' => 2,
                            'nestedZ' => [1, 2],
                            'only-app' => 42,
                        ],
                    ],
                    'top2' => [
                        'A' => 1,
                    ],
                    'top3' => ['Z' => 3],
                ],
                0,
                true,
            ],
            'level1-reverse' => [
                [
                    'top0' => ['X' => 7],
                    'top1' => [
                        'nestedA' => [
                            'nestedA-1' => 1,
                            'nestedA-2' => 2,
                            'nestedZ' => [1, 2],
                            'only-app' => 42,
                        ],
                        'nestedB' => [
                            'nestedB-3' => 3,
                            'nestedB-4' => 4,
                        ],
                    ],
                    'top2' => [
                        'A' => 1,
                        'B' => 2,
                    ],
                    'top3' => ['Z' => 3],
                ],
                1,
                true,
            ],
            'level2-reverse' => [
                [
                    'top0' => ['X' => 7],
                    'top1' => [
                        'nestedA' => [
                            'nestedA-1' => 1,
                            'nestedA-2' => 2,
                            'nestedZ' => [1, 2],
                            'only-app' => 42,
                            'nestedA-3' => 3,
                            'nestedA-4' => 4,
                        ],
                        'nestedB' => [
                            'nestedB-3' => 3,
                            'nestedB-4' => 4,
                        ],
                    ],
                    'top2' => [
                        'A' => 1,
                        'B' => 2,
                    ],
                    'top3' => ['Z' => 3],
                ],
                2,
                true,
            ],
            'level3-reverse' => [
                [
                    'top0' => ['X' => 7],
                    'top1' => [
                        'nestedA' => [
                            'nestedA-1' => 1,
                            'nestedA-2' => 2,
                            'nestedZ' => [1, 2, 3, 4],
                            'only-app' => 42,
                            'nestedA-3' => 3,
                            'nestedA-4' => 4,
                        ],
                        'nestedB' => [
                            'nestedB-3' => 3,
                            'nestedB-4' => 4,
                        ],
                    ],
                    'top2' => [
                        'A' => 1,
                        'B' => 2,
                    ],
                    'top3' => ['Z' => 3],
                ],
                3,
                true,
            ],
        ];
    }

    #[DataProvider('dataBase')]
    public function testBase(array $expected, ?int $depth, bool $reverse = false): void
    {
        $config = $this->runComposerUpdateAndCreateConfig(
            rootPath: __DIR__,
            packages: [
                'test/a' => __DIR__ . '/packages/a',
            ],
            extra: [
                'config-plugin-options' => [
                    'source-directory' => 'config',
                ],
                'config-plugin' => [
                    'params' => 'params.php',
                ],
            ],
            configDirectory: 'config',
            modifiers: [
                RecursiveMerge::groupsWithDepth(['params'], $depth),
                ReverseMerge::groups(...($reverse ? ['params'] : [])),
            ],
        );

        $this->assertSame($expected, $config->get('params'));
    }
}
