<?php

namespace ItkEnter\DataModels\Tests\Validation\Checks;

use ItkEnter\DataModels\Validation\Checks\RefsResolveCheck;
use ItkEnter\DataModels\Validation\OpisValidatorFactory;

final class RefsResolveCheckTest extends CheckTestCase
{
    private RefsResolveCheck $check;

    protected function setUp(): void
    {
        parent::setUp();
        $this->check = new RefsResolveCheck(new OpisValidatorFactory($this->repoRoot));
    }

    public function testFailsOnAnUnresolvableRef(): void
    {
        $errors = $this->check->check($this->fixture('UnresolvedRef'), $this->config);

        self::assertNotSame([], $errors, 'a $ref that does not resolve should fail this check');
    }

    public function testPassesOnPublicToilet(): void
    {
        self::assertSame([], $this->check->check($this->publicToilet(), $this->config));
    }
}
