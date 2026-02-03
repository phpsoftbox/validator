<?php

declare(strict_types=1);

namespace PhpSoftBox\Validator\Rule;

use PhpSoftBox\Validator\Support\DataPath;
use PhpSoftBox\Validator\ValidationEnum;
use PhpSoftBox\Validator\ValidationViolation;

use function implode;
use function in_array;

/**
 * Управляющее правило для запрета присутствия поля.
 */
final class MissingValidation extends AbstractControlRule
{
    /**
     * Условия отсутствия (missing_if/with/...).
     *
     * @var list<array{rule: string, fn: callable, params: array}>
     */
    private array $conditions = [];

    /**
     * Проверяет, что поле отсутствует с учетом условий.
     */
    public function validate(mixed $value, string $field, bool $present, array $data): array
    {
        if ($this->conditions === []) {
            return $present ? [new ValidationViolation(ValidationEnum::MISSING->value)] : [];
        }

        foreach ($this->conditions as $condition) {
            $fn = $condition['fn'];
            if ($fn($data) !== true) {
                continue;
            }

            return $present ? [new ValidationViolation($condition['rule'], $condition['params'])] : [];
        }

        return [];
    }

    /**
     * Запрещает наличие поля, если связанное поле равно любому из значений.
     */
    public function missingIf(string $field, mixed ...$values): static
    {
        return $this->condition(
            ValidationEnum::MISSING_IF->value,
            function (array $data) use ($field, $values): bool {
                return $this->anyMatchEquals($data, $field, $values);
            },
            ['other' => $field, 'values' => $values],
        );
    }

    /**
     * Запрещает наличие поля, если связанное поле не равно любому из значений.
     */
    public function missingUnless(string $field, mixed ...$values): static
    {
        return $this->condition(
            ValidationEnum::MISSING_UNLESS->value,
            function (array $data) use ($field, $values): bool {
                return $this->unlessCondition($data, $field, $values);
            },
            ['other' => $field, 'values' => $values],
        );
    }

    /**
     * Запрещает наличие поля при наличии хотя бы одного из связанных полей.
     */
    public function missingWith(string ...$fields): static
    {
        return $this->condition(
            ValidationEnum::MISSING_WITH->value,
            function (array $data) use ($fields): bool {
                return $this->anyPresent($data, $fields);
            },
            ['other' => implode(', ', $fields)],
        );
    }

    /**
     * Запрещает наличие поля при наличии всех связанных полей.
     */
    public function missingWithAll(string ...$fields): static
    {
        return $this->condition(
            ValidationEnum::MISSING_WITH_ALL->value,
            function (array $data) use ($fields): bool {
                return $this->allPresent($data, $fields);
            },
            ['other' => implode(', ', $fields)],
        );
    }

    /**
     * Сообщения для missing‑правил.
     */
    public function messages(): array
    {
        return [
            ValidationEnum::MISSING->value          => 'Поле {field} должно отсутствовать.',
            ValidationEnum::MISSING_IF->value       => 'Поле {field} должно отсутствовать, если {other} равно {values}.',
            ValidationEnum::MISSING_UNLESS->value   => 'Поле {field} должно отсутствовать, если {other} не равно {values}.',
            ValidationEnum::MISSING_WITH->value     => 'Поле {field} должно отсутствовать при наличии полей {other}.',
            ValidationEnum::MISSING_WITH_ALL->value => 'Поле {field} должно отсутствовать при наличии всех полей {other}.',
        ];
    }

    /**
     * Добавить условие отсутствия.
     */
    protected function condition(string $rule, callable $fn, array $params = []): static
    {
        $this->conditions[] = ['rule' => $rule, 'fn' => $fn, 'params' => $params];

        return $this;
    }

    /**
     * Проверить, что поле присутствует в данных.
     */
    private function isPresent(array $data, string $field): bool
    {
        $matches = DataPath::extract($data, $field);
        foreach ($matches as $match) {
            if ($match->present) {
                return true;
            }
        }

        return false;
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
     * Проверить, что присутствует хотя бы одно поле.
     * @param array<int, string> $fields
     */
    private function anyPresent(array $data, array $fields): bool
    {
        foreach ($fields as $field) {
            if ($this->isPresent($data, $field)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Проверить, что присутствуют все поля.
     * @param array<int, string> $fields
     */
    private function allPresent(array $data, array $fields): bool
    {
        foreach ($fields as $field) {
            if (!$this->isPresent($data, $field)) {
                return false;
            }
        }

        return $fields !== [];
    }
}
