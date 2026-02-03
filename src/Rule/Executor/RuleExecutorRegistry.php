<?php

declare(strict_types=1);

namespace PhpSoftBox\Validator\Rule\Executor;

use PhpSoftBox\Validator\Exception\RuleExecutorNotFoundException;
use PhpSoftBox\Validator\Rule\RuleSpecificationInterface;

use function get_debug_type;
use function sprintf;

final class RuleExecutorRegistry implements RuleExecutorRegistryInterface
{
    /**
     * @var array<class-string<RuleSpecificationInterface>, RuleExecutorInterface>
     */
    private array $executorsByRuleClass = [];

    /**
     * @param iterable<array{0: class-string<RuleSpecificationInterface>, 1: RuleExecutorInterface}> $bindings
     */
    public function __construct(iterable $bindings = [])
    {
        foreach ($bindings as $binding) {
            $this->register($binding[0], $binding[1]);
        }
    }

    public function register(string $ruleClass, RuleExecutorInterface $executor): void
    {
        $this->executorsByRuleClass[$ruleClass] = $executor;
    }

    public function resolve(RuleSpecificationInterface $rule): RuleExecutorInterface
    {
        $ruleClass = $rule::class;

        if (isset($this->executorsByRuleClass[$ruleClass])) {
            return $this->executorsByRuleClass[$ruleClass];
        }

        foreach ($this->executorsByRuleClass as $executor) {
            if ($executor->supports($rule)) {
                return $executor;
            }
        }

        throw new RuleExecutorNotFoundException(sprintf(
            'No executor registered for rule "%s".',
            get_debug_type($rule),
        ));
    }
}
