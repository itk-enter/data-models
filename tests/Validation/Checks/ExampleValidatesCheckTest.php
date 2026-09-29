<?php

namespace ItkEnter\DataModels\Tests\Validation\Checks;

use ItkEnter\DataModels\Validation\Checks\ExampleValidatesCheck;
use ItkEnter\DataModels\Validation\OpisValidatorFactory;

final class ExampleValidatesCheckTest extends CheckTestCase
{
    private ExampleValidatesCheck $check;

    protected function setUp(): void
    {
        parent::setUp();
        $this->check = new ExampleValidatesCheck(new OpisValidatorFactory($this->repoRoot));
    }

    public function testFailsWhenTheExampleDoesNotMatchTheSchema(): void
    {
        $errors = $this->check->check($this->fixture('RejectsExample'), $this->config);

        self::assertNotSame([], $errors, 'an example with an invalid enum value should fail this check');
    }

    public function testPassesOnPublicToilet(): void
    {
        self::assertSame([], $this->check->check($this->publicToilet(), $this->config));
    }
}
