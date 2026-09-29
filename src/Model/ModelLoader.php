<?php

namespace ItkEnter\DataModels\Model;

/**
 * Dereferences a ModelFolder's schema.json into a
 * DereferencedModel, against the vendored common-schema.json.
 */
final class ModelLoader
{
    public function __construct(private readonly string $repoRoot, private readonly string $commonSchemaRefUrl)
    {
    }

    public function load(ModelFolder $folder): DereferencedModel
    {
        $commonSchema = json_decode(
            file_get_contents($this->repoRoot.'/vendor-assets/common-schema.json'),
            true,
            flags: JSON_THROW_ON_ERROR,
        );

        $dereferencer = new SchemaDereferencer($commonSchema, $this->commonSchemaRefUrl);
        $result = $dereferencer->dereference($folder->schema);

        return new DereferencedModel(
            $folder,
            $result['description'] ?? '',
            $result['properties'],
            $result['required'],
            $result['commonSchemaProperties'],
        );
    }
}
