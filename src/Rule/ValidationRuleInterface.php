<?php

declare(strict_types=1);

namespace PhpSoftBox\Validator\Rule;

use PhpSoftBox\Validator\ValidationViolation;

/**
 * Контракт для правил валидации.
 */
interface ValidationRuleInterface
{
    /**
     * @return list<ValidationViolation>
     */
    public function validate(mixed $value, string $field, bool $present, array $data): array;

    public function isRequired(): bool;

    public function requiredViolation(array $data, mixed $context, string $field): ?ValidationViolation;

    public function isNullable(): bool;

    public function shouldExclude(array $data, mixed $context, string $field): bool;

    /**
     * @return array<string, string>
     */
    public function messages(): array;

    /**
     * @return array<string, string>
     */
    public function customMessages(): array;
}
