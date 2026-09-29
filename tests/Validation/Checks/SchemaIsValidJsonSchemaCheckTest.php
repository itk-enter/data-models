<?php

namespace ItkEnter\DataModels\Tests\Validation\Checks;

use ItkEnter\DataModels\Validation\Checks\SchemaIsValidJsonSchemaCheck;
use ItkEnter\DataModels\Validation\OpisValidatorFactory;

final class SchemaIsValidJsonSchemaCheckTest extends CheckTestCase
{
    private SchemaIsValidJsonSchemaCheck $check;

    protected function setUp(): void
    {
        parent::setUp();
        $this->check = new SchemaIsValidJsonSchemaCheck(new OpisValidatorFactory($this->repoRoot));
    }

    public function testFailsOnAMalformedSchema(): void
    {
        $errors = $this->check->check($this->fixture('InvalidSchema'), $this->config);

        self::assertNotSame([], $errors, 'a schema with a malformed keyword should fail this check');
    }

    public function testPassesOnPublicToilet(): void
    {
        self::assertSame([], $this->check->check($this->publicToilet(), $this->config));
    }
}
