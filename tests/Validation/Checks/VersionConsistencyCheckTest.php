<?php

namespace ItkEnter\DataModels\Tests\Validation\Checks;

use ItkEnter\DataModels\Validation\Checks\VersionConsistencyCheck;

final class VersionConsistencyCheckTest extends CheckTestCase
{
    private VersionConsistencyCheck $check;

    protected function setUp(): void
    {
        parent::setUp();
        $this->check = new VersionConsistencyCheck();
    }

    public function testFailsWhenSchemaVersionAndXVersionDisagree(): void
    {
        $model = $this->fixture('VersionConsistency', [
            '$schemaVersion' => '0.0.1',
            'x-version' => '0.0.2',
        ]);

        self::assertNotSame([], $this->check->check($model, $this->config));
    }

    public function testFailsOnANonSemverVersion(): void
    {
        $model = $this->fixture('VersionConsistency', [
            '$schemaVersion' => 'v1',
            'x-version' => 'v1',
        ]);

        self::assertNotSame([], $this->check->check($model, $this->config));
    }

    public function testPassesOnPublicToilet(): void
    {
        self::assertSame([], $this->check->check($this->publicToilet(), $this->config));
    }
}
