<?php

declare(strict_types=1);

namespace PhpSoftBox\Validator\Tests;

use PhpSoftBox\Validator\Exception\RuleExecutorNotFoundException;
use PhpSoftBox\Validator\Rule\AbstractRule;
use PhpSoftBox\Validator\Rule\Executor\RuleExecutorInterface;
use PhpSoftBox\Validator\Rule\Executor\RuleExecutorRegistry;
use PhpSoftBox\Validator\Rule\RuleSpecificationInterface;
use PhpSoftBox\Validator\ValidationViolation;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(RuleExecutorRegistry::class)]
final class RuleExecutorRegistryTest extends TestCase
{
    /**
     * Проверяет резолв executor-а по прямому биндингу rule class -> executor.
     */
    #[Test]
    public function resolvesExecutorByRuleClassBinding(): void
    {
        $registry = new RuleExecutorRegistry();
        $executor = new ExactExecutor();
        $rule     = new DummyRule();

        $registry->register(DummyRule::class, $executor);

        self::assertSame($executor, $registry->resolve($rule));
    }

    /**
     * Проверяет fallback-резолв через supports(...), если прямого биндинга нет.
     */
    #[Test]
    public function resolvesExecutorViaSupportsFallback(): void
    {
        $fallback = new SupportsExecutor();

        $registry = new RuleExecutorRegistry([
                    [OtherRule::class, $fallback],
                ]);

        self::assertSame($fallback, $registry->resolve(new DummyRule()));
    }

    /**
     * Проверяет ошибку при отсутствии executor-а для правила.
     */
    #[Test]
    public function throwsWhenExecutorIsMissing(): void
    {
        $registry = new RuleExecutorRegistry();

        $this->expectException(RuleExecutorNotFoundException::class);
        $this->expectExceptionMessage('No executor registered for rule');

        $registry->resolve(new DummyRule());
    }
}

final class DummyRule extends AbstractRule implements RuleSpecificationInterface
{
    public function validate(mixed $value, string $field, bool $present, array $data): array
    {
        return [];
    }

    public function messages(): array
    {
        return [];
    }
}

final class OtherRule extends AbstractRule implements RuleSpecificationInterface
{
    public function validate(mixed $value, string $field, bool $present, array $data): array
    {
        return [];
    }

    public function messages(): array
    {
        return [];
    }
}

final class ExactExecutor implements RuleExecutorInterface
{
    public function supports(RuleSpecificationInterface $rule): bool
    {
        return false;
    }

    public function validate(
        RuleSpecificationInterface $rule,
        mixed $value,
        string $field,
        bool $present,
        array $data,
    ): array {
        return [new ValidationViolation('exact')];
    }
}

final class SupportsExecutor implements RuleExecutorInterface
{
    public function supports(RuleSpecificationInterface $rule): bool
    {
        return $rule instanceof DummyRule;
    }

    public function validate(
        RuleSpecificationInterface $rule,
        mixed $value,
        string $field,
        bool $present,
        array $data,
    ): array {
        return [new ValidationViolation('supports')];
    }
}
