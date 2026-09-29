<?php

namespace ItkEnter\DataModels\Generator;

use ItkEnter\DataModels\Model\DereferencedModel;

/**
 * Generates schema.sql: a PHP port of
 * `generate_sql_schema`'s model.yaml-to-PostgreSQL type mapping, from
 * https://github.com/smart-data-models/data-models/blob/master/pysmartdatamodels/pysmartdatamodels/pysmartdatamodels.py,
 * © the FIWARE Foundation and contributors to the Smart Data Models
 * program, Apache License 2.0.
 *
 * `id` is always `TEXT PRIMARY KEY`, matching the original; unlike the
 * original, a property that reaches none of the type/format/enum/oneOf
 * branches still gets a `JSON` column instead of being silently dropped.
 */
final class SchemaSqlGenerator
{
    private const TYPE_MAPPING = [
        'string' => 'TEXT',
        'integer' => 'INTEGER',
        'number' => 'NUMERIC',
        'boolean' => 'BOOLEAN',
        'object' => 'JSON',
        'array' => 'JSON',
    ];

    private const FORMAT_MAPPING = [
        'date-time' => 'TIMESTAMP',
        'date' => 'DATE',
        'time' => 'TIME',
        'uri' => 'TEXT',
        'email' => 'TEXT',
        'idn-email' => 'TEXT',
        'hostname' => 'TEXT',
        'duration' => 'TEXT',
    ];

    public function generate(DereferencedModel $model): string
    {
        $entity = $model->folder->name;
        $columns = [];
        $enumTypes = [];

        foreach ($model->properties as $name => $property) {
            if ('id' === $name) {
                $columns[$name] = 'TEXT PRIMARY KEY';
                continue;
            }

            if (isset($property['format'])) {
                $columns[$name] = self::FORMAT_MAPPING[$property['format']] ?? 'TEXT';
            } elseif (isset($property['enum'])) {
                $typeName = ('type' === $name ? $entity : $name).'_type';
                $enumTypes[$typeName] = array_map(strval(...), $property['enum']);
                $columns[$name] = $typeName;
            } elseif (isset($property['type'])) {
                $columns[$name] = self::TYPE_MAPPING[$property['type']] ?? 'JSON';
            } else {
                $columns[$name] = 'JSON';
            }
        }

        ksort($columns);

        $sql = "/* (Beta) Export of data model {$entity} of the subject {$model->folder->subject} for a PostgreSQL database.".
            " Pending translation of enumerations and multityped attributes */\n";

        foreach ($enumTypes as $typeName => $values) {
            $quoted = implode(', ', array_map(
                static fn (string $v) => "'".str_replace("'", "''", $v)."'",
                $values,
            ));
            $sql .= "CREATE TYPE {$typeName} AS ENUM ({$quoted});\n";
        }

        $columnDefinitions = [];
        foreach ($columns as $name => $type) {
            $columnDefinitions[] = "{$name} {$type}";
        }

        $sql .= "CREATE TABLE {$entity} (".implode(', ', $columnDefinitions).');';

        return $sql;
    }
}
