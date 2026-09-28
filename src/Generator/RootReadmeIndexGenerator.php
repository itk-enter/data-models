<?php

namespace ItkEnter\DataModels\Generator;

use ItkEnter\DataModels\Config;
use ItkEnter\DataModels\Model\DereferencedModel;

/**
 * Generates the root README.md's model index (phase 4, point 8): a table
 * of subject, model, version, status and links, written between the
 * `<!-- model-index:start -->` / `<!-- model-index:end -->` markers.
 */
final class RootReadmeIndexGenerator
{
    private const START_MARKER = '<!-- model-index:start -->';
    private const END_MARKER = '<!-- model-index:end -->';

    public function __construct(private readonly Config $config)
    {
    }

    /**
     * @param DereferencedModel[] $models
     */
    public function update(string $readme, array $models): string
    {
        $start = strpos($readme, self::START_MARKER);
        $end = strpos($readme, self::END_MARKER);
        if (false === $start || false === $end) {
            throw new \RuntimeException('README.md is missing the model-index markers');
        }

        $before = substr($readme, 0, $start + \strlen(self::START_MARKER));
        $after = substr($readme, $end);

        return $before."\n\n".$this->table($models)."\n\n".$after;
    }

    /**
     * @param DereferencedModel[] $models
     */
    private function table(array $models): string
    {
        if ([] === $models) {
            return 'No models have been imported yet (see `data-models-PLAN.md`, phase 2).';
        }

        usort($models, static fn (DereferencedModel $a, DereferencedModel $b) => $a->folder->name <=> $b->folder->name);

        $rows = ['| Subject | Model | Version | Status | Links |', '| --- | --- | --- | --- | --- |'];
        foreach ($models as $model) {
            $folder = $model->folder;
            $version = (string) ($folder->schema['x-version'] ?? $folder->schema['$schemaVersion'] ?? '');
            $status = $folder->notes['status'] ?? 'own model';
            $modelPageUrl = "{$this->config->pagesUrl}/{$folder->subject}/{$folder->name}/";
            $rows[] = "| {$folder->subject} | {$folder->name} | {$version} | {$status} | [Spec]({$modelPageUrl}) |";
        }

        return implode("\n", $rows);
    }
}
