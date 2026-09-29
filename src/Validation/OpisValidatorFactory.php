<?php

namespace ItkEnter\DataModels\Validation;

use ItkEnter\DataModels\Config;
use Opis\JsonSchema\Parsers\SchemaParser;
use Opis\JsonSchema\Resolvers\SchemaResolver;
use Opis\JsonSchema\SchemaLoader;
use Opis\JsonSchema\Validator;

/**
 * Builds an opis/json-schema Validator whose resolver maps SDM's
 * common-schema URI to the pinned local copy in vendor-assets/, so
 * validation is offline and reproducible.
 */
final class OpisValidatorFactory
{
    public function __construct(private readonly string $repoRoot)
    {
    }

    public function create(Config $config): Validator
    {
        $resolver = new SchemaResolver();
        $resolver->registerFile($config->commonSchemaRefUrl, $this->repoRoot.'/vendor-assets/common-schema.json');

        $validator = new Validator(new SchemaLoader(new SchemaParser(), $resolver));
        $validator->setMaxErrors(1000);

        return $validator;
    }
}
