<?php

namespace ItkEnter\DataModels\Tests\Site;

use ItkEnter\DataModels\Site\GitTags;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;

final class GitTagsTest extends TestCase
{
    private string $repo;

    protected function setUp(): void
    {
        $this->repo = sys_get_temp_dir().'/git-tags-test-'.uniqid();
        mkdir($this->repo, recursive: true);
        $this->git(['init', '--initial-branch=main']);
        $this->git(['config', 'user.email', 'test@example.test']);
        $this->git(['config', 'user.name', 'Test']);
        file_put_contents("{$this->repo}/file.txt", 'a');
        $this->git(['add', '-A']);
        $this->git(['commit', '-m', 'first']);
    }

    protected function tearDown(): void
    {
        (new Filesystem())->remove($this->repo);
    }

    public function testVersionsAreSortedBySemverNotAlphabetically(): void
    {
        // Alphabetically "0.0.10" sorts before "0.0.2" — semver must not.
        $this->git(['tag', 'PublicToilet/v0.0.2']);
        $this->git(['tag', 'PublicToilet/v0.0.10']);
        $this->git(['tag', 'PublicToilet/v0.0.1']);
        $this->git(['tag', 'OtherModel/v1.0.0']);

        self::assertSame(['0.0.1', '0.0.2', '0.0.10'], (new GitTags($this->repo))->versions('PublicToilet'));
    }

    public function testAModelWithNoTagsHasNoVersions(): void
    {
        self::assertSame([], (new GitTags($this->repo))->versions('NeverReleased'));
    }

    public function testArchiveExportsAPathspecAtATagStrippingLeadingComponents(): void
    {
        mkdir("{$this->repo}/dataModel.Test/Thing", recursive: true);
        file_put_contents("{$this->repo}/dataModel.Test/Thing/schema.json", '{"ok":true}');
        $this->git(['add', '-A']);
        $this->git(['commit', '-m', 'add model']);
        $this->git(['tag', 'Thing/v1.0.0']);

        $dest = sys_get_temp_dir().'/git-tags-test-dest-'.uniqid();
        (new GitTags($this->repo))->archive('Thing/v1.0.0', 'dataModel.Test/Thing', $dest, 2);

        self::assertFileEquals("{$this->repo}/dataModel.Test/Thing/schema.json", "{$dest}/schema.json");

        (new Filesystem())->remove($dest);
    }

    /**
     * @param string[] $args
     */
    private function git(array $args): void
    {
        (new Process(['git', ...$args], $this->repo))->mustRun();
    }
}
