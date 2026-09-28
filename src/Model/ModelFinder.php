<?php

namespace ItkEnter\DataModels\Model;

use Symfony\Component\Yaml\Yaml;

/**
 * Discovers model folders: <Subject>/<Model>/schema.json under the repo
 * root. A subject folder is any top-level directory whose name matches
 * SDM's dataModel.* convention.
 */
final class ModelFinder
{
    public function __construct(private readonly string $repoRoot)
    {
    }

    /**
     * @return ModelFolder[]
     */
    public function all(): array
    {
        $models = [];
        foreach (glob($this->repoRoot.'/dataModel.*', GLOB_ONLYDIR) as $subjectPath) {
            $subject = basename($subjectPath);
            foreach (glob($subjectPath.'/*/schema.json') as $schemaPath) {
                $name = basename(dirname($schemaPath));
                $models[] = $this->load($subject, $name, dirname($schemaPath));
            }
        }

        return $models;
    }

    public function find(string $name): ?ModelFolder
    {
        foreach ($this->all() as $model) {
            if ($model->name === $name) {
                return $model;
            }
        }

        return null;
    }

    private function load(string $subject, string $name, string $path): ModelFolder
    {
        $schema = json_decode(file_get_contents($path.'/schema.json'), true, flags: JSON_THROW_ON_ERROR);

        $notesPath = $path.'/notes.yaml';
        $notes = is_file($notesPath) ? Yaml::parseFile($notesPath) : null;

        $examplePath = $path.'/examples/example.json';
        $example = is_file($examplePath)
            ? json_decode(file_get_contents($examplePath), true, flags: JSON_THROW_ON_ERROR)
            : null;

        return new ModelFolder($subject, $name, $path, $schema, $notes, $example);
    }
}
