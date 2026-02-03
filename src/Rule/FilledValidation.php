<?php

declare(strict_types=1);

namespace PhpSoftBox\Validator\Rule;

use PhpSoftBox\Validator\ValidationEnum;
use PhpSoftBox\Validator\ValidationViolation;

/**
 * Проверяет, что поле не пустое, если оно присутствует.
 */
final class FilledValidation extends AbstractControlRule
{
    /**
     * Проверяет, что поле не пустое, если оно присутствует.
     */
    public function validate(mixed $value, string $field, bool $present, array $data): array
    {
        if ($this->isEmptyValue($value)) {
            return [new ValidationViolation(ValidationEnum::FILLED->value)];
        }

        return [];
    }

    /**
     * Сообщения для filled‑правила.
     */
    public function messages(): array
    {
        return [
            ValidationEnum::FILLED->value => 'Поле {field} не должно быть пустым.',
        ];
    }
}
