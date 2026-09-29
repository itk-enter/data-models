<?php

namespace ItkEnter\DataModels\Tests\Generator;

use ItkEnter\DataModels\Config;
use ItkEnter\DataModels\Generator\ContextJsonldGenerator;
use ItkEnter\DataModels\Model\ModelFinder;
use ItkEnter\DataModels\Model\ModelLoader;
use PHPUnit\Framework\TestCase;

/**
 * `id` and `type` map to the JSON-LD keywords, not to ordinary terms, so
 * the context expands an entity's identifier and type on its own.
 */
final class ContextJsonldGeneratorTest extends TestCase
{
    public function testIdAndTypeMapToJsonLdKeywords(): void
    {
        $repoRoot = dirname(__DIR__, 2);
        $config = Config::fromFile($repoRoot.'/config.yaml');
        $folder = (new ModelFinder($repoRoot))->find('PublicToilet');
        self::assertNotNull($folder);

        $model = (new ModelLoader($repoRoot, $config->commonSchemaRefUrl))->load($folder);
        $context = json_decode(
            (new ContextJsonldGenerator($config))->generate($folder->subject, [$model]),
            true,
            flags: JSON_THROW_ON_ERROR,
        )['@context'];

        self::assertSame('@id', $context['id']);
        self::assertSame('@type', $context['type']);
    }
}
