<?php

namespace ItkEnter\DataModels\Generator;

use ItkEnter\DataModels\Model\DereferencedModel;

/**
 * Generates swagger.yaml: OpenAPI 3.0.0 in SDM's shape,
 * but `$ref`ing model.yaml and the examples by relative path — not SDM's
 * absolute URLs — so the same file works at every versioned docs-site
 * path (no `servers`, "Try it out" is off).
 */
final class SwaggerYamlGenerator
{
    /**
     * @return array<string, mixed>
     */
    public function generate(DereferencedModel $model): array
    {
        $name = $model->folder->name;
        $version = (string) ($model->folder->schema['x-version'] ?? $model->folder->schema['$schemaVersion'] ?? '0.0.0');

        return [
            'openapi' => '3.0.0',
            'info' => [
                'title' => $name,
                'description' => $model->description,
                'version' => $version,
            ],
            'paths' => [
                '/ngsi-ld/v1/entities' => [
                    'get' => [
                        'tags' => ['ngsi-ld'],
                        'description' => 'Retrieve a set of entities which matches a specific query from an NGSI-LD system',
                        'parameters' => [
                            [
                                'name' => 'type',
                                'in' => 'query',
                                'required' => true,
                                'schema' => ['type' => 'string', 'enum' => [$name]],
                            ],
                        ],
                        'responses' => [
                            '200' => [
                                'description' => 'OK',
                                'content' => [
                                    'application/ld+json' => [
                                        'examples' => [
                                            'keyvalues' => [
                                                'summary' => 'Key-Values Pairs',
                                                // The response is a list of entities, so the
                                                // example value is a one-item list too.
                                                'value' => [['$ref' => './examples/example.json']],
                                            ],
                                            'normalized' => [
                                                'summary' => 'Normalized NGSI-LD',
                                                'value' => [['$ref' => './examples/example-normalized.jsonld']],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'components' => [
                'schemas' => [
                    $name => ['$ref' => "./model.yaml#/{$name}"],
                ],
            ],
            'tags' => [
                ['name' => 'ngsi-ld', 'description' => 'NGSI-LD Linked-data Format'],
            ],
        ];
    }
}
