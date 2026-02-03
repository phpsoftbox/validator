<?php

declare(strict_types=1);

namespace PhpSoftBox\Validator;

interface ValidatorInterface
{
    /**
     * @param array<string, mixed> $data
     * @param array<string, mixed> $rules
     * @param array<string, mixed> $messages
     * @param array<string, string> $attributes
     */
    public function validate(
        array $data,
        array $rules,
        array $messages = [],
        array $attributes = [],
        ?ValidationOptions $options = null,
        mixed $context = null,
    ): ValidationResult;
}
