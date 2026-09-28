<?php

namespace ItkEnter\DataModels\Validation\Checks;

use ItkEnter\DataModels\Config;
use ItkEnter\DataModels\Model\ModelFolder;
use ItkEnter\DataModels\Validation\Check;
use ItkEnter\DataModels\Validation\OpisValidatorFactory;
use ItkEnter\DataModels\Validation\ValidationError;
use Opis\JsonSchema\Exceptions\SchemaException;
use Opis\JsonSchema\Exceptions\UnresolvedReferenceException;

/**
 * Check 2: every `$ref` resolves against vendor-assets/common-schema.json,
 * offline. `allOf` evaluates every branch regardless of the instance, so
 * validating an empty object still exercises every `$ref` reachable from
 * the schema's top level.
 */
final class RefsResolveCheck implements Check
{
    public function __construct(private readonly OpisValidatorFactory $validators)
    {
    }

    public function check(ModelFolder $model, Config $config): array
    {
        $schema = json_decode(file_get_contents($model->schemaPath()), false, flags: JSON_THROW_ON_ERROR);

        try {
            $this->validators->create($config)->validate(new \stdClass(), $schema);
        } catch (UnresolvedReferenceException $e) {
            return [new ValidationError($model->name, 'schema.json', 'unresolved $ref: '.$e->getMessage())];
        } catch (SchemaException) {
            // Not this check's concern.
        }

        return [];
    }
}
