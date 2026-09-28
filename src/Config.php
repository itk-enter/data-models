<?php

namespace ItkEnter\DataModels;

use Symfony\Component\Yaml\Yaml;

/**
 * The repository's config.yaml: the IRI namespace (D2), public base URLs,
 * and the vendored third-party file pins.
 */
final class Config
{
    public readonly string $namespace;
    public readonly string $rawUrl;
    public readonly string $pagesUrl;
    public readonly string $repoUrl;
    public readonly string $defaultBranch;
    public readonly string $commonSchemaRefUrl;
    public readonly string $commonSchemaSource;
    public readonly string $commonSchemaRef;
    public readonly string $swaggerUiVersion;

    /**
     * @param array<string, mixed> $data
     */
    public function __construct(array $data)
    {
        $this->namespace = $data['namespace'];
        $this->rawUrl = rtrim($data['urls']['raw'], '/');
        $this->pagesUrl = rtrim($data['urls']['pages'], '/');
        $this->repoUrl = rtrim($data['urls']['repo'], '/');
        $this->defaultBranch = $data['urls']['default_branch'];
        $this->commonSchemaRefUrl = $data['vendor']['common_schema']['ref_url'];
        $this->commonSchemaSource = $data['vendor']['common_schema']['source'];
        $this->commonSchemaRef = (string) $data['vendor']['common_schema']['ref'];
        $this->swaggerUiVersion = (string) $data['vendor']['swagger_ui']['version'];
    }

    public static function fromFile(string $path): self
    {
        return new self(Yaml::parseFile($path));
    }

    /**
     * The URL a fully fetched, pinned copy of SDM's common-schema.json
     * comes from (vendor:update's fetch source).
     */
    public function commonSchemaFetchUrl(): string
    {
        return str_replace('{ref}', $this->commonSchemaRef, $this->commonSchemaSource);
    }

    /**
     * The Pages URL for a model's schema.json, its $id and x-model-schema.
     */
    public function modelSchemaUrl(string $subject, string $model): string
    {
        return "{$this->pagesUrl}/{$subject}/{$model}/schema.json";
    }

    /**
     * The GitHub blob URL for a model's LICENSE.md, its x-license-url.
     */
    public function modelLicenseUrl(string $subject, string $model): string
    {
        return "{$this->repoUrl}/blob/{$this->defaultBranch}/{$subject}/{$model}/LICENSE.md";
    }
}
