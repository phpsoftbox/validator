<?php

declare(strict_types=1);

namespace PhpSoftBox\Validator\Tests;

use PhpSoftBox\Validator\Rule\FloatValidation;
use PhpSoftBox\Validator\Validator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(FloatValidation::class)]
#[CoversMethod(FloatValidation::class, 'digits')]
#[CoversMethod(FloatValidation::class, 'multipleOf')]
#[CoversMethod(FloatValidation::class, 'validate')]
final class FloatValidationDecimalTest extends TestCase
{
    /**
     * Проверим, что нули целого числа учитываются в количестве цифр: у 100 три цифры.
     *
     * @see FloatValidation::digits()
     * @see FloatValidation::validate()
     */
    #[Test]
    public function digitsCountsTrailingZerosOfInteger(): void
    {
        $validator = new Validator();

        $result = $validator->validate(['value' => 100], ['value' => [new FloatValidation()->digits(3)]]);

        self::assertFalse($result->hasErrors());
    }

    /**
     * Проверим, что 0.3 кратно 0.1, несмотря на двоичное представление float.
     *
     * @see FloatValidation::multipleOf()
     * @see FloatValidation::validate()
     */
    #[Test]
    public function multipleOfAcceptsDecimalFloat(): void
    {
        $validator = new Validator();

        $result = $validator->validate(['value' => 0.3], ['value' => [new FloatValidation()->multipleOf(0.1)]]);

        self::assertFalse($result->hasErrors());
    }

    /**
     * Проверим, что кратность строкового значения проверяется по его десятичной записи.
     *
     * @see FloatValidation::multipleOf()
     * @see FloatValidation::validate()
     */
    #[Test]
    public function multipleOfAcceptsDecimalString(): void
    {
        $validator = new Validator();

        $result = $validator->validate(['value' => '1.15'], ['value' => [new FloatValidation()->multipleOf(0.05)]]);

        self::assertFalse($result->hasErrors());
    }

    /**
     * Проверим, что некратное значение отклоняется.
     *
     * @see FloatValidation::multipleOf()
     * @see FloatValidation::validate()
     */
    #[Test]
    public function multipleOfRejectsNonMultiple(): void
    {
        $validator = new Validator();

        $result = $validator->validate(['value' => 0.35], ['value' => [new FloatValidation()->multipleOf(0.1)]]);

        self::assertSame(['Поле value должно быть кратно 0.1.'], $result->errorBag()->get('value'));
    }
}
