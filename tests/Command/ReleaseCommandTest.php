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
 * (already-valid, per phase 3's tests) PublicToilet model — never the
 * project's own repo, since this tags and pushes for real.
 */
final class ReleaseCommandTest extends TestCase
{
    private string $projectRoot;
    private string $origin;
    private string $repo;
    private Filesystem $filesystem;

    protected function setUp(): void
    {
        $this->projectRoot = dirname(__DIR__, 2);
        $this->filesystem = new Filesystem();

        $this->origin = sys_get_temp_dir().'/release-test-origin-'.uniqid();
        $this->git(['init', '--bare', '--initial-branch=main', $this->origin], $this->projectRoot);

        $this->repo = sys_get_temp_dir().'/release-test-repo-'.uniqid();
        mkdir($this->repo, recursive: true);
        $this->seedRepo();
        // So the initial commit already matches what release's own
        // regenerate-and-diff check would produce — like the real repo,
        // where committed files are always current (task check enforces
        // it), rather than an artificial "always dirty" fixture.
        (new RepositoryGenerator($this->repo))->generate();

        $this->git(['init', '--initial-branch=main'], $this->repo);
        $this->git(['config', 'user.email', 'test@example.test'], $this->repo);
        $this->git(['config', 'user.name', 'Test'], $this->repo);
        $this->git(['remote', 'add', 'origin', $this->origin], $this->repo);
        $this->git(['add', '-A'], $this->repo);
        $this->git(['commit', '-m', 'initial'], $this->repo);
        $this->git(['push', 'origin', 'main'], $this->repo);
    }

    protected function tearDown(): void
    {
        $this->filesystem->remove([$this->repo, $this->origin]);
    }

    public function testTagsAndPushesTheReleaseWhenTheTreeIsCleanAndValid(): void
    {
        $status = $this->runRelease('PublicToilet');

        self::assertSame(0, $status);
        self::assertSame(
            'PublicToilet/v0.0.1',
            trim((new Process(['git', 'tag', '--list', 'PublicToilet/v0.0.1'], $this->repo))->mustRun()->getOutput()),
        );
        self::assertSame(
            'PublicToilet/v0.0.1',
            trim((new Process(['git', 'tag', '--list', 'PublicToilet/v0.0.1'], $this->origin))->mustRun()->getOutput()),
            'the tag should have been pushed to origin',
        );
    }

    public function testFailsWhenTheTagAlreadyExists(): void
    {
        self::assertSame(0, $this->runRelease('PublicToilet'));
        self::assertNotSame(0, $this->runRelease('PublicToilet'));
    }

    public function testFailsOnAnUncleanTree(): void
    {
        file_put_contents("{$this->repo}/notes.local.md", 'an uncommitted scratch file');

        self::assertNotSame(0, $this->runRelease('PublicToilet'));
    }

    public function testFailsOnAnUnknownModel(): void
    {
        self::assertNotSame(0, $this->runRelease('NoSuchModel'));
    }

    private function runRelease(string $model): int
    {
        $application = new Application();
        $application->addCommand(new ReleaseCommand($this->repo));
        $tester = new CommandTester($application->find('release'));

        return $tester->execute(['model' => $model]);
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
        file_put_contents(
            "{$this->repo}/README.md",
            "<!-- model-index:start -->\n<!-- model-index:end -->\n",
        );
    }

    /**
     * @param string[] $args
     */
    private function git(array $args, string $cwd): void
    {
        (new Process(['git', ...$args], $cwd))->mustRun();
    }
}
