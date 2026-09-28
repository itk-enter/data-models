<?php

namespace ItkEnter\DataModels\Model;

final class ParsedDescription
{
    /**
     * @param string[]|null $enum
     */
    public function __construct(
        public readonly ?string $ngsiType,
        public readonly ?string $model,
        public readonly ?string $units,
        public readonly ?array $enum,
        public readonly string $text,
    ) {
    }
}
