<?php

namespace ItkEnter\DataModels\Validation;

final class ValidationError
{
    public function __construct(
        public readonly string $model,
        public readonly string $file,
        public readonly string $message,
    ) {
    }

    public function __toString(): string
    {
        return "{$this->model} ({$this->file}): {$this->message}";
    }
}
