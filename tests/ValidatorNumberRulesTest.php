<?php

declare(strict_types=1);

namespace PhpSoftBox\Validator\Tests;

use PhpSoftBox\Validator\Rule\FloatValidation;
use PhpSoftBox\Validator\Rule\IntValidation;
use PhpSoftBox\Validator\ValidationEnum;
use PhpSoftBox\Validator\Validator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(Validator::class)]
#[CoversClass(IntValidation::class)]
#[CoversClass(FloatValidation::class)]
final class ValidatorNumberRulesTest extends TestCase
{
    /**
     * Проверяет правила between и size для целых чисел.
     */
    #[Test]
    public function intBetweenAndSizeRules(): void
    {
        $validator = new Validator();
        $rules     = [
            'age' => [new IntValidation()->between(10, 20)->size(15)],
        ];

        $result = $validator->validate(['age' => 5], $rules);

        self::assertSame(
            ['Поле age должно быть между 10 и 20.', 'Поле age должно быть равно 15.'],
            $result->errorBag()->get('age'),
        );
    }

    /**
     * Проверяет правила digits, min_digits, max_digits и digits_between.
     */
    #[Test]
    public function intDigitsRules(): void
    {
        $validator = new Validator();
        $rules     = [
            'code' => [new IntValidation()->digits(3)->minDigits(4)->maxDigits(1)->digitsBetween(5, 6)],
        ];

        $result = $validator->validate(['code' => 12], $rules);

        self::assertSame(
            [
                'Поле code должно содержать 3 цифр.',
                'Поле code должно содержать не меньше 4 цифр.',
                'Поле code должно содержать не больше 1 цифр.',
                'Поле code должно содержать от 5 до 6 цифр.',
            ],
            $result->errorBag()->get('code'),
        );
    }

    /**
     * Проверяет правила multiple_of и in для целых чисел.
     */
    #[Test]
    public function intMultipleOfAndInRules(): void
    {
        $validator = new Validator();
        $rules     = [
            'num' => [new IntValidation()->multipleOf(4)->in(1, 2, 3)],
        ];

        $result = $validator->validate(['num' => 10], $rules);

        self::assertSame(
            ['Поле num должно быть кратно 4.', 'Поле num должно быть одним из [1,2,3].'],
            $result->errorBag()->get('num'),
        );
    }

    /**
     * Проверяет правила сравнения и same/different для целых чисел.
     */
    #[Test]
    public function intComparisonRules(): void
    {
        $validator = new Validator();
        $rules     = [
            'same'      => [new IntValidation()->same('other')],
            'different' => [new IntValidation()->different('limit')],
            'gt'        => [new IntValidation()->greaterThan('limit')],
            'gte'       => [new IntValidation()->greaterThanOrEqual('limit')],
            'lt'        => [new IntValidation()->lessThan('limit')],
            'lte'       => [new IntValidation()->lessThanOrEqual('limit')],
        ];

        $result = $validator->validate([
            'other'     => 5,
            'limit'     => 10,
            'same'      => 4,
            'different' => 10,
            'gt'        => 9,
            'gte'       => 9,
            'lt'        => 11,
            'lte'       => 11,
        ], $rules);

        self::assertSame(['Поле same должно совпадать с other.'], $result->errorBag()->get('same'));
        self::assertSame(['Поле different должно отличаться от limit.'], $result->errorBag()->get('different'));
        self::assertSame(['Поле gt должно быть больше limit.'], $result->errorBag()->get('gt'));
        self::assertSame(['Поле gte должно быть больше или равно limit.'], $result->errorBag()->get('gte'));
        self::assertSame(['Поле lt должно быть меньше limit.'], $result->errorBag()->get('lt'));
        self::assertSame(['Поле lte должно быть меньше или равно limit.'], $result->errorBag()->get('lte'));
    }

    /**
     * Проверяет правила between и size для чисел с плавающей точкой.
     */
    #[Test]
    public function floatBetweenAndSizeRules(): void
    {
        $validator = new Validator();
        $rules     = [
            'price' => [new FloatValidation()->between(1.5, 2.5)->size(2.0)],
        ];

        $result = $validator->validate(['price' => 3.2], $rules);

        self::assertSame(
            ['Поле price должно быть между 1.5 и 2.5.', 'Поле price должно быть равно 2.'],
            $result->errorBag()->get('price'),
        );
    }

