<?php

declare(strict_types=1);

namespace PhpSoftBox\Validator\Tests;

use PhpSoftBox\Validator\Rule\BoolValidation;
use PhpSoftBox\Validator\Validator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(Validator::class)]
#[CoversClass(BoolValidation::class)]
final class ValidatorBoolRulesTest extends TestCase
{
    /**
     * Проверяет, что boolean принимает типичные булевы значения.
     */
    #[Test]
    public function booleanAcceptsCommonValues(): void
    {
        $validator = new Validator();
        $rules     = [
            'flag' => [new BoolValidation()],
        ];

        $result = $validator->validate(['flag' => 'yes'], $rules);

        self::assertFalse($result->hasErrors());
    }

    /**
     * Проверяет, что boolean отклоняет невалидные значения.
     */
    #[Test]
    public function booleanRejectsInvalidValues(): void
    {
        $validator = new Validator();
        $rules     = [
            'flag' => [new BoolValidation()],
        ];

        $result = $validator->validate(['flag' => 'maybe'], $rules);

        self::assertSame(['Поле flag должно быть булевым.'], $result->errorBag()->get('flag'));
    }

    /**
     * Проверяет правило accepted.
     */
    #[Test]
    public function acceptedRequiresAcceptedValue(): void
    {
        $validator = new Validator();
        $rules     = [
            'terms' => [new BoolValidation()->accepted()],
        ];

        $result = $validator->validate(['terms' => 'no'], $rules);

        self::assertSame(['Поле terms должно быть принято.'], $result->errorBag()->get('terms'));
    }

    /**
     * Проверяет правило accepted_if.
     */
    #[Test]
    public function acceptedIfChecksCondition(): void
    {
        $validator = new Validator();
        $rules     = [
            'terms' => [new BoolValidation()->acceptedIf('status', 'active')],
        ];

        $result = $validator->validate(['status' => 'active', 'terms' => 'no'], $rules);

        self::assertSame(
            ['Поле terms должно быть принято, если status равно ["active"].'],
            $result->errorBag()->get('terms'),
        );
    }

    /**
     * Проверяет правило declined.
     */
    #[Test]
    public function declinedRequiresDeclinedValue(): void
    {
        $validator = new Validator();
        $rules     = [
            'flag' => [new BoolValidation()->declined()],
        ];

        $result = $validator->validate(['flag' => 'yes'], $rules);

        self::assertSame(['Поле flag должно быть отклонено.'], $result->errorBag()->get('flag'));
    }

    /**
     * Проверяет правило declined_if.
     */
    #[Test]
    public function declinedIfChecksCondition(): void
    {
        $validator = new Validator();
        $rules     = [
            'flag' => [new BoolValidation()->declinedIf('mode', 'strict')],
        ];

        $result = $validator->validate(['mode' => 'strict', 'flag' => 'yes'], $rules);

        self::assertSame(
            ['Поле flag должно быть отклонено, если mode равно ["strict"].'],
            $result->errorBag()->get('flag'),
        );
    }
}
