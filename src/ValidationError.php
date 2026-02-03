<?php

declare(strict_types=1);

namespace PhpSoftBox\Validator;

final readonly class ValidationError
{
    /**
     * @param array<string, mixed> $params
     */
    public function __construct(
        public string $field,
        public string $rule,
        public string $message,
        public array $params = [],
    ) {
    }
}
