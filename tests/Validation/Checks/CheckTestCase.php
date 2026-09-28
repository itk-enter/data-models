<?php

namespace ItkEnter\DataModels\Tests\Validation\Checks;

use ItkEnter\DataModels\Config;
use ItkEnter\DataModels\Model\ModelFolder;
use PHPUnit\Framework\TestCase;

abstract class CheckTestCase extends TestCase
{
    protected string $repoRoot;
    protected Config $config;

    protected function setUp(): void
    {
        $this->repoRoot = dirname(__DIR__, 3);
        $this->config = Config::fromFile($this->repoRoot.'/config.yaml');
    }

    /**
     * @param array<string, mixed> $schema
     */
    protected function fixture(string $name, array $schema = []): ModelFolder
    {
        return new ModelFolder('Test', $name, $this->repoRoot.'/tests/fixtures/Checks/'.$name, $schema, null, null);
    }

    protected function publicToilet(): ModelFolder
    {
        $path = $this->repoRoot.'/dataModel.PointOfInterest/PublicToilet';
        $schema = json_decode(file_get_contents($path.'/schema.json'), true, flags: JSON_THROW_ON_ERROR);

        return new ModelFolder('dataModel.PointOfInterest', 'PublicToilet', $path, $schema, null, null);
    }
}
