<?php

namespace ItkEnter\DataModels\Tests\Validation\Checks;

use ItkEnter\DataModels\Model\ModelFolder;
use ItkEnter\DataModels\Validation\Checks\SelfReferencesCheck;

final class SelfReferencesCheckTest extends CheckTestCase
{
    private SelfReferencesCheck $check;

    protected function setUp(): void
    {
        parent::setUp();
        $this->check = new SelfReferencesCheck();
    }

    public function testFailsWhenSelfReferencesStillPointAtSdm(): void
    {
        $model = new ModelFolder(
            'dataModel.PointOfInterest',
            'PublicToilet',
            $this->repoRoot.'/tests/fixtures/Checks/SelfReferences',
            [
                '$id' => 'https://smart-data-models.github.io/dataModel.PointOfInterest/PublicToilet/schema.json',
                'x-model-schema' => 'https://smart-data-models.github.io/dataModel.PointOfInterest/PublicToilet/schema.json',
                'x-license-url' => 'https://github.com/smart-data-models/dataModel.PointOfInterest/blob/master/PublicToilet/LICENSE.md',
            ],
            null,
            null,
        );

        self::assertNotSame([], $this->check->check($model, $this->config));
    }

    public function testPassesOnPublicToilet(): void
    {
        self::assertSame([], $this->check->check($this->publicToilet(), $this->config));
    }
}
