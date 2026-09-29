<?php

declare(strict_types=1);

namespace PhpSoftBox\Validator\Tests\Fixtures;

use PhpSoftBox\Validator\Rule\Executor\RuleExecutorInterface;
use PhpSoftBox\Validator\Rule\RuleSpecificationInterface;

/**
 * Executor для тестов: любое значение считает корректным.
 */
final class PassingSpecificationExecutor implements RuleExecutorInterface
{
    public function supports(RuleSpecificationInterface $rule): bool
    {
        return true;
    }

    public function validate(
        RuleSpecificationInterface $rule,
        mixed $value,
        string $field,
        bool $present,
        array $data,
    ): array {
        return [];
    }
}
