<?php

namespace ItkEnter\DataModels\Site;

final class ModelPageData
{
    public function __construct(
        public readonly string $subject,
        public readonly string $name,
        public readonly string $status,
        public readonly string $version,
        public readonly bool $isLatest,
        public readonly string $versionsLine,
        public readonly string $specBody,
        public readonly string $contextUrl,
        public readonly string $rootPath,
    ) {
    }
}
