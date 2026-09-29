<?php

declare(strict_types=1);

namespace PhpSoftBox\Validator\Tests\Fixtures;

use PhpSoftBox\Validator\Rule\AbstractRule;
use PhpSoftBox\Validator\ValidationViolation;

/**
 * Правило для тестов: запоминает контекст и route-параметр, доступные во время проверки.
 */
final class ContextCapturingRule extends AbstractRule
{
    public mixed $seenContext = null;

    public mixed $seenRouteId = null;

    public function validate(mixed $value, string $field, bool $present, array $data): array
    {
        $this->seenContext = $this->context();
        $this->seenRouteId = $this->route('id');

        return $this->seenContext === null ? [new ValidationViolation('context_missing')] : [];
    }

    public function messages(): array
    {
        return ['context_missing' => 'Контекст недоступен.'];
    }

    /**
     * Текущий контекст правила (вне проверки должен быть сброшен).
     */
    public function currentContext(): mixed
    {
        return $this->context();
    }

    /**
     * Текущий route-параметр id (вне проверки должен быть сброшен).
     */
    public function currentRouteId(): mixed
    {
        return $this->route('id');
    }
}
