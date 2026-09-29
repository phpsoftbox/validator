<?php

declare(strict_types=1);

namespace PhpSoftBox\Validator\Tests;

use PhpSoftBox\Validator\Rule\IntValidation;
use PhpSoftBox\Validator\Validator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use const PHP_INT_MAX;

#[CoversClass(IntValidation::class)]
#[CoversMethod(IntValidation::class, 'validate')]
final class IntValidationParsingTest extends TestCase
{
    /**
     * Проверим, что float без дробной части (5.0) считается целым числом.
     *
     * @see IntValidation::validate()
     */
    #[Test]
    public function acceptsIntegralFloat(): void
    {
        $validator = new Validator();

        $result = $validator->validate(['value' => 5.0], ['value' => [new IntValidation()->max(5)]]);

        self::assertFalse($result->hasErrors());
    }

    /**
     * Проверим, что строка с нулевой дробной частью («5.0») считается целым числом.
     *
     * @see IntValidation::validate()
     */
    #[Test]
    public function acceptsIntegralString(): void
    {
        $validator = new Validator();

        $result = $validator->validate(['value' => '5.0'], ['value' => [new IntValidation()->digits(1)]]);

        self::assertFalse($result->hasErrors());
    }

    /**
     * Проверим, что float с дробной частью отклоняется.
     *
     * @see IntValidation::validate()
     */
    #[Test]
    public function rejectsFractionalFloat(): void
    {
        $validator = new Validator();

        $result = $validator->validate(['value' => 5.5], ['value' => [new IntValidation()]]);

        self::assertSame(['Поле value должно быть целым числом.'], $result->errorBag()->get('value'));
    }

    /**
     * Проверим, что строка за пределами int не «обрезается» до PHP_INT_MAX и не проходит max(PHP_INT_MAX).
     *
     * @see IntValidation::validate()
     */
    #[Test]
    public function rejectsOverflowString(): void
    {
        $validator = new Validator();

        $result = $validator->validate(
            ['value' => '99999999999999999999'],
            ['value' => [new IntValidation()->max(PHP_INT_MAX)]],
        );

        self::assertSame(['Поле value должно быть целым числом.'], $result->errorBag()->get('value'));
    }
}
