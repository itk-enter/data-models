<?php

namespace ItkEnter\DataModels\Site;

use ItkEnter\DataModels\Config;
use ItkEnter\DataModels\Model\ModelFinder;
use ItkEnter\DataModels\Model\ModelFolder;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Yaml\Yaml;
use Twig\Environment;

/**
 * site:prepare: writes a MkDocs source tree to build/docs/, one
 * folder per released `<Model>/v<version>` tag plus an unversioned
 * "latest" alias, from git history — never from the live working tree,
 * since a tag is what's actually published.
 */
final class SiteBuilder
{
    private const ASSET_FILES = ['swagger-ui-bundle.js', 'swagger-ui.css'];

    private readonly Filesystem $filesystem;
    private readonly string $buildDir;

    public function __construct(
        private readonly string $repoRoot,
        private readonly Config $config,
        private readonly Environment $twig,
        private readonly GitTags $gitTags,
    ) {
        $this->filesystem = new Filesystem();
        $this->buildDir = $this->repoRoot.'/build/docs';
    }

    public function build(): void
    {
        $this->filesystem->remove($this->buildDir);
        $this->filesystem->mkdir($this->buildDir);
        $this->copyAssets();

        $folders = (new ModelFinder($this->repoRoot))->all();
        $released = [];
        $unreleased = [];

        foreach ($folders as $folder) {
            $versions = $this->gitTags->versions($folder->name);
            if ([] === $versions) {
                $unreleased[] = $folder;
                continue;
            }

            foreach ($versions as $version) {
                $this->exportVersion($folder, $version);
            }
            $this->copyLatest($folder, end($versions));
            $released[] = ['folder' => $folder, 'versions' => $versions];
        }

        $bySubject = [];
        foreach ($released as $entry) {
            $bySubject[$entry['folder']->subject][] = $entry;
        }
        foreach ($bySubject as $subject => $entries) {
            $this->writeSubjectLatestContext($subject, $entries);
        }

        foreach ($released as $entry) {
            $this->buildModelPages($entry['folder'], $entry['versions']);
        }

        $this->buildTermPages($released);
        $this->buildIndexPage($released, $unreleased);
    }

    private function copyAssets(): void
    {
        $assetsDir = "{$this->buildDir}/assets";
        $this->filesystem->mkdir($assetsDir);
        foreach (self::ASSET_FILES as $file) {
            $this->filesystem->copy("{$this->repoRoot}/vendor-assets/swagger-ui/{$file}", "{$assetsDir}/{$file}");
        }
    }

    private function exportVersion(ModelFolder $folder, string $version): void
    {
        $tag = "{$folder->name}/v{$version}";
        $dest = "{$this->buildDir}/{$folder->subject}/{$folder->name}/v{$version}";

        $this->gitTags->archive($tag, "{$folder->subject}/{$folder->name}", $dest, 2);
        $this->gitTags->archive($tag, "{$folder->subject}/context.jsonld", $dest, 1);

        // The model's own README.md (for GitHub) would otherwise collide
        // with the index.md this class writes for the very same page.
        $this->filesystem->remove("{$dest}/README.md");
    }

    private function copyLatest(ModelFolder $folder, string $latestVersion): void
    {
        $source = "{$this->buildDir}/{$folder->subject}/{$folder->name}/v{$latestVersion}";
        $dest = "{$this->buildDir}/{$folder->subject}/{$folder->name}";
        $this->filesystem->mirror($source, $dest);
    }

    /**
     * Merges every released model's latest context.jsonld (exported per
     * model, above) into one subject-level file — the unversioned
     * `/<Subject>/context.jsonld` the URL layout calls for, distinct from
     * each model's own per-version copy.
     *
     * @param array<int, array{folder: ModelFolder, versions: string[]}> $entries
     */
    private function writeSubjectLatestContext(string $subject, array $entries): void
    {
        $merged = [];
        foreach ($entries as $entry) {
            $path = "{$this->buildDir}/{$subject}/{$entry['folder']->name}/context.jsonld";
            $context = json_decode(file_get_contents($path), true, flags: JSON_THROW_ON_ERROR)['@context'] ?? [];
            foreach ($context as $term => $iri) {
                if (isset($merged[$term]) && $merged[$term] !== $iri) {
                    throw new \RuntimeException("Term '{$term}' is defined with two different IRIs in subject {$subject}: '{$merged[$term]}' and '{$iri}'");
                }
                $merged[$term] = $iri;
            }
        }

        ksort($merged);
        $json = json_encode(['@context' => $merged], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)."\n";
        file_put_contents("{$this->buildDir}/{$subject}/context.jsonld", $json);
    }

    /**
     * @param string[] $versions
     */
    private function buildModelPages(ModelFolder $folder, array $versions): void
    {
        $latest = end($versions);

        foreach ([...$versions, 'latest'] as $token) {
            $isLatest = 'latest' === $token;
            $version = $isLatest ? $latest : $token;
            $dir = $isLatest
                ? "{$this->buildDir}/{$folder->subject}/{$folder->name}"
                : "{$this->buildDir}/{$folder->subject}/{$folder->name}/v{$version}";

            $notes = is_file("{$dir}/notes.yaml") ? Yaml::parseFile("{$dir}/notes.yaml") : [];
            $status = (string) ($notes['status'] ?? 'own model');
            $specBody = $this->stripGeneratedHeader(file_get_contents("{$dir}/doc/spec.md"));

            $page = new ModelPageData(
                subject: $folder->subject,
                name: $folder->name,
                status: $status,
                version: $version,
                isLatest: $isLatest,
                versionsLine: $this->versionsLine($versions, $version, $isLatest),
                specBody: $specBody,
                contextUrl: "{$this->config->rawUrl}/{$folder->name}/v{$version}/{$folder->subject}/context.jsonld",
                rootPath: str_repeat('../', $isLatest ? 2 : 3),
            );

            file_put_contents("{$dir}/index.md", $this->twig->render('site/model-page.md.twig', get_object_vars($page)));
        }
    }

