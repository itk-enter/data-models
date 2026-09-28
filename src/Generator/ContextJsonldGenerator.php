<?php

namespace ItkEnter\DataModels\Generator;

use ItkEnter\DataModels\Config;
use ItkEnter\DataModels\Model\DereferencedModel;

/**
 * Generates a subject's context.jsonld (phase 4, point 3): the model type
 * names and this subject's own terms map to the D2 namespace; common-schema
 * terms map to smartdatamodels.org. Only top-level property names become
 * terms — deeply nested structural terms (e.g. GeoJSON's `coordinates`)
 * are common vocabulary, not this subject's, and aren't scoped here.
 */
final class ContextJsonldGenerator
{
    public function __construct(private readonly Config $config)
    {
    }

    /**
     * @param DereferencedModel[] $models every model in one subject
     */
    public function generate(string $subject, array $models): string
    {
        $terms = [];

        foreach ($models as $model) {
            $this->addTerm($terms, $model->folder->name, "{$this->config->namespace}/{$subject}/{$model->folder->name}");

            foreach (array_keys($model->properties) as $name) {
                $iri = $model->isOwnProperty($name)
                    ? "{$this->config->namespace}/{$subject}/{$name}"
                    : "https://smartdatamodels.org/{$name}";
                $this->addTerm($terms, $name, $iri);
            }
        }

        ksort($terms);

        return json_encode(['@context' => $terms], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)."\n";
    }

    /**
     * @param array<string, string> $terms
     */
    private function addTerm(array &$terms, string $term, string $iri): void
    {
        if (isset($terms[$term]) && $terms[$term] !== $iri) {
            throw new \RuntimeException("Term '{$term}' is defined with two different IRIs in one subject: '{$terms[$term]}' and '{$iri}'");
        }

        $terms[$term] = $iri;
    }
}
