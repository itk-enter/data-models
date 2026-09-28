<?php

namespace ItkEnter\DataModels\Validation\Checks;

use ItkEnter\DataModels\Config;
use ItkEnter\DataModels\Model\ModelFolder;
use ItkEnter\DataModels\Validation\Check;
use ItkEnter\DataModels\Validation\OpisValidatorFactory;
use ItkEnter\DataModels\Validation\ValidationError;
use Opis\JsonSchema\Exceptions\ParseException;
use Opis\JsonSchema\Exceptions\SchemaException;

/**
 * Check 1: schema.json is valid JSON Schema 2020-12 (structurally — opis
 * can parse it). Unresolved `$ref`s are RefsResolveCheck's concern, not
 * this one's.
 */
final class SchemaIsValidJsonSchemaCheck implements Check
{
    public function __construct(private readonly OpisValidatorFactory $validators)
    {
    }

    public function check(ModelFolder $model, Config $config): array
    {
        $schema = json_decode(file_get_contents($model->schemaPath()), false, flags: JSON_THROW_ON_ERROR);

        try {
            $this->validators->create($config)->validate(new \stdClass(), $schema);
        } catch (ParseException $e) {
            return [new ValidationError($model->name, 'schema.json', 'not valid JSON Schema: '.$e->getMessage())];
        } catch (SchemaException) {
            // Not this check's concern.
        }

        return [];
    }
}
