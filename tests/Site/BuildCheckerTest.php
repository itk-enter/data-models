<?php

namespace ItkEnter\DataModels\Tests\Site;

use ItkEnter\DataModels\Config;
use ItkEnter\DataModels\Site\BuildChecker;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;

final class BuildCheckerTest extends TestCase
{
    private string $repoRoot;
    private Config $config;

    protected function setUp(): void
    {
        $this->repoRoot = sys_get_temp_dir().'/build-checker-test-'.uniqid();
        mkdir($this->repoRoot, recursive: true);
        $this->config = Config::fromFile(dirname(__DIR__, 2).'/config.yaml');
    }

    protected function tearDown(): void
    {
        (new Filesystem())->remove($this->repoRoot);
    }

    public function testPassesWhenEverythingResolves(): void
    {
        $this->writeModel('PublicToilet', 'https://itk-enter.github.io/data-models/dataModel.PointOfInterest/PublicToilet/schema.json');
        $this->writeBuiltFile('dataModel.PointOfInterest/PublicToilet/schema.json', '{}');
        $this->writeContext(['toiletType' => 'https://itk-enter.github.io/data-models/dataModel.PointOfInterest/toiletType']);
        $this->writeBuiltFile('dataModel.PointOfInterest/toiletType/index.html', '<html></html>');
        $this->writeBuiltFile('dataModel.PointOfInterest/PublicToilet/swagger.yaml', "components:\n  schemas:\n    PublicToilet:\n      \$ref: ./model.yaml#/PublicToilet\n");
        $this->writeBuiltFile('dataModel.PointOfInterest/PublicToilet/model.yaml', 'PublicToilet: {}');

        self::assertSame([], (new BuildChecker($this->repoRoot, $this->config))->check());
    }

    public function testFailsWhenTheIdHasNoBuiltFile(): void
    {
        $this->writeModel('PublicToilet', 'https://itk-enter.github.io/data-models/dataModel.PointOfInterest/PublicToilet/schema.json');
        // No build/site/.../schema.json written.

        $errors = (new BuildChecker($this->repoRoot, $this->config))->check();

        self::assertNotSame([], $errors);
        self::assertStringContainsString('has no built file', $errors[0]);
    }

    public function testFailsWhenAnOwnNamespaceTermHasNoPage(): void
    {
        $this->writeModel('PublicToilet', 'https://itk-enter.github.io/data-models/dataModel.PointOfInterest/PublicToilet/schema.json');
        $this->writeBuiltFile('dataModel.PointOfInterest/PublicToilet/schema.json', '{}');
        $this->writeContext(['toiletType' => 'https://itk-enter.github.io/data-models/dataModel.PointOfInterest/toiletType']);
        // No term page written for toiletType.

        $errors = (new BuildChecker($this->repoRoot, $this->config))->check();

        self::assertNotSame([], $errors);
        self::assertStringContainsString('toiletType', implode("\n", $errors));
    }

    public function testFailsWhenARelativeSwaggerRefDoesNotResolve(): void
    {
        $this->writeModel('PublicToilet', 'https://itk-enter.github.io/data-models/dataModel.PointOfInterest/PublicToilet/schema.json');
        $this->writeBuiltFile('dataModel.PointOfInterest/PublicToilet/schema.json', '{}');
        $this->writeBuiltFile('dataModel.PointOfInterest/PublicToilet/swagger.yaml', "\$ref: ./model.yaml#/PublicToilet\n");
        // No model.yaml written alongside it.

        $errors = (new BuildChecker($this->repoRoot, $this->config))->check();

        self::assertNotSame([], $errors);
        self::assertStringContainsString('does not resolve', implode("\n", $errors));
    }

    private function writeModel(string $name, string $id): void
    {
        $dir = "{$this->repoRoot}/dataModel.PointOfInterest/{$name}";
        mkdir($dir, recursive: true);
        file_put_contents("{$dir}/schema.json", json_encode(['$id' => $id]));
        // The BuildChecker skips unreleased models by checking for a
        // build/docs/ source directory.
        mkdir("{$this->repoRoot}/build/docs/dataModel.PointOfInterest/{$name}", recursive: true);
    }

    /**
     * @param array<string, string> $terms
     */
    private function writeContext(array $terms): void
    {
        $this->writeSourceFile('build/docs/dataModel.PointOfInterest/context.jsonld', json_encode(['@context' => $terms]));
    }

    private function writeBuiltFile(string $relativePath, string $contents): void
    {
        $this->writeSourceFile("build/site/{$relativePath}", $contents);
    }

    private function writeSourceFile(string $relativePath, string $contents): void
    {
        $path = "{$this->repoRoot}/{$relativePath}";
        if (!is_dir(\dirname($path))) {
            mkdir(\dirname($path), recursive: true);
        }
        file_put_contents($path, $contents);
    }
}
