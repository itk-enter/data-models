<?php

namespace ItkEnter\DataModels\Validation\Checks;

use ItkEnter\DataModels\Config;
use ItkEnter\DataModels\Model\ModelFolder;
use ItkEnter\DataModels\Validation\Check;
use ItkEnter\DataModels\Validation\ValidationError;

/**
 * $id, x-model-schema and x-license-url equal the URLs derived
 * from config.yaml for this model's folder.
 */
final class SelfReferencesCheck implements Check
{
    public function check(ModelFolder $model, Config $config): array
    {
        $expected = [
            '$id' => $config->modelSchemaUrl($model->subject, $model->name),
            'x-model-schema' => $config->modelSchemaUrl($model->subject, $model->name),
            'x-license-url' => $config->modelLicenseUrl($model->subject, $model->name),
        ];

        $errors = [];
        foreach ($expected as $key => $expectedValue) {
            $actual = $model->schema[$key] ?? null;
            if ($actual !== $expectedValue) {
                $errors[] = new ValidationError($model->name, 'schema.json', "{$key} is '{$actual}', expected '{$expectedValue}'");
            }
        }

        return $errors;
    }
}
