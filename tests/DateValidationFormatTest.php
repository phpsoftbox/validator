<?php

declare(strict_types=1);

namespace PhpSoftBox\Validator\Tests;

use PhpSoftBox\Validator\Rule\DateValidation;
use PhpSoftBox\Validator\Validator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(DateValidation::class)]
#[CoversMethod(DateValidation::class, 'dateFormat')]
#[CoversMethod(DateValidation::class, 'validate')]
final class DateValidationFormatTest extends TestCase
{
    /**
     * Проверим, что дата в формате d/m/Y с днём больше 12 принимается.
     *
     * @see DateValidation::dateFormat()
     * @see DateValidation::validate()
     */
    #[Test]
    public function acceptsDayFirstFormat(): void
    {
        $validator = new Validator();

        $result = $validator->validate(
            data: ['date' => '31/12/2024'],
            rules: ['date' => [new DateValidation()->dateFormat('d/m/Y')]],
        );

        self::assertFalse($result->hasErrors());
    }

    /**
     * Проверим, что несуществующая дата (31 февраля) не проходит dateFormat.
     *
     * @see DateValidation::dateFormat()
     * @see DateValidation::validate()
     */
    #[Test]
    public function rejectsOverflowDate(): void
    {
        $validator = new Validator();

        $result = $validator->validate(
            data: ['date' => '31/02/2024'],
            rules: ['date' => [new DateValidation()->dateFormat('d/m/Y')]],
        );

        self::assertSame(
            ['Поле date должно соответствовать формату ["d/m/Y"].'],
            $result->errorBag()->get('date'),
        );
    }

    /**
     * Проверим, что сравнение использует дату, разобранную по формату: 01/02/2024 — это 1 февраля, а не 2 января.
     *
     * @see DateValidation::dateFormat()
     * @see DateValidation::validate()
     */
    #[Test]
    public function comparesUsingFormatParsedValue(): void
    {
        $validator = new Validator();

        $result = $validator->validate(
            data: ['date' => '01/02/2024'],
            rules: ['date' => [new DateValidation()->dateFormat('d/m/Y')->dateEquals('2024-02-01')]],
        );

        self::assertFalse($result->hasErrors());
    }

    /**
     * Проверим, что значение другого поля в сравнении тоже разбирается по формату dateFormat.
     *
     * @see DateValidation::dateFormat()
     * @see DateValidation::validate()
     */
    #[Test]
    public function comparesWithOtherFieldUsingFormat(): void
    {
        $validator = new Validator();

        $result = $validator->validate(
            data: ['start' => '01/02/2024', 'end' => '15/01/2024'],
            rules: ['end' => [new DateValidation()->dateFormat('d/m/Y')->afterOrEqual('start')]],
        );

        // 15 января раньше 1 февраля.
        self::assertSame(['Поле end должно быть после или равно start.'], $result->errorBag()->get('end'));
    }
}
