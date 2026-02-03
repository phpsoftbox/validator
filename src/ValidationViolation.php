<?php

declare(strict_types=1);

namespace PhpSoftBox\Validator;

final readonly class ValidationViolation
{
    /**
     * @param array<string, mixed> $params
     */
    public function __construct(
        public string $rule,
        public array $params = [],
    ) {
    }
}
