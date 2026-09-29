<?php

namespace ItkEnter\DataModels\Model;

/**
 * A model's schema, fully dereferenced: SDM's model.yaml shape, plus which
 * of its top-level properties came from a common-schema `$ref` rather than
 * this model's own extension (context.jsonld needs that
 * distinction to pick the right IRI namespace).
 */
final class DereferencedModel
{
    /**
     * @param array<string, array<string, mixed>> $properties
     * @param string[]                            $required
     * @param string[]                            $commonSchemaProperties
     */
    public function __construct(
        public readonly ModelFolder $folder,
        public readonly string $description,
        public readonly array $properties,
        public readonly array $required,
        public readonly array $commonSchemaProperties,
    ) {
    }

    public function isOwnProperty(string $name): bool
    {
        return !\in_array($name, $this->commonSchemaProperties, true);
    }

    public function parsedDescription(string $name): ?ParsedDescription
    {
        $description = $this->properties[$name]['description'] ?? null;

        return null === $description ? null : DescriptionParser::parse($description);
    }
}
