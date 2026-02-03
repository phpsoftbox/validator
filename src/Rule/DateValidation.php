<?php

declare(strict_types=1);

namespace PhpSoftBox\Validator\Rule;

use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use PhpSoftBox\Validator\Support\DataPath;
use PhpSoftBox\Validator\ValidationEnum;
use PhpSoftBox\Validator\ValidationViolation;

use function date_default_timezone_get;
use function date_parse;
use function in_array;
use function is_array;
use function is_int;
use function is_string;
use function strtotime;

/**
 * Проверяет даты, форматы и временные сравнения.
 */
final class DateValidation extends AbstractRule
{
    /**
     * Требовать валидную дату (не относительную).
     */
    private bool $date = false;
    /**
     * Требовать валидный идентификатор часового пояса.
     */
    private bool $timezone = false;
    /**
     * Допустимые форматы даты (dateFormat).
     *
     * @var array<int, string>
     */
    private array $formats = [];
    /**
     * Список сравнений с датами (before/after/equals).
     *
     * @var list<array{rule: string, target: mixed, inclusive: bool}>
     */
    private array $comparisons = [];

    /**
     * Значение должно быть корректной, не относительной датой.
     *
     * Пример:
     * `->date()` принимает `2025-02-10`, но отклоняет `next monday`.
     */
    public function date(): self
    {
        $this->date = true;

        return $this;
    }

    /**
     * Значение должно быть корректным часовым поясом.
     *
     * Пример:
     * `->timezone()` принимает `Europe/Moscow` и отклоняет `UTC+3`.
     */
    public function timezone(): self
    {
        $this->timezone = true;

        return $this;
    }

    /**
     * Значение должно соответствовать одному из форматов.
     */
    public function dateFormat(string ...$formats): self
    {
        $this->formats = $formats;

        return $this;
    }

    /**
     * Значение должно быть после указанной даты.
     */
    public function after(string|DateTimeInterface|int $date): self
    {
        $this->comparisons[] = ['rule' => ValidationEnum::AFTER->value, 'target' => $date, 'inclusive' => false];

        return $this;
    }

    /**
     * Значение должно быть после указанной даты или равно ей.
     */
    public function afterOrEqual(string|DateTimeInterface|int $date): self
    {
        $this->comparisons[] = ['rule' => ValidationEnum::AFTER_OR_EQUAL->value, 'target' => $date, 'inclusive' => true];

        return $this;
    }

    /**
     * Значение должно быть до указанной даты.
     */
    public function before(string|DateTimeInterface|int $date): self
    {
        $this->comparisons[] = ['rule' => ValidationEnum::BEFORE->value, 'target' => $date, 'inclusive' => false];

        return $this;
    }

    /**
     * Значение должно быть до указанной даты или равно ей.
     */
    public function beforeOrEqual(string|DateTimeInterface|int $date): self
    {
        $this->comparisons[] = ['rule' => ValidationEnum::BEFORE_OR_EQUAL->value, 'target' => $date, 'inclusive' => true];

        return $this;
    }

    /**
     * Значение должно быть равно указанной дате.
     */
    public function dateEquals(string|DateTimeInterface|int $date): self
    {
        $this->comparisons[] = ['rule' => ValidationEnum::DATE_EQUALS->value, 'target' => $date, 'inclusive' => true];

        return $this;
    }

    /**
     * Значение должно быть после сегодняшнего дня.
     */
    public function afterToday(): self
    {
        return $this->after(new DateTimeImmutable('today'));
    }

    /**
     * Значение должно быть сегодня или после.
     */
    public function todayOrAfter(): self
    {
        return $this->afterOrEqual(new DateTimeImmutable('today'));
    }

    /**
     * Значение должно быть до сегодняшнего дня.
     */
    public function beforeToday(): self
    {
        return $this->before(new DateTimeImmutable('today'));
    }

    /**
     * Значение должно быть сегодня или до.
     */
    public function todayOrBefore(): self
    {
        return $this->beforeOrEqual(new DateTimeImmutable('today'));
    }

