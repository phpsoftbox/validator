<?php

declare(strict_types=1);

namespace PhpSoftBox\Validator\Rule;

use PhpSoftBox\Validator\Support\DataPath;
use PhpSoftBox\Validator\ValidationEnum;
use PhpSoftBox\Validator\ValidationViolation;

use function implode;
use function in_array;

/**
 * Управляющее правило для проверки присутствия поля.
 */
final class PresentValidation extends AbstractControlRule
{
    /**
     * Условия присутствия (present_if/with/...).
     *
     * @var list<array{rule: string, fn: callable, params: array}>
     */
    private array $conditions = [];

    /**
     * Валидатор не выполняет проверок, так как правило управляющее.
     */
    public function validate(mixed $value, string $field, bool $present, array $data): array
    {
        return [];
    }

    /**
     * Требовать присутствие, если связанное поле равно любому из значений.
     */
    public function presentIf(string $field, mixed ...$values): static
    {
        return $this->condition(
            ValidationEnum::PRESENT_IF->value,
            function (array $data) use ($field, $values): bool {
                return $this->anyMatchEquals($data, $field, $values);
            },
            ['other' => $field, 'values' => $values],
        );
    }

    /**
     * Требовать присутствие, если связанное поле не равно любому из значений.
     */
    public function presentUnless(string $field, mixed ...$values): static
    {
        return $this->condition(
            ValidationEnum::PRESENT_UNLESS->value,
            function (array $data) use ($field, $values): bool {
                return $this->unlessCondition($data, $field, $values);
            },
            ['other' => $field, 'values' => $values],
        );
    }

    /**
     * Требовать присутствие, если есть хотя бы одно из связанных полей.
     */
    public function presentWith(string ...$fields): static
    {
        return $this->condition(
            ValidationEnum::PRESENT_WITH->value,
            function (array $data) use ($fields): bool {
                return $this->anyPresent($data, $fields);
            },
            ['other' => implode(', ', $fields)],
        );
    }

    /**
     * Требовать присутствие, если присутствуют все связанные поля.
     */
    public function presentWithAll(string ...$fields): static
    {
        return $this->condition(
            ValidationEnum::PRESENT_WITH_ALL->value,
            function (array $data) use ($fields): bool {
                return $this->allPresent($data, $fields);
            },
            ['other' => implode(', ', $fields)],
        );
    }

    /**
     * Возвращает нарушение, если поле должно присутствовать, но отсутствует.
     */
    public function requiredViolation(array $data, mixed $context, string $field): ?ValidationViolation
    {
        $parentViolation = parent::requiredViolation($data, $context, $field);
        if ($parentViolation !== null) {
            return $parentViolation;
        }

        $isPresent = $this->isPresent($data, $field);

        if ($this->conditions === []) {
            return $isPresent ? null : new ValidationViolation(ValidationEnum::PRESENT->value);
        }

        foreach ($this->conditions as $condition) {
            $fn = $condition['fn'];
            if ($fn($data) !== true) {
                continue;
            }

            return $isPresent ? null : new ValidationViolation($condition['rule'], $condition['params']);
        }

        return null;
    }

    /**
     * Сообщения для present‑правил.
     */
    public function messages(): array
    {
        return [
            ValidationEnum::PRESENT->value          => 'Поле {field} должно присутствовать.',
            ValidationEnum::PRESENT_IF->value       => 'Поле {field} должно присутствовать, если {other} равно {values}.',
            ValidationEnum::PRESENT_UNLESS->value   => 'Поле {field} должно присутствовать, если {other} не равно {values}.',
            ValidationEnum::PRESENT_WITH->value     => 'Поле {field} должно присутствовать при наличии полей {other}.',
            ValidationEnum::PRESENT_WITH_ALL->value => 'Поле {field} должно присутствовать при наличии всех полей {other}.',
        ];
    }

    /**
     * Добавить условие присутствия.
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
