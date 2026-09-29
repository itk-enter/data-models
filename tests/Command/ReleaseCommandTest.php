<?php

namespace ItkEnter\DataModels\Tests\Command;

use ItkEnter\DataModels\Command\ReleaseCommand;
use ItkEnter\DataModels\Generator\RepositoryGenerator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;

/**
 * Exercises release against a throwaway git repo, seeded from the real
 * (already-valid) PublicToilet model — never the
 * project's own repo. release only tags locally (the Taskfile's `release`
 * task pushes separately, on the host — see Taskfile.yml), so nothing
 * here touches a remote.
 */
final class ReleaseCommandTest extends TestCase
{
    private string $projectRoot;
    private string $repo;
    private Filesystem $filesystem;

    protected function setUp(): void
    {
        $this->projectRoot = dirname(__DIR__, 2);
        $this->filesystem = new Filesystem();

        $this->repo = sys_get_temp_dir().'/release-test-repo-'.uniqid();
        mkdir($this->repo, recursive: true);
        $this->seedRepo();
        // So the initial commit already matches what release's own
        // regenerate-and-diff check would produce — like the real repo,
        // where committed files are always current (task check enforces
        // it), rather than an artificial "always dirty" fixture.
        (new RepositoryGenerator($this->repo))->generate();

        $this->git(['init', '--initial-branch=main']);
        $this->git(['config', 'user.email', 'test@example.test']);
        $this->git(['config', 'user.name', 'Test']);
        $this->git(['add', '-A']);
        $this->git(['commit', '-m', 'initial']);
    }

    protected function tearDown(): void
    {
        $this->filesystem->remove($this->repo);
    }

    public function testTagsTheReleaseLocallyWhenTheTreeIsCleanAndValid(): void
    {
        // Read from the copied schema so the test survives version bumps
        // of the real model.
        $schema = json_decode(
            file_get_contents("{$this->repo}/dataModel.PointOfInterest/PublicToilet/schema.json"),
            true,
            flags: JSON_THROW_ON_ERROR,
        );
        $tag = "PublicToilet/v{$schema['x-version']}";

        $tester = $this->runRelease('PublicToilet');

        self::assertSame(0, $tester->getStatusCode());
        // The Taskfile's release task greps this exact output for the
        // tag to push — it must be the only thing printed on success.
        self::assertSame($tag, trim($tester->getDisplay()));
        self::assertSame(
            $tag,
            trim((new Process(['git', 'tag', '--list', $tag], $this->repo))->mustRun()->getOutput()),
        );
    }

    public function testFailsWhenTheTagAlreadyExists(): void
    {
        self::assertSame(0, $this->runRelease('PublicToilet')->getStatusCode());
        self::assertNotSame(0, $this->runRelease('PublicToilet')->getStatusCode());
    }

    public function testFailsOnAnUncleanTree(): void
    {
        file_put_contents("{$this->repo}/notes.local.md", 'an uncommitted scratch file');

        self::assertNotSame(0, $this->runRelease('PublicToilet')->getStatusCode());
    }

    public function testFailsOnAnUnknownModel(): void
    {
        self::assertNotSame(0, $this->runRelease('NoSuchModel')->getStatusCode());
    }

    private function runRelease(string $model): CommandTester
    {
        $application = new Application();
        $application->addCommand(new ReleaseCommand($this->repo));
        $tester = new CommandTester($application->find('release'));
        $tester->execute(['model' => $model]);

        return $tester;
    }

    private function seedRepo(): void
    {
        $this->filesystem->copy("{$this->projectRoot}/config.yaml", "{$this->repo}/config.yaml");
        $this->filesystem->mkdir("{$this->repo}/vendor-assets");
        $this->filesystem->copy(
            "{$this->projectRoot}/vendor-assets/common-schema.json",
            "{$this->repo}/vendor-assets/common-schema.json",
        );
        $this->filesystem->mirror(
            "{$this->projectRoot}/dataModel.PointOfInterest/PublicToilet",
            "{$this->repo}/dataModel.PointOfInterest/PublicToilet",
        );
        $this->filesystem->mkdir("{$this->repo}/templates");
        $this->filesystem->mirror("{$this->projectRoot}/templates", "{$this->repo}/templates");
    }

    /**
     * @param string[] $args
     */
    private function git(array $args): void
    {
        (new Process(['git', ...$args], $this->repo))->mustRun();
    }
}
