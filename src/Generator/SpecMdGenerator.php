<?php

namespace ItkEnter\DataModels\Generator;

use ItkEnter\DataModels\Model\DereferencedModel;
use ItkEnter\DataModels\Model\DescriptionParser;
use Twig\Environment;

/**
 * Generates doc/spec.md: a header, description and
 * version, a property list, the required properties, then notes.yaml.
 *
 * The property/required lists are pre-rendered here, not looped over in
 * the template: Twig's whitespace trimming is easy to get subtly wrong
 * across repeated blocks, and a plain `implode("\n", …)` isn't.
 */
final class SpecMdGenerator
{
    public function __construct(private readonly Environment $twig)
    {
    }

    public function generate(DereferencedModel $model): string
    {
        $names = array_keys($model->properties);
        sort($names);

        $lines = [];
        foreach ($names as $name) {
            $property = $model->properties[$name];
            $parsed = $model->parsedDescription($name);
            $text = null !== $parsed ? $parsed->text : ($property['description'] ?? '');
            $parsedModel = $parsed?->model;
            if (null !== $parsedModel) {
                $text = DescriptionParser::withoutMarker($text, 'Model');
            }
            $type = \is_string($property['type'] ?? null) ? $property['type'] : '*';
            $modelLink = null !== $parsedModel ? " Model: [{$parsedModel}]({$parsedModel})" : '';
            $lines[] = "- `{$name}[{$type}]`: {$text}{$modelLink}";
        }

        $required = $model->required;
        sort($required);
        $requiredLines = array_map(static fn (string $name) => "- `{$name}`", $required);

        $notes = $model->folder->notes ?? [];

        return $this->twig->render('spec.md.twig', [
            'name' => $model->folder->name,
            'version' => (string) ($model->folder->schema['x-version'] ?? $model->folder->schema['$schemaVersion'] ?? ''),
            'description' => $model->description,
            'propertiesList' => implode("\n", $lines),
            'requiredList' => implode("\n", $requiredLines),
            'notesHeader' => $notes['notesHeader'] ?? null,
            'notesMiddle' => $notes['notesMiddle'] ?? null,
            'notesFooter' => $notes['notesFooter'] ?? null,
        ]);
    }
}
