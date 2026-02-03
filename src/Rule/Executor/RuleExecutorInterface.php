<?php

declare(strict_types=1);

namespace PhpSoftBox\Validator\Rule\Executor;

use PhpSoftBox\Validator\Rule\RuleSpecificationInterface;

interface RuleExecutorInterface
{
    public function supports(RuleSpecificationInterface $rule): bool;

    /**
     * @param array<string, mixed> $data
     */
    public function validate(
        RuleSpecificationInterface $rule,
        mixed $value,
        string $field,
        bool $present,
        array $data,
    ): array;
}