    /**
     * A "Versions: 0.0.1 · **0.0.2**" line, blank line included, or '' when
     * there's only ever been one — built here, not looped over in the
     * template, for the same reason spec.md's property list is (see
     * SpecMdGenerator).
     *
     * @param string[] $versions
     */
    private function versionsLine(array $versions, string $currentVersion, bool $isLatest): string
    {
        if (\count($versions) < 2) {
            return '';
        }

        $parts = [];
        foreach ($versions as $v) {
            $href = $isLatest ? "v{$v}/" : ($v === $currentVersion ? './' : "../v{$v}/");
            $parts[] = $v === $currentVersion ? "**{$v}**" : "[{$v}]({$href})";
        }

        return "\n\nVersions: ".implode(' · ', $parts);
    }

    /**
     * spec.md's generated-file HTML comments and its own "# Name" / "Version:
     * …" header are redundant on the site page, which supplies its own
     * (with status and a version switcher alongside). Both are a fixed
     * shape SpecMdGenerator itself produces, so they're safe to strip by
     * position rather than guess at from content.
     */
    private function stripGeneratedHeader(string $markdown): string
    {
        $lines = explode("\n", $markdown);
        while ([] !== $lines && (str_starts_with($lines[0], '<!--') || '' === trim($lines[0]))) {
            array_shift($lines);
        }

        // "# Name", "", "Version: …", ""
        $lines = \array_slice($lines, 4);

        return implode("\n", $lines);
    }

    /**
     * @param array<int, array{folder: ModelFolder, versions: string[]}> $released
     */
    private function buildTermPages(array $released): void
    {
        $bySubject = [];
        foreach ($released as $entry) {
            $bySubject[$entry['folder']->subject][] = $entry;
        }

        foreach ($bySubject as $subject => $entries) {
            $contextPath = "{$this->buildDir}/{$subject}/context.jsonld";
            if (!is_file($contextPath)) {
                continue;
            }

            $context = json_decode(file_get_contents($contextPath), true, flags: JSON_THROW_ON_ERROR)['@context'];
            $modelNames = array_map(static fn (array $e) => $e['folder']->name, $entries);

            foreach ($context as $term => $iri) {
                if (!\is_string($iri) || !str_starts_with($iri, $this->config->namespace) || \in_array($term, $modelNames, true)) {
                    // Common-schema/schema.org terms resolve elsewhere; a
                    // model type name has its own model page already.
                    continue;
                }

                $this->buildTermPage($subject, $term, $iri, $entries);
            }
        }
    }

    /**
     * @param array<int, array{folder: ModelFolder, versions: string[]}> $entries
     */
    private function buildTermPage(string $subject, string $term, string $iri, array $entries): void
    {
        $description = null;
        $ngsiType = null;
        $usages = [];

        foreach ($entries as $entry) {
            $folder = $entry['folder'];
            $modelYaml = Yaml::parseFile("{$this->buildDir}/{$subject}/{$folder->name}/model.yaml");
            $property = $modelYaml[$folder->name]['properties'][$term] ?? null;
            if (null === $property) {
                continue;
            }

            $description ??= $property['description'] ?? null;
            $ngsiType ??= $property['x-ngsi']['type'] ?? null;
            $usages[] = ['model' => $folder->name, 'version' => end($entry['versions']), 'href' => "../{$folder->name}/"];
        }

        if ([] === $usages) {
            return;
        }

        $rendered = $this->twig->render('site/term-page.md.twig', [
            'term' => $term,
            'iri' => $iri,
            'description' => $description,
            'ngsiType' => $ngsiType,
            'usages' => $usages,
        ]);

        $dir = "{$this->buildDir}/{$subject}/{$term}";
        $this->filesystem->mkdir($dir);
        file_put_contents("{$dir}/index.md", $rendered);
    }

    /**
     * @param array<int, array{folder: ModelFolder, versions: string[]}> $released
     * @param ModelFolder[]                                              $unreleased
     */
    private function buildIndexPage(array $released, array $unreleased): void
    {
        $bySubject = [];

        foreach ($released as $entry) {
            $folder = $entry['folder'];
            $notes = Yaml::parseFile("{$this->buildDir}/{$folder->subject}/{$folder->name}/notes.yaml") ?: [];
            $bySubject[$folder->subject][] = [
                'name' => $folder->name,
                'version' => end($entry['versions']),
                'status' => (string) ($notes['status'] ?? 'own model'),
                // A .md target (not a bare directory) so mkdocs --strict validates it.
                'links' => "[Spec]({$folder->subject}/{$folder->name}/index.md)",
            ];
        }

        foreach ($unreleased as $folder) {
            $githubUrl = "{$this->config->repoUrl}/tree/{$this->config->defaultBranch}/{$folder->subject}/{$folder->name}";
            $bySubject[$folder->subject][] = [
                'name' => $folder->name,
                'version' => 'unreleased',
                'status' => (string) ($folder->notes['status'] ?? 'own model'),
                'links' => "[GitHub]({$githubUrl})",
            ];
        }

        $subjects = [];
        foreach ($bySubject as $subject => $models) {
            usort($models, static fn (array $a, array $b) => $a['name'] <=> $b['name']);
            $subjects[] = ['name' => $subject, 'models' => $models];
        }
        usort($subjects, static fn (array $a, array $b) => $a['name'] <=> $b['name']);

        file_put_contents("{$this->buildDir}/index.md", $this->twig->render('site/index.md.twig', ['subjects' => $subjects]));
    }
}