    /**
     * Проверяет правило decimal.
     */
    #[Test]
    public function floatDecimalRule(): void
    {
        $validator = new Validator();
        $rules     = [
            'rate' => [new FloatValidation()->decimal(2, 3)],
        ];

        $result = $validator->validate(['rate' => '1.2345'], $rules);

        self::assertSame(
            ['Поле rate должно содержать от 2 до 3 знаков после запятой.'],
            $result->errorBag()->get('rate'),
        );
    }

    /**
     * Проверяет правило digits для чисел с десятичной частью.
     */
    #[Test]
    public function floatDigitsRejectsDecimal(): void
    {
        $validator = new Validator();
        $rules     = [
            'value' => [new FloatValidation()->digits(2)],
        ];

        $result = $validator->validate(['value' => '1.23'], $rules);

        self::assertSame(['Поле value должно содержать 2 цифр.'], $result->errorBag()->get('value'));
    }

    /**
     * Проверяет правила min_digits, max_digits и digits_between для чисел с плавающей точкой.
     */
    #[Test]
    public function floatDigitsRangeRules(): void
    {
        $validator = new Validator();
        $rules     = [
            'value' => [new FloatValidation()->minDigits(4)->maxDigits(2)->digitsBetween(4, 5)],
        ];

        $result = $validator->validate(['value' => 123], $rules);

        self::assertSame(
            [
                'Поле value должно содержать не меньше 4 цифр.',
                'Поле value должно содержать не больше 2 цифр.',
                'Поле value должно содержать от 4 до 5 цифр.',
            ],
            $result->errorBag()->get('value'),
        );
    }

    /**
     * Проверяет правила multiple_of и in для чисел с плавающей точкой.
     */
    #[Test]
    public function floatMultipleOfAndInRules(): void
    {
        $validator = new Validator();
        $rules     = [
            'amount' => [new FloatValidation()->multipleOf(0.5)->in(1.5, 2.5)],
        ];

        $result = $validator->validate(['amount' => 1.3], $rules);

        self::assertSame(
            ['Поле amount должно быть кратно 0.5.', 'Поле amount должно быть одним из [1.5,2.5].'],
            $result->errorBag()->get('amount'),
        );
    }

    /**
     * Проверяет правила сравнения и same/different для чисел с плавающей точкой.
     */
    #[Test]
    public function floatComparisonRules(): void
    {
        $validator = new Validator();
        $rules     = [
            'same'      => [new FloatValidation()->same('other')],
            'different' => [new FloatValidation()->different('limit')],
            'gt'        => [new FloatValidation()->greaterThan('limit')],
            'gte'       => [new FloatValidation()->greaterThanOrEqual('limit')],
            'lt'        => [new FloatValidation()->lessThan('limit')],
            'lte'       => [new FloatValidation()->lessThanOrEqual('limit')],
        ];

        $result = $validator->validate([
            'other'     => 1.5,
            'limit'     => 10.5,
            'same'      => 1.4,
            'different' => 10.5,
            'gt'        => 10.0,
            'gte'       => 10.0,
            'lt'        => 11.0,
            'lte'       => 11.0,
        ], $rules);

        self::assertSame(['Поле same должно совпадать с other.'], $result->errorBag()->get('same'));
        self::assertSame(['Поле different должно отличаться от limit.'], $result->errorBag()->get('different'));
        self::assertSame(['Поле gt должно быть больше limit.'], $result->errorBag()->get('gt'));
        self::assertSame(['Поле gte должно быть больше или равно limit.'], $result->errorBag()->get('gte'));
        self::assertSame(['Поле lt должно быть меньше limit.'], $result->errorBag()->get('lt'));
        self::assertSame(['Поле lte должно быть меньше или равно limit.'], $result->errorBag()->get('lte'));
    }

    /**
     * Проверяет правило numeric с кастомным сообщением.
     */
    #[Test]
    public function numericRuleUsesNumericKey(): void
    {
        $validator = new Validator();
        $rules     = [
            'value' => [new FloatValidation()->numeric()],
        ];
        $messages = [
            'value' => [
                ValidationEnum::NUMERIC->value => 'Поле value должно быть числом (numeric).',
            ],
        ];

        $result = $validator->validate(['value' => 'nope'], $rules, $messages);

        self::assertSame(['Поле value должно быть числом (numeric).'], $result->errorBag()->get('value'));
    }
}
