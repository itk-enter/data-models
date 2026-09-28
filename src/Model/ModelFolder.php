<?php

namespace ItkEnter\DataModels\Model;

/**
 * One model's folder: its subject and name, and the source files a
 * validator or generator reads. Loaded as-is; nothing here is
 * dereferenced or parsed beyond decoding the source formats.
 */
final class ModelFolder
{
    public readonly string $subject;
    public readonly string $name;
    public readonly string $path;

    /** @var array<string, mixed> */
    public readonly array $schema;

    /** @var array<string, mixed>|null */
    public readonly ?array $notes;

    /** @var array<string, mixed>|null */
    public readonly ?array $example;

    /**
     * @param array<string, mixed>      $schema
     * @param array<string, mixed>|null $notes
     * @param array<string, mixed>|null $example
     */
    public function __construct(string $subject, string $name, string $path, array $schema, ?array $notes, ?array $example)
    {
        $this->subject = $subject;
        $this->name = $name;
        $this->path = $path;
        $this->schema = $schema;
        $this->notes = $notes;
        $this->example = $example;
    }

    public function schemaPath(): string
    {
        return $this->path.'/schema.json';
    }

    public function examplePath(): string
    {
        return $this->path.'/examples/example.json';
    }

    /**
     * The properties this model itself defines, as {name => propertySchema},
     * excluding common-schema properties pulled in through a `$ref`. SDM
     * schemas either declare `properties` directly or nest them in an
     * `allOf` branch alongside `$ref`s to common-schema definitions.
     *
     * @return array<string, array<string, mixed>>
     */
    public function ownProperties(): array
    {
        $properties = $this->schema['properties'] ?? [];

        foreach ($this->schema['allOf'] ?? [] as $branch) {
            if (!isset($branch['$ref']) && isset($branch['properties'])) {
                $properties += $branch['properties'];
            }
        }

        return $properties;
    }
}
