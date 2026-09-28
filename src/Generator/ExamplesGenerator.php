<?php

namespace ItkEnter\DataModels\Generator;

use ItkEnter\DataModels\Config;
use ItkEnter\DataModels\Model\DereferencedModel;

/**
 * Generates the three files derived from examples/example.json (phase 4,
 * point 1): example-normalized.json (NGSI v2), example.jsonld (NGSI-LD
 * key-values) and example-normalized.jsonld (NGSI-LD normalized).
 */
final class ExamplesGenerator
{
    private const ENTITY_KEYS = ['id', 'type'];

    public function __construct(private readonly Config $config)
    {
    }

    /**
     * @return array{
     *     'example-normalized.json': array<string, mixed>,
     *     'example.jsonld': array<string, mixed>,
     *     'example-normalized.jsonld': array<string, mixed>,
     * }
     */
    public function generate(DereferencedModel $model): array
    {
        $example = $model->folder->example ?? [];
        $contextUrl = "{$this->config->pagesUrl}/{$model->folder->subject}/context.jsonld";

        $normalizedV2 = $this->entityKeys($example);
        $normalizedJsonld = $this->entityKeys($example);

        foreach ($example as $name => $value) {
            if (\in_array($name, self::ENTITY_KEYS, true)) {
                continue;
            }

            $property = $model->properties[$name] ?? [];
            $parsed = $model->parsedDescription($name);
            $ngsiType = null === $parsed ? 'Property' : ($parsed->ngsiType ?? 'Property');

            $normalizedV2[$name] = ['type' => $this->ngsiV2Type($property, $ngsiType), 'value' => $value];
            $normalizedJsonld[$name] = 'Relationship' === $ngsiType
                ? ['type' => 'Relationship', 'object' => $value]
                : ['type' => $ngsiType, 'value' => $value];
        }

        $normalizedJsonld['@context'] = $contextUrl;

        return [
            'example-normalized.json' => $normalizedV2,
            'example.jsonld' => [...$example, '@context' => $contextUrl],
            'example-normalized.jsonld' => $normalizedJsonld,
        ];
    }

    /**
     * @param array<string, mixed> $example
     *
     * @return array<string, mixed>
     */
    private function entityKeys(array $example): array
    {
        $keys = [];
        foreach (self::ENTITY_KEYS as $key) {
            if (isset($example[$key])) {
                $keys[$key] = $example[$key];
            }
        }

        return $keys;
    }

    /**
     * @param array<string, mixed> $property
     */
    private function ngsiV2Type(array $property, string $ngsiType): string
    {
        if ('Relationship' === $ngsiType) {
            return 'Relationship';
        }
        if ('GeoProperty' === $ngsiType) {
            return 'geo:json';
        }

        if ('date-time' === ($property['format'] ?? null)) {
            return 'DateTime';
        }

        return match ($property['type'] ?? null) {
            'boolean' => 'Boolean',
            'integer', 'number' => 'Number',
            'object', 'array' => 'StructuredValue',
            default => isset($property['oneOf']) || isset($property['anyOf']) ? 'StructuredValue' : 'Text',
        };
    }
}
