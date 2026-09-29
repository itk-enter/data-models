<?php

namespace ItkEnter\DataModels\Validation\Checks;

use ItkEnter\DataModels\Config;
use ItkEnter\DataModels\Model\DescriptionParser;
use ItkEnter\DataModels\Model\ModelFolder;
use ItkEnter\DataModels\Validation\Check;
use ItkEnter\DataModels\Validation\ValidationError;

/**
 * Each top-level property's description follows the SDM
 * convention the generators rely on.
 */
final class DescriptionConventionCheck implements Check
{
    public function check(ModelFolder $model, Config $config): array
    {
        $errors = [];

        foreach ($model->ownProperties() as $name => $property) {
            // A pure `{"$ref": "…"}` pass-through to a common-schema
            // definition follows that definition's own convention, not
            // this model's.
            if (isset($property['$ref']) && !isset($property['description'])) {
                continue;
            }

            if (!isset($property['description']) || !is_string($property['description'])) {
                $errors[] = new ValidationError($model->name, 'schema.json', "property '{$name}' has no description");
                continue;
            }

            $errors = [...$errors, ...$this->checkDescription($model->name, $name, $property)];
        }

        return $errors;
    }

    /**
     * @param array<string, mixed> $schema
     *
     * @return ValidationError[]
     */
    private function checkDescription(string $modelName, string $property, array $schema): array
    {
        $description = $schema['description'];
        $parsed = DescriptionParser::parse($description);
        $errors = [];

        if (null === $parsed->ngsiType) {
            $errors[] = new ValidationError($modelName, 'schema.json', "property '{$property}' description must start with Property., Relationship. or GeoProperty.");
        }

        foreach (['Model', 'Units', 'Enum'] as $marker) {
            if (DescriptionParser::hasUnterminatedMarker($description, $marker)) {
                $errors[] = new ValidationError($modelName, 'schema.json', "property '{$property}' has an unterminated {$marker}:'…' marker");
            }
        }

        if (null !== $parsed->enum) {
            // Array-typed properties (e.g. `"type": "array"`) declare their
            // enum under `items.enum`, not on the property itself.
            $declaredEnum = $schema['enum'] ?? $schema['items']['enum'] ?? null;
            if (null === $declaredEnum) {
                $errors[] = new ValidationError($modelName, 'schema.json', "property '{$property}' description has Enum:'…' but the schema has no enum");
            } elseif ($parsed->enum !== array_values($declaredEnum)) {
                $errors[] = new ValidationError(
                    $modelName,
                    'schema.json',
                    "property '{$property}' description's Enum:'…' (".implode(', ', $parsed->enum).
                        ") doesn't match the schema's enum (".implode(', ', $declaredEnum).')',
                );
            }
        }

        return $errors;
    }
}