    public function validate(mixed $value, string $field, bool $present, array $data): array
    {
        $violations = [];

        if ($this->timezone) {
            if (!is_string($value) || !in_array($value, DateTimeZone::listIdentifiers(), true)) {
                $violations[] = new ValidationViolation(ValidationEnum::TIMEZONE->value);
            }
        }

        if (!$this->needsDateValidation()) {
            return $violations;
        }

        $date = $this->toDateTime($value, $this->date);
        if ($date === null) {
            $violations[] = new ValidationViolation(ValidationEnum::DATE->value);

            return $violations;
        }

        if ($this->formats !== []) {
            if (!$this->matchesAnyFormat($value, $this->formats)) {
                $violations[] = new ValidationViolation(ValidationEnum::DATE_FORMAT->value, [
                    'formats' => $this->formats,
                ]);
            }
        }

        foreach ($this->comparisons as $comparison) {
            $rule       = $comparison['rule'];
            $target     = $comparison['target'];
            $inclusive  = $comparison['inclusive'];
            $targetDate = $this->resolveTargetDate($data, $target);

            if ($targetDate === null) {
                $violations[] = new ValidationViolation($rule, ['date' => $this->describeTarget($target)]);
                continue;
            }

            $cmp = $this->compare($date, $targetDate);

            if ($rule === ValidationEnum::DATE_EQUALS->value) {
                if ($cmp !== 0) {
                    $violations[] = new ValidationViolation($rule, ['date' => $this->describeTarget($target)]);
                }
                continue;
            }

            if ($rule === ValidationEnum::AFTER->value || $rule === ValidationEnum::AFTER_OR_EQUAL->value) {
                if ($inclusive) {
                    if ($cmp < 0) {
                        $violations[] = new ValidationViolation($rule, ['date' => $this->describeTarget($target)]);
                    }
                } elseif ($cmp <= 0) {
                    $violations[] = new ValidationViolation($rule, ['date' => $this->describeTarget($target)]);
                }
                continue;
            }

            if ($rule === ValidationEnum::BEFORE->value || $rule === ValidationEnum::BEFORE_OR_EQUAL->value) {
                if ($inclusive) {
                    if ($cmp > 0) {
                        $violations[] = new ValidationViolation($rule, ['date' => $this->describeTarget($target)]);
                    }
                } elseif ($cmp >= 0) {
                    $violations[] = new ValidationViolation($rule, ['date' => $this->describeTarget($target)]);
                }
            }
        }

        return $violations;
    }

    public function messages(): array
    {
        return [
            ValidationEnum::DATE->value            => 'Поле {field} должно быть корректной датой.',
            ValidationEnum::DATE_FORMAT->value     => 'Поле {field} должно соответствовать формату {formats}.',
            ValidationEnum::DATE_EQUALS->value     => 'Поле {field} должно быть равно {date}.',
            ValidationEnum::AFTER->value           => 'Поле {field} должно быть после {date}.',
            ValidationEnum::AFTER_OR_EQUAL->value  => 'Поле {field} должно быть после или равно {date}.',
            ValidationEnum::BEFORE->value          => 'Поле {field} должно быть до {date}.',
            ValidationEnum::BEFORE_OR_EQUAL->value => 'Поле {field} должно быть до или равно {date}.',
            ValidationEnum::TIMEZONE->value        => 'Поле {field} должно быть корректным часовым поясом.',
        ];
    }

    private function needsDateValidation(): bool
    {
        return $this->date || $this->formats !== [] || $this->comparisons !== [];
    }

    private function matchesAnyFormat(mixed $value, array $formats): bool
    {
        if ($value instanceof DateTimeInterface) {
            return $formats !== [];
        }

        if (!is_string($value)) {
            return false;
        }

        foreach ($formats as $format) {
            $dt = DateTimeImmutable::createFromFormat($format, $value);
            if ($dt === false) {
                continue;
            }
            if ($dt->format($format) === $value) {
                return true;
            }
        }

        return false;
    }

    private function resolveTargetDate(array $data, mixed $target): ?DateTimeImmutable
    {
        if ($target instanceof DateTimeInterface || is_int($target)) {
            return $this->toDateTime($target, false);
        }

        if (!is_string($target)) {
            return null;
        }

        if (DataPath::has($data, $target)) {
            return $this->toDateTime(DataPath::get($data, $target), false);
        }

        return $this->toDateTime($target, false);
    }

    private function describeTarget(mixed $target): string
    {
        if ($target instanceof DateTimeInterface) {
            return $target->format(DateTimeInterface::ATOM);
        }

        if (is_int($target)) {
            return new DateTimeImmutable('@' . $target)->format(DateTimeInterface::ATOM);
        }

        if (is_string($target)) {
            return $target;
        }

        return 'дата';
    }

    private function toDateTime(mixed $value, bool $strictAbsolute): ?DateTimeImmutable
    {
        if ($value instanceof DateTimeInterface) {
            return DateTimeImmutable::createFromInterface($value);
        }

        if (is_int($value)) {
            return new DateTimeImmutable('@' . $value)->setTimezone(new DateTimeZone(date_default_timezone_get()));
        }

        if (!is_string($value)) {
            return null;
        }

        $parsed = date_parse($value);
        if (($parsed['error_count'] ?? 0) > 0 || ($parsed['warning_count'] ?? 0) > 0) {
            return null;
        }

        if ($strictAbsolute && $this->isRelativeDate($parsed)) {
            return null;
        }

        $timestamp = strtotime($value);
        if ($timestamp === false) {
            return null;
        }

        return new DateTimeImmutable()->setTimestamp($timestamp);
    }

    private function isRelativeDate(array $parsed): bool
    {
        if (!isset($parsed['relative']) || !is_array($parsed['relative'])) {
            return false;
        }

        foreach ($parsed['relative'] as $value) {
            if ($value !== 0) {
                return true;
            }
        }

        return false;
    }

    private function compare(DateTimeInterface $left, DateTimeInterface $right): int
    {
        $leftValue  = $left->getTimestamp() * 1000000 + (int) $left->format('u');
        $rightValue = $right->getTimestamp() * 1000000 + (int) $right->format('u');

        return $leftValue <=> $rightValue;
    }
}
