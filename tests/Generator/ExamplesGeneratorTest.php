<?php

namespace ItkEnter\DataModels\Tests\Generator;

use ItkEnter\DataModels\Config;
use ItkEnter\DataModels\Generator\ExamplesGenerator;
use ItkEnter\DataModels\Model\ModelFinder;
use ItkEnter\DataModels\Model\ModelLoader;
use PHPUnit\Framework\TestCase;

/**
 * Phase 4, point 1's required test: the generated example-normalized.json
 * equals the hand-written fixture phase 2 kept in tests/fixtures/ for this
 * comparison.
 */
final class ExamplesGeneratorTest extends TestCase
{
    public function testGeneratedNormalizedExampleMatchesTheHandWrittenFixture(): void
    {
        $repoRoot = dirname(__DIR__, 2);
        $config = Config::fromFile($repoRoot.'/config.yaml');
        $folder = (new ModelFinder($repoRoot))->find('PublicToilet');
        self::assertNotNull($folder);

        $model = (new ModelLoader($repoRoot, $config->commonSchemaRefUrl))->load($folder);
        $generated = (new ExamplesGenerator($config))->generate($model);

        $handWritten = json_decode(
            file_get_contents($repoRoot.'/tests/fixtures/PublicToilet/example-normalized.json'),
            true,
            flags: JSON_THROW_ON_ERROR,
        );

        self::assertSame($handWritten, $generated['example-normalized.json']);
    }
}
