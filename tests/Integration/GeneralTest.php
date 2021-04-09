<?php

declare(strict_types=1);

namespace Yiisoft\Config\Tests\Integration;

final class GeneralTest extends ComposerTest
{
    public function testIgnoringLineEndings(): void
    {
        $paramsFile = '/config/packages/test/a/config/dist/params.php';
        $mergePlanFile = '/config/packages/merge_plan.php';

        $this->initComposer([
            'require' => [
                'yiisoft/config' => '*',
                'test/a' => '*',
            ],
            'extra' => [
                'config-plugin-options' => [
                    'force-check' => true,
                ],
            ],
        ]);

        $params = $this->changeLineEndingsToR($paramsFile);
        $mergePlan = $this->changeLineEndingsToR($mergePlanFile);
        $this->execComposer('du');
        $this->assertSame($params, $this->getEnvironmentFileContents($paramsFile));
        $this->assertSame($mergePlan, $this->getEnvironmentFileContents($mergePlanFile));

        $params = $this->changeLineEndingsToN($paramsFile);
        $mergePlan = $this->changeLineEndingsToN($mergePlanFile);
        $this->execComposer('du');
        $this->assertSame($params, $this->getEnvironmentFileContents($paramsFile));
        $this->assertSame($mergePlan, $this->getEnvironmentFileContents($mergePlanFile));
    }

    private function changeLineEndingsToN(string $file): string
    {
        $content = strtr($this->getEnvironmentFileContents($file), [
            "\r\n" => "\n",
            "\r" => "\n",
        ]);

        $this->putEnvironmentFileContents($file, $content);

        return $content;
    }

    private function changeLineEndingsToR(string $file): string
    {
        $content = strtr($this->getEnvironmentFileContents($file), [
            "\r\n" => "\r",
            "\n" => "\r",
        ]);

        $this->putEnvironmentFileContents($file, $content);

        return $content;
    }
}
