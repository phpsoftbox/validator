<?php

declare(strict_types=1);

namespace PhpSoftBox\Validator\Rule;

use PhpSoftBox\Validator\Support\DataPath;
use PhpSoftBox\Validator\ValidationEnum;
use PhpSoftBox\Validator\ValidationViolation;

use function in_array;

/**
 * Управляющее правило: запрещает поле при выполнении условий.
 */
final class ProhibitedValidation extends AbstractControlRule
{
    /**
     * Условия запрета.
     *
     * @var list<array{rule: string, fn: callable, params: array}>
     */
    private array $conditions = [];

    /**
     * Проверяет, что поле запрещено при выполнении условий.
     */
    public function validate(mixed $value, string $field, bool $present, array $data): array
    {
        if (!$present) {
            return [];
        }

        if ($this->conditions === []) {
            return [new ValidationViolation(ValidationEnum::PROHIBITED->value)];
        }

        foreach ($this->conditions as $condition) {
            $fn = $condition['fn'];
            if ($fn($data) !== true) {
                continue;
            }

            return [new ValidationViolation($condition['rule'], $condition['params'])];
        }

        return [];
    }

    /**
     * Запрещает поле, если связанное поле равно любому из значений.
     */
    public function prohibitedIf(string $field, mixed ...$values): static
    {
        return $this->condition(
            ValidationEnum::PROHIBITED_IF->value,
            function (array $data) use ($field, $values): bool {
                return $this->anyMatchEquals($data, $field, $values);
            },
            ['other' => $field, 'values' => $values],
        );
    }

    /**
     * Запрещает поле, если связанное поле не равно любому из значений.
     */
    public function prohibitedUnless(string $field, mixed ...$values): static
    {
        return $this->condition(
            ValidationEnum::PROHIBITED_UNLESS->value,
            function (array $data) use ($field, $values): bool {
                return $this->unlessCondition($data, $field, $values);
            },
            ['other' => $field, 'values' => $values],
        );
    }

    /**
     * Запрещает поле, если связанное поле принято.
     */
    public function prohibitedIfAccepted(string $field): static
    {
        return $this->condition(
            ValidationEnum::PROHIBITED_IF_ACCEPTED->value,
            function (array $data) use ($field): bool {
                return $this->anyMatch($data, $field, fn (mixed $value): bool => $this->isAccepted($value));
            },
            ['other' => $field],
        );
    }

    /**
     * Запрещает поле, если связанное поле отклонено.
     */
    public function prohibitedIfDeclined(string $field): static
    {
        return $this->condition(
            ValidationEnum::PROHIBITED_IF_DECLINED->value,
            function (array $data) use ($field): bool {
                return $this->anyMatch($data, $field, fn (mixed $value): bool => $this->isDeclined($value));
            },
            ['other' => $field],
        );
    }

    /**
     * Сообщения для prohibited‑правил.
     */
    public function messages(): array
    {
        return [
            ValidationEnum::PROHIBITED->value             => 'Поле {field} запрещено.',
            ValidationEnum::PROHIBITED_IF->value          => 'Поле {field} запрещено, если {other} равно {values}.',
            ValidationEnum::PROHIBITED_UNLESS->value      => 'Поле {field} запрещено, если {other} не равно {values}.',
            ValidationEnum::PROHIBITED_IF_ACCEPTED->value => 'Поле {field} запрещено, если {other} принято.',
            ValidationEnum::PROHIBITED_IF_DECLINED->value => 'Поле {field} запрещено, если {other} отклонено.',
        ];
    }

    /**
     * Добавить условие запрета.
     */
    protected function condition(string $rule, callable $fn, array $params = []): static
    {
        $this->conditions[] = ['rule' => $rule, 'fn' => $fn, 'params' => $params];

        return $this;
    }

    /**
     * Проверить, что связанное поле равно любому из значений.
     */
    private function anyMatchEquals(array $data, string $field, array $values): bool
    {
        $matches = DataPath::extract($data, $field);
        foreach ($matches as $match) {
            if (!$match->present) {
                continue;
            }
            if (in_array($match->value, $values, true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Условие "unless": true, если поле отсутствует или не равно ни одному значению.
     */
    private function unlessCondition(array $data, string $field, array $values): bool
    {
        $matches    = DataPath::extract($data, $field);
        $hasPresent = false;
        foreach ($matches as $match) {
            if (!$match->present) {
                continue;
            }
            $hasPresent = true;
            if (in_array($match->value, $values, true)) {
                return false;
            }
        }

        if ($hasPresent) {
            return true;
        }

        return !in_array(null, $values, true);
    }

    /**
     * Проверить, что связанное поле соответствует предикату.
     */
    protected function anyMatch(array $data, string $field, callable $predicate): bool
    {
        $matches = DataPath::extract($data, $field);
        foreach ($matches as $match) {
            if (!$match->present) {
                continue;
            }
            if ($predicate($match->value) === true) {
                return true;
            }
        }

        return false;
    }
}
