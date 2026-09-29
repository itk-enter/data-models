<?php

namespace ItkEnter\DataModels\Generator;

use ItkEnter\DataModels\Config;
use ItkEnter\DataModels\Model\DereferencedModel;
use Twig\Environment;

/**
 * Generates a model's README.md: status, current
 * version, a pinnable contextUrl for the (eventual) latest tag, and links
 * to spec, schema, examples and swagger.
 */
final class ModelReadmeGenerator
{
    public function __construct(private readonly Environment $twig, private readonly Config $config)
    {
    }

    public function generate(DereferencedModel $model): string
    {
        $folder = $model->folder;
        $version = (string) ($folder->schema['x-version'] ?? $folder->schema['$schemaVersion'] ?? '0.0.0');
        $status = $folder->notes['status'] ?? 'own model';
        $tag = "{$folder->name}/v{$version}";

        return $this->twig->render('model-readme.md.twig', [
            'name' => $folder->name,
            'subject' => $folder->subject,
            'version' => $version,
            'status' => $status,
            'contextUrl' => "{$this->config->rawUrl}/{$tag}/{$folder->subject}/context.jsonld",
            'specUrl' => "{$this->config->pagesUrl}/{$folder->subject}/{$folder->name}/",
            'schemaUrl' => $this->config->modelSchemaUrl($folder->subject, $folder->name),
            'exampleUrl' => "{$this->config->pagesUrl}/{$folder->subject}/{$folder->name}/examples/example.json",
            'swaggerUrl' => "{$this->config->pagesUrl}/{$folder->subject}/{$folder->name}/swagger.yaml",
        ]);
    }
}
