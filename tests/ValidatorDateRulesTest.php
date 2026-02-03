<?php

declare(strict_types=1);

namespace PhpSoftBox\Validator\Tests;

use DateTimeImmutable;
use PhpSoftBox\Validator\Rule\DateValidation;
use PhpSoftBox\Validator\Validator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(Validator::class)]
#[CoversClass(DateValidation::class)]
final class ValidatorDateRulesTest extends TestCase
{
    /**
     * Проверяет, что date отклоняет относительную дату.
     */
    #[Test]
    public function dateRejectsRelative(): void
    {
        $validator = new Validator();
        $rules     = [
            'date' => [new DateValidation()->date()],
        ];

        $result = $validator->validate(['date' => 'tomorrow'], $rules);

        self::assertSame(['Поле date должно быть корректной датой.'], $result->errorBag()->get('date'));
    }

    /**
     * Проверяет, что date принимает объект даты.
     */
    #[Test]
    public function dateAcceptsDateTime(): void
    {
        $validator = new Validator();
        $rules     = [
            'date' => [new DateValidation()->date()],
        ];

        $result = $validator->validate(['date' => new DateTimeImmutable('2024-01-01')], $rules);

        self::assertFalse($result->hasErrors());
    }

    /**
     * Проверяет правило date_format.
     */
    #[Test]
    public function dateFormatValidation(): void
    {
        $validator = new Validator();
        $rules     = [
            'date' => [new DateValidation()->dateFormat('Y-m-d')],
        ];

        $result = $validator->validate(['date' => '2024-01-01'], $rules);

        self::assertFalse($result->hasErrors());
    }

    /**
     * Проверяет сравнение after с датой.
     */
    #[Test]
    public function afterDateRule(): void
    {
        $validator = new Validator();
        $rules     = [
            'start' => [new DateValidation()->after('2024-01-02')],
        ];

        $result = $validator->validate(['start' => '2024-01-01'], $rules);

        self::assertSame(['Поле start должно быть после 2024-01-02.'], $result->errorBag()->get('start'));
    }

    /**
     * Проверяет сравнение после другого поля.
     */
    #[Test]
    public function afterFieldRule(): void
    {
        $validator = new Validator();
        $rules     = [
            'finish' => [new DateValidation()->after('start')],
        ];

        $result = $validator->validate([
            'start'  => '2024-01-10',
            'finish' => '2024-01-05',
        ], $rules);

        self::assertSame(['Поле finish должно быть после start.'], $result->errorBag()->get('finish'));
    }

    /**
     * Проверяет правило date_equals.
     */
    #[Test]
    public function dateEqualsRule(): void
    {
        $validator = new Validator();
        $rules     = [
            'date' => [new DateValidation()->dateEquals('2024-02-01')],
        ];

        $result = $validator->validate(['date' => '2024-02-02'], $rules);

        self::assertSame(['Поле date должно быть равно 2024-02-01.'], $result->errorBag()->get('date'));
    }

    /**
     * Проверяет правило timezone.
     */
    #[Test]
    public function timezoneRule(): void
    {
        $validator = new Validator();
        $rules     = [
            'tz' => [new DateValidation()->timezone()],
        ];

        $result = $validator->validate(['tz' => 'Invalid/Zone'], $rules);

        self::assertSame(['Поле tz должно быть корректным часовым поясом.'], $result->errorBag()->get('tz'));
    }
}
