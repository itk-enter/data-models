<?php

namespace ItkEnter\DataModels\Model;

/**
 * Fully dereferences a model's schema.json into one flat structure: SDM's
 * model.yaml format (`{description, properties, required, type}`, `$ref`s
 * resolved and nested objects expanded). PHP has no direct counterpart to
 * Python's jsonref, so this is a small resolver of our own, over exactly
 * the two documents a model schema can reference: itself (bare `#/...`
 * fragments) and the vendored common-schema.json (by full URI, or by a
 * bare fragment found while already resolving inside it).
 */
final class SchemaDereferencer
{
    private const MAX_DEPTH = 20;

    /**
     * @param array<string, mixed> $commonSchema
     */
    public function __construct(
        private readonly array $commonSchema,
        private readonly string $commonSchemaRefUrl,
    ) {
    }

    /**
     * @param array<string, mixed> $schema
     *
     * @return array<string, mixed>
     */
    public function dereference(array $schema): array
    {
        $properties = [];
        $required = [];
        $commonSchemaProperties = [];

        foreach ([$schema, ...($schema['allOf'] ?? [])] as $branch) {
            [$resolved, $insideCommonSchema] = $this->resolveBranch($branch, $schema);

            foreach ($resolved['properties'] ?? [] as $name => $property) {
                $properties[$name] = $this->dereferenceProperty($property, $schema, $insideCommonSchema, 0);
                if ($insideCommonSchema) {
                    $commonSchemaProperties[] = $name;
                }
            }
            $required = [...$required, ...($resolved['required'] ?? [])];
        }

        return [
            'description' => $schema['description'] ?? null,
            'properties' => $properties,
            'required' => array_values(array_unique($required)),
            'commonSchemaProperties' => array_values(array_unique($commonSchemaProperties)),
            'type' => 'object',
        ];
    }

    /**
     * @param array<string, mixed> $branch
     * @param array<string, mixed> $ownSchema
     *
     * @return array{0: array<string, mixed>, 1: bool}
     */
    private function resolveBranch(array $branch, array $ownSchema): array
    {
        if (!isset($branch['$ref'])) {
            return [$branch, false];
        }

        return [$this->resolveRef($branch['$ref'], $ownSchema, false), true];
    }

    /**
     * @param array<string, mixed> $property
     * @param array<string, mixed> $ownSchema
     *
     * @return array<string, mixed>
     */
    private function dereferenceProperty(array $property, array $ownSchema, bool $insideCommonSchema, int $depth): array
    {
        if ($depth > self::MAX_DEPTH) {
            throw new \RuntimeException('$ref cycle detected while dereferencing a schema');
        }

        if (isset($property['$ref'])) {
            $resolved = $this->resolveRef($property['$ref'], $ownSchema, $insideCommonSchema);

            return $this->dereferenceProperty($resolved, $ownSchema, true, $depth + 1);
        }

        if (isset($property['properties']) && is_array($property['properties'])) {
            foreach ($property['properties'] as $name => $nested) {
                $property['properties'][$name] = $this->dereferenceProperty($nested, $ownSchema, $insideCommonSchema, $depth + 1);
            }
        }

        if (isset($property['items']) && is_array($property['items'])) {
            $property['items'] = $this->dereferenceProperty($property['items'], $ownSchema, $insideCommonSchema, $depth + 1);
        }

        foreach (['oneOf', 'anyOf', 'allOf'] as $keyword) {
            if (isset($property[$keyword]) && is_array($property[$keyword])) {
                foreach ($property[$keyword] as $i => $branch) {
                    $property[$keyword][$i] = $this->dereferenceProperty($branch, $ownSchema, $insideCommonSchema, $depth + 1);
                }
            }
        }

        return $property;
    }

    /**
     * @param array<string, mixed> $ownSchema
     *
     * @return array<string, mixed>
     */
    private function resolveRef(string $ref, array $ownSchema, bool $insideCommonSchema): array
    {
        if (str_starts_with($ref, '#')) {
            $document = $insideCommonSchema ? $this->commonSchema : $ownSchema;
            $resolved = $this->resolvePointer($document, substr($ref, 1));
        } elseif (str_starts_with($ref, $this->commonSchemaRefUrl.'#')) {
            $resolved = $this->resolvePointer($this->commonSchema, substr($ref, strlen($this->commonSchemaRefUrl) + 1));
        } else {
            $resolved = null;
        }

        if (null === $resolved) {
            throw new \RuntimeException("Could not resolve \$ref: {$ref}");
        }

        return $resolved;
    }

    /**
     * @param array<string, mixed> $document
     *
     * @return array<string, mixed>|null
     */
    private function resolvePointer(array $document, string $pointer): ?array
    {
        $pointer = ltrim($pointer, '/');
        $current = $document;

        if ('' !== $pointer) {
            foreach (explode('/', $pointer) as $segment) {
                $segment = str_replace(['~1', '~0'], ['/', '~'], $segment);
                if (!is_array($current) || !array_key_exists($segment, $current)) {
                    return null;
                }
                $current = $current[$segment];
            }
        }

        return is_array($current) ? $current : null;
    }
}
