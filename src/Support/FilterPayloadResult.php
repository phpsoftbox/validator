<?php

declare(strict_types=1);

namespace PhpSoftBox\Validator\Support;

final readonly class FilterPayloadResult
{
    /**
     * @param array<string, mixed> $payload
     * @param array<string, list<string>> $errors
     */
    public function __construct(
        public array $payload,
        public array $errors = [],
    ) {
    }
}
