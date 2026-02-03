<?php

declare(strict_types=1);

namespace PhpSoftBox\Validator\Rule;

/**
 * Управляющее правило: останавливает проверку поля при первой ошибке.
 */
final class BailValidation extends AbstractControlRule
{
    /**
     * Валидатор не выполняет проверок, так как правило управляющее.
     */
    public function validate(mixed $value, string $field, bool $present, array $data): array
    {
        return [];
    }

    /**
     * Сообщения для bail не используются.
     */
    public function messages(): array
    {
        return [];
    }
}
