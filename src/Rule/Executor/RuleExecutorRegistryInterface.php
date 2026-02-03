<?php

declare(strict_types=1);

namespace PhpSoftBox\Validator\Rule\Executor;

use PhpSoftBox\Validator\Rule\RuleSpecificationInterface;

interface RuleExecutorRegistryInterface
{
    /**
     * @param class-string<RuleSpecificationInterface> $ruleClass
     */
    public function register(string $ruleClass, RuleExecutorInterface $executor): void;

    public function resolve(RuleSpecificationInterface $rule): RuleExecutorInterface;
}
