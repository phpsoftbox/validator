<?php

declare(strict_types=1);

namespace PhpSoftBox\Validator\Tests;

use PhpSoftBox\Validator\Rule\AbstractRule;
use PhpSoftBox\Validator\Tests\Fixtures\ContextCapturingRule;
use PhpSoftBox\Validator\Validator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(AbstractRule::class)]
#[CoversMethod(AbstractRule::class, 'setRuntimeState')]
#[CoversMethod(AbstractRule::class, 'resetRuntimeState')]
#[CoversMethod(Validator::class, 'validate')]
final class RuleRuntimeStateTest extends TestCase
{
    /**
     * Проверим, что во время проверки правило видит данные и контекст, а после неё не удерживает payload запроса.
     *
     * @see Validator::validate()
     * @see AbstractRule::setRuntimeState()
     * @see AbstractRule::resetRuntimeState()
     */
    #[Test]
    public function releasesRuntimeStateAfterValidation(): void
    {
        $rule      = new ContextCapturingRule();
        $validator = new Validator();

        $validator->validate(
            data: ['value' => 'abc', '_route_params' => ['id' => 42]],
            rules: ['value' => [$rule]],
            context: 'request-context',
        );

        // Во время проверки данные были доступны.
        self::assertSame('request-context', $rule->seenContext);
        self::assertSame(42, $rule->seenRouteId);

        // После проверки состояние сброшено.
        self::assertNull($rule->currentContext());
        self::assertNull($rule->currentRouteId());
    }
}
