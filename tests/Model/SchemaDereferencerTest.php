<?php

namespace ItkEnter\DataModels\Tests\Model;

use ItkEnter\DataModels\Model\SchemaDereferencer;
use PHPUnit\Framework\TestCase;

final class SchemaDereferencerTest extends TestCase
{
    private const REF_URL = 'https://example.test/common-schema.json';

    public function testMergesAllOfBranchesIntoOneFlatPropertySet(): void
    {
        $commonSchema = [
            'definitions' => [
                'Common' => [
                    'type' => 'object',
                    'properties' => [
                        'id' => ['type' => 'string', 'description' => 'Property. An id'],
                    ],
                ],
            ],
        ];
        $schema = [
            'description' => 'A thing',
            'allOf' => [
                ['$ref' => self::REF_URL.'#/definitions/Common'],
                ['properties' => ['name' => ['type' => 'string', 'description' => 'Property. A name']]],
            ],
            'required' => ['id'],
        ];

        $result = (new SchemaDereferencer($commonSchema, self::REF_URL))->dereference($schema);

        self::assertSame(['id', 'name'], array_keys($result['properties']));
        self::assertSame(['id'], $result['required']);
        self::assertSame(['id'], $result['commonSchemaProperties']);
    }

    public function testResolvesABareFragmentRefFoundInsideTheCommonSchemaAgainstTheCommonSchema(): void
    {
        $commonSchema = [
            'definitions' => [
                'Wrapper' => [
                    'type' => 'object',
                    'properties' => [
                        'id' => ['$ref' => '#/definitions/Id'],
                    ],
                ],
                'Id' => ['type' => 'string', 'description' => 'Property. An id'],
            ],
        ];
        $schema = ['allOf' => [['$ref' => self::REF_URL.'#/definitions/Wrapper']]];

        $result = (new SchemaDereferencer($commonSchema, self::REF_URL))->dereference($schema);

        self::assertSame(['type' => 'string', 'description' => 'Property. An id'], $result['properties']['id']);
    }

    public function testExpandsNestedPropertiesAndItems(): void
    {
        $schema = [
            'properties' => [
                'address' => [
                    'type' => 'object',
                    'properties' => [
                        'street' => ['$ref' => self::REF_URL.'#/definitions/Text'],
                    ],
                ],
                'tags' => [
                    'type' => 'array',
                    'items' => ['$ref' => self::REF_URL.'#/definitions/Text'],
                ],
            ],
        ];
        $commonSchema = ['definitions' => ['Text' => ['type' => 'string', 'description' => 'Property. Free text']]];

        $result = (new SchemaDereferencer($commonSchema, self::REF_URL))->dereference($schema);

        self::assertSame('string', $result['properties']['address']['properties']['street']['type']);
        self::assertSame('string', $result['properties']['tags']['items']['type']);
    }

    public function testThrowsOnAnUnresolvableRef(): void
    {
        $schema = ['allOf' => [['$ref' => self::REF_URL.'#/definitions/DoesNotExist']]];

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Could not resolve $ref');

        (new SchemaDereferencer(['definitions' => []], self::REF_URL))->dereference($schema);
    }

    public function testThrowsOnACircularRefInsteadOfLoopingForever(): void
    {
        $commonSchema = [
            'definitions' => [
                'A' => ['$ref' => '#/definitions/B'],
                'B' => ['$ref' => '#/definitions/A'],
            ],
        ];
        $schema = ['properties' => ['loop' => ['$ref' => self::REF_URL.'#/definitions/A']]];

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('cycle');

        (new SchemaDereferencer($commonSchema, self::REF_URL))->dereference($schema);
    }
}
