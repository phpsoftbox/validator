<?php

declare(strict_types=1);

namespace PhpSoftBox\Validator\Tests\Fixtures;

use PhpSoftBox\Validator\Rule\AbstractRule;
use PhpSoftBox\Validator\Rule\RuleSpecificationInterface;
use PhpSoftBox\Validator\ValidationViolation;

/**
 * Specification-правило для тестов: прямой вызов validate() всегда возвращает ошибку,
 * успех возможен только через executor.
 */
final class DirectCallFailingSpecificationRule extends AbstractRule implements RuleSpecificationInterface
{
    public function validate(mixed $value, string $field, bool $present, array $data): array
    {
        return [new ValidationViolation('direct_call')];
    }

    public function messages(): array
    {
        return ['direct_call' => 'Правило вызвано напрямую.'];
    }
}
