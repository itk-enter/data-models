<?php

namespace ItkEnter\DataModels\Site;

use ItkEnter\DataModels\Config;
use ItkEnter\DataModels\Model\ModelFinder;

/**
 * The build checks phase 6 requires task site:build to fail on: every
 * `$id` in a latest schema maps to a built file, every own-namespace IRI
 * in every context has a page, and every relative `$ref` in every built
 * swagger.yaml resolves. Runs against build/site/, after `mkdocs build`.
 */
final class BuildChecker
{
    public function __construct(private readonly string $repoRoot, private readonly Config $config)
    {
    }

    /**
     * @return string[] problems found; empty if the build is clean
     */
    public function check(): array
    {
        return [
            ...$this->checkSchemaIdsMapToBuiltFiles(),
            ...$this->checkContextIrisHavePages(),
            ...$this->checkSwaggerRefsResolve(),
        ];
    }

    /**
     * @return string[]
     */
    private function checkSchemaIdsMapToBuiltFiles(): array
    {
        $errors = [];
        foreach ((new ModelFinder($this->repoRoot))->all() as $folder) {
            // Unreleased models have no build/docs/ source, by design (they
            // show as "unreleased" on the index instead) — nothing to check.
            if (!is_dir("{$this->repoRoot}/build/docs/{$folder->subject}/{$folder->name}")) {
                continue;
            }

            $id = $folder->schema['$id'] ?? null;
            if (!\is_string($id)) {
                continue;
            }

            $builtPath = $this->pagesUrlToBuiltPath($id);
            if (!is_file($builtPath)) {
                $errors[] = "{$folder->name}'s \$id ({$id}) has no built file at ".$this->relativeToRepo($builtPath);
            }
        }

        return $errors;
    }

    /**
     * @return string[]
     */
    private function checkContextIrisHavePages(): array
    {
        $errors = [];
        foreach (glob("{$this->repoRoot}/build/docs/*/context.jsonld") as $contextPath) {
            $context = json_decode(file_get_contents($contextPath), true, flags: JSON_THROW_ON_ERROR)['@context'] ?? [];
            foreach ($context as $term => $iri) {
                if (!\is_string($iri) || !str_starts_with($iri, $this->config->namespace)) {
                    continue;
                }

                $builtPath = $this->pagesUrlToBuiltPath(rtrim($iri, '/').'/index.html');
                if (!is_file($builtPath)) {
                    $errors[] = "term '{$term}' ({$iri}) has no built page at ".$this->relativeToRepo($builtPath);
                }
            }
        }

        return $errors;
    }

    /**
     * @return string[]
     */
    private function checkSwaggerRefsResolve(): array
    {
        $errors = [];
        $pattern = "{$this->repoRoot}/build/site/*/*/{,v*/}swagger.yaml";
        foreach (glob($pattern, \GLOB_BRACE) as $swaggerPath) {
            preg_match_all('/\$ref:\s*\.\/([^\s\'"]+)/', file_get_contents($swaggerPath), $matches);
            foreach ($matches[1] as $relRef) {
                $target = \dirname($swaggerPath).'/'.explode('#', $relRef, 2)[0];
                if (!is_file($target)) {
                    $errors[] = $this->relativeToRepo($swaggerPath)." \$ref './{$relRef}' does not resolve";
                }
            }
        }

        return $errors;
    }

    private function pagesUrlToBuiltPath(string $url): string
    {
        $relative = ltrim(substr($url, \strlen(rtrim($this->config->pagesUrl, '/'))), '/');

        return "{$this->repoRoot}/build/site/{$relative}";
    }

    private function relativeToRepo(string $path): string
    {
        return ltrim(substr($path, \strlen($this->repoRoot)), '/');
    }
}
