<?php

namespace ItkEnter\DataModels\Validation\Checks;

use ItkEnter\DataModels\Config;
use ItkEnter\DataModels\Model\ModelFolder;
use ItkEnter\DataModels\Validation\Check;
use ItkEnter\DataModels\Validation\OpisValidatorFactory;
use ItkEnter\DataModels\Validation\ValidationError;
use Opis\JsonSchema\Errors\ErrorFormatter;
use Opis\JsonSchema\Exceptions\SchemaException;

/**
 * examples/example.json validates against schema.json.
 */
final class ExampleValidatesCheck implements Check
{
    public function __construct(private readonly OpisValidatorFactory $validators)
    {
    }

    public function check(ModelFolder $model, Config $config): array
    {
        if (!is_file($model->examplePath())) {
            return [new ValidationError($model->name, 'examples/example.json', 'file is missing')];
        }

        $schema = json_decode(file_get_contents($model->schemaPath()), false, flags: JSON_THROW_ON_ERROR);
        $example = json_decode(file_get_contents($model->examplePath()), false, flags: JSON_THROW_ON_ERROR);

        try {
            $result = $this->validators->create($config)->validate($example, $schema);
        } catch (SchemaException $e) {
            return [new ValidationError($model->name, 'examples/example.json', 'could not validate: '.$e->getMessage())];
        }

        if ($result->isValid()) {
            return [];
        }

        $formatter = new ErrorFormatter();

        return array_map(
            static fn (string $message) => new ValidationError($model->name, 'examples/example.json', $message),
            $formatter->formatFlat($result->error()),
        );
    }
}
