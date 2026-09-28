<?php

namespace ItkEnter\DataModels\Generator;

use ItkEnter\DataModels\Model\DereferencedModel;
use ItkEnter\DataModels\Model\DescriptionParser;

/**
 * Generates model.yaml (phase 4, point 2): SDM's format,
 * `{ModelName: {description, properties, required, type: object}}`, with
 * each property's `x-ngsi` derived from its description.
 */
final class ModelYamlGenerator
{
    /**
     * @return array<string, mixed>
     */
    public function generate(DereferencedModel $model): array
    {
        $properties = [];
        foreach ($model->properties as $name => $property) {
            $properties[$name] = $this->transformProperty($property);
        }
        ksort($properties);

        return [
            $model->folder->name => $this->sorted([
                'description' => $model->description,
                'properties' => $properties,
                'required' => $model->required,
                'type' => 'object',
            ]),
        ];
    }

    /**
     * @param array<string, mixed> $property
     *
     * @return array<string, mixed>
     */
    private function transformProperty(array $property): array
    {
        $out = $property;

        if (isset($out['properties']) && \is_array($out['properties'])) {
            $out['properties'] = array_map($this->transformProperty(...), $out['properties']);
            ksort($out['properties']);
        }

        if (isset($out['items']) && $this->isSchema($out['items'])) {
            $out['items'] = $this->transformProperty($out['items']);
        }

        foreach (['oneOf', 'anyOf', 'allOf'] as $key) {
            if (isset($out[$key]) && \is_array($out[$key])) {
                $out[$key] = array_map($this->transformProperty(...), $out[$key]);
            }
        }

        if (isset($out['description']) && \is_string($out['description'])) {
            $parsed = DescriptionParser::parse($out['description']);
            $xNgsi = ['type' => $parsed->ngsiType ?? 'Property'];
            if (null !== $parsed->model) {
                $xNgsi['model'] = $parsed->model;
            }
            if (null !== $parsed->units) {
                $xNgsi['units'] = $parsed->units;
            }
            // SDM's own model.yaml strips only the leading NGSI-type token
            // from the description (Model:'…'/Enum:'…'/Source: … stay).
            $out['description'] = $parsed->text;
            $out['x-ngsi'] = $this->sorted($xNgsi);
        }

        return $this->sorted($out);
    }

    private function isSchema(mixed $value): bool
    {
        return \is_array($value) && !array_is_list($value);
    }

    /**
     * @param array<string, mixed> $array
     *
     * @return array<string, mixed>
     */
    private function sorted(array $array): array
    {
        ksort($array);

        return $array;
    }
}
