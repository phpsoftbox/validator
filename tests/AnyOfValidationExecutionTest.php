<?php

declare(strict_types=1);

namespace PhpSoftBox\Validator\Tests;

use PhpSoftBox\Validator\Rule\AnyOfValidation;
use PhpSoftBox\Validator\Rule\Executor\RuleExecutorRegistry;
use PhpSoftBox\Validator\Rule\IntValidation;
use PhpSoftBox\Validator\Tests\Fixtures\ContextCapturingRule;
use PhpSoftBox\Validator\Tests\Fixtures\DirectCallFailingSpecificationRule;
use PhpSoftBox\Validator\Tests\Fixtures\PassingSpecificationExecutor;
use PhpSoftBox\Validator\Validator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(Validator::class)]
#[CoversClass(AnyOfValidation::class)]
#[CoversMethod(Validator::class, 'validate')]
#[CoversMethod(AnyOfValidation::class, 'rules')]
final class AnyOfValidationExecutionTest extends TestCase
{
    /**
     * Проверим, что specification-правило внутри anyOf выполняется через executor, а не прямым вызовом.
     *
     * @see Validator::validate()
     * @see AnyOfValidation::rules()
     */
    #[Test]
    public function executesSpecificationRuleThroughExecutor(): void
    {
        $registry = new RuleExecutorRegistry();

        $registry->register(DirectCallFailingSpecificationRule::class, new PassingSpecificationExecutor());
        $validator = new Validator($registry);

        $result = $validator->validate(
            data: ['value' => 'abc'],
            rules: ['value' => [new AnyOfValidation(new IntValidation(), new DirectCallFailingSpecificationRule())]],
        );

        self::assertFalse($result->hasErrors());
    }

    /**
     * Проверим, что вложенные правила anyOf получают контекст проверки.
     *
     * @see Validator::validate()
     * @see AnyOfValidation::rules()
     */
    #[Test]
    public function passesRuntimeStateToNestedRules(): void
    {
        $rule      = new ContextCapturingRule();
        $validator = new Validator();

        $result = $validator->validate(
            data: ['value' => 'abc'],
            rules: ['value' => [new AnyOfValidation($rule)]],
            context: 'request-context',
        );

        self::assertFalse($result->hasErrors());
        self::assertSame('request-context', $rule->seenContext);
    }
}
