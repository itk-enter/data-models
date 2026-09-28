<?php

namespace ItkEnter\DataModels\Site;

use Symfony\Component\Process\Process;

/**
 * The `git tag`/`git archive` calls site:prepare needs (phase 6, step 1).
 * CI must check out with `fetch-depth: 0` for tags to be visible.
 */
final class GitTags
{
    public function __construct(private readonly string $repoRoot)
    {
    }

    /**
     * A model's `<Model>/v*` tags, sorted oldest to newest by semver.
     *
     * @return string[] the version part only, e.g. ["0.0.1", "0.0.2"]
     */
    public function versions(string $modelName): array
    {
        $process = new Process(['git', 'tag', '--list', "{$modelName}/v*"], $this->repoRoot);
        $process->mustRun();

        $versions = [];
        foreach (explode("\n", trim($process->getOutput())) as $tag) {
            if ('' === $tag) {
                continue;
            }
            $versions[] = substr($tag, \strlen($modelName) + 2);
        }

        usort($versions, static fn (string $a, string $b) => version_compare($a, $b));

        return $versions;
    }

    /**
     * Exports `$pathspec` as it was at `$tag`, into `$destination`, with
     * `$stripComponents` leading path segments removed (matching
     * `tar --strip-components`).
     */
    public function archive(string $tag, string $pathspec, string $destination, int $stripComponents): void
    {
        if (!is_dir($destination)) {
            mkdir($destination, recursive: true);
        }

        $archive = new Process(['git', 'archive', $tag, '--', $pathspec], $this->repoRoot);
        $tar = new Process(['tar', 'x', "--strip-components={$stripComponents}", '-C', $destination]);

        $archive->start();
        $tar->setInput($archive->getIterator(Process::ITER_SKIP_ERR));
        $tar->run();

        if (!$archive->isSuccessful()) {
            throw new \RuntimeException("git archive {$tag} -- {$pathspec} failed: ".$archive->getErrorOutput());
        }
        if (!$tar->isSuccessful()) {
            throw new \RuntimeException("Extracting {$tag} -- {$pathspec} failed: ".$tar->getErrorOutput());
        }
    }
}
