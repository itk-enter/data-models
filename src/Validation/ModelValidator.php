<?php

namespace ItkEnter\DataModels\Validation;

use ItkEnter\DataModels\Config;
use ItkEnter\DataModels\Model\ModelFolder;
use ItkEnter\DataModels\Validation\Checks\DescriptionConventionCheck;
use ItkEnter\DataModels\Validation\Checks\ExampleValidatesCheck;
use ItkEnter\DataModels\Validation\Checks\RefsResolveCheck;
use ItkEnter\DataModels\Validation\Checks\SchemaIsValidJsonSchemaCheck;
use ItkEnter\DataModels\Validation\Checks\SelfReferencesCheck;
use ItkEnter\DataModels\Validation\Checks\VersionConsistencyCheck;

/**
 * Runs every validation check against a model.
 */
final class ModelValidator
{
    /** @var Check[] */
    private readonly array $checks;

    public function __construct(string $repoRoot)
    {
        $opisValidators = new OpisValidatorFactory($repoRoot);

        $this->checks = [
            new SchemaIsValidJsonSchemaCheck($opisValidators),
            new RefsResolveCheck($opisValidators),
            new ExampleValidatesCheck($opisValidators),
            new DescriptionConventionCheck(),
            new VersionConsistencyCheck(),
            new SelfReferencesCheck(),
        ];
    }

    /**
     * @return ValidationError[]
     */
    public function validate(ModelFolder $model, Config $config): array
    {
        $errors = [];
        foreach ($this->checks as $check) {
            $errors = [...$errors, ...$check->check($model, $config)];
        }

        return $errors;
    }
}
