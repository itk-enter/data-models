<?php

namespace ItkEnter\DataModels\Tests\Validation\Checks;

use ItkEnter\DataModels\Validation\Checks\DescriptionConventionCheck;

final class DescriptionConventionCheckTest extends CheckTestCase
{
    private DescriptionConventionCheck $check;

    protected function setUp(): void
    {
        parent::setUp();
        $this->check = new DescriptionConventionCheck();
    }

    public function testFailsWhenADescriptionHasNoNgsiTypePrefix(): void
    {
        $model = $this->fixture('DescriptionConvention', [
            'properties' => [
                'foo' => ['type' => 'string', 'description' => 'Just some text, no prefix.'],
            ],
        ]);

        $errors = $this->check->check($model, $this->config);

        self::assertNotSame([], $errors);
    }

    public function testFailsWhenTheEnumMarkerDoesNotMatchTheSchemaEnum(): void
    {
        $model = $this->fixture('DescriptionConvention', [
            'properties' => [
                'foo' => [
                    'type' => 'string',
                    'description' => "Property. Enum:'a, b'.",
                    'enum' => ['a', 'c'],
                ],
            ],
        ]);

        $errors = $this->check->check($model, $this->config);

        self::assertNotSame([], $errors);
    }

    public function testPassesOnPublicToilet(): void
    {
        self::assertSame([], $this->check->check($this->publicToilet(), $this->config));
    }
}
