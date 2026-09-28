<?php

namespace ItkEnter\DataModels\Validation\Checks;

use ItkEnter\DataModels\Config;
use ItkEnter\DataModels\Model\ModelFolder;
use ItkEnter\DataModels\Validation\Check;
use ItkEnter\DataModels\Validation\ValidationError;

/**
 * Check 5: $schemaVersion equals x-version, and is a semver string (D6).
 */
final class VersionConsistencyCheck implements Check
{
    public function check(ModelFolder $model, Config $config): array
    {
        $schemaVersion = $model->schema['$schemaVersion'] ?? null;
        $xVersion = $model->schema['x-version'] ?? null;
        $errors = [];

        if (null === $schemaVersion) {
            $errors[] = new ValidationError($model->name, 'schema.json', 'missing $schemaVersion');
        } elseif (!preg_match('/^\d+\.\d+\.\d+$/', $schemaVersion)) {
            $errors[] = new ValidationError($model->name, 'schema.json', "\$schemaVersion '{$schemaVersion}' is not a semver string");
        }

        if (null === $xVersion) {
            $errors[] = new ValidationError($model->name, 'schema.json', 'missing x-version');
        }

        if (null !== $schemaVersion && null !== $xVersion && $schemaVersion !== $xVersion) {
            $errors[] = new ValidationError($model->name, 'schema.json', "\$schemaVersion ({$schemaVersion}) does not equal x-version ({$xVersion})");
        }

        return $errors;
    }
}
