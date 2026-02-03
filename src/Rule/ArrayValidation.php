<?php

declare(strict_types=1);

namespace PhpSoftBox\Validator\Rule;

use PhpSoftBox\Validator\Support\DataPath;
use PhpSoftBox\Validator\ValidationEnum;
use PhpSoftBox\Validator\ValidationViolation;

use function array_is_list;
use function array_key_exists;
use function count;
use function function_exists;
use function in_array;
use function is_array;
use function is_object;
use function serialize;
use function spl_object_id;

/**
 * Проверяет массивы: размеры, список/уникальность, содержание и ключи.
 */
final class ArrayValidation extends AbstractRule
{
    /**
     * Минимальное количество элементов.
     */
    private ?int $min = null;

    /**
     * Максимальное количество элементов.
     */
    private ?int $max = null;

    /**
     * Минимум в диапазоне between().
     */
    private ?int $betweenMin = null;

    /**
     * Максимум в диапазоне between().
     */
    private ?int $betweenMax = null;

    /**
     * Точный размер массива.
     */
    private ?int $size = null;

    /**
     * Требовать уникальные значения.
     */
    private bool $distinct = false;

    /**
     * Требовать список (array_is_list).
     */
    private bool $listOnly = false;

    /**
     * Значения, которые должны присутствовать в массиве.
     *
     * @var array<int, mixed>|null
     */
    private ?array $contains = null;

    /**
     * Значения, которые не должны присутствовать в массиве.
     *
     * @var array<int, mixed>|null
     */
    private ?array $doesntContain = null;

    /**
     * Поле, в котором хранится список допустимых значений (inArray).
     */
    private ?string $inArrayField = null;

    /**
     * Допустимые ключи массива (inArrayKeys).
     *
     * @var array<int, string|int>|null
     */
    private ?array $inArrayKeys = null;

    public function min(int $min): self
    {
        $this->min = $min;

        return $this;
    }

    public function max(int $max): self
    {
        $this->max = $max;

        return $this;
    }

    public function setMin(int $min): self
    {
        return $this->min($min);
    }

    public function setMax(int $max): self
    {
        return $this->max($max);
    }

    /**
     * Размер должен быть в диапазоне.
     */
    public function between(int $min, int $max): self
    {
        $this->betweenMin = $min;
        $this->betweenMax = $max;

        return $this;
    }

    /**
     * Размер массива должен быть равен.
     */
    public function size(int $size): self
    {
        $this->size = $size;

        return $this;
    }

    /**
     * Массив должен быть списком.
     */
    public function listOnly(): self
    {
        $this->listOnly = true;

        return $this;
    }

    /**
     * Массив должен содержать уникальные значения.
     */
    public function distinct(): self
    {
        $this->distinct = true;

        return $this;
    }

    /**
     * Массив должен содержать значения.
     */
    public function contains(mixed ...$values): self
    {
        $this->contains = $values;

        return $this;
    }

    /**
     * Массив не должен содержать значения.
     */
    public function doesntContain(mixed ...$values): self
    {
        $this->doesntContain = $values;

        return $this;
    }

    /**
     * Каждый элемент массива должен присутствовать в значениях другого массива.
     */
    public function inArray(string $field): self
    {
        $this->inArrayField = $field;

        return $this;
    }

    /**
     * Массив должен иметь хотя бы один из ключей.
     */
    public function inArrayKeys(string|int ...$keys): self
    {
        $this->inArrayKeys = $keys;

        return $this;
    }

    public function validate(mixed $value, string $field, bool $present, array $data): array
    {
        if (!is_array($value)) {
            return [new ValidationViolation(ValidationEnum::ARRAY->value)];
        }

        $count      = count($value);
        $violations = [];

        if ($this->min !== null && $count < $this->min) {
            $violations[] = new ValidationViolation(ValidationEnum::MIN->value, ['min' => $this->min, 'count' => $count]);
        }

        if ($this->max !== null && $count > $this->max) {
            $violations[] = new ValidationViolation(ValidationEnum::MAX->value, ['max' => $this->max, 'count' => $count]);
        }

        if ($this->betweenMin !== null && $this->betweenMax !== null) {
            if ($count < $this->betweenMin || $count > $this->betweenMax) {
                $violations[] = new ValidationViolation(ValidationEnum::BETWEEN->value, [
                    'min' => $this->betweenMin,
                    'max' => $this->betweenMax,
                ]);
            }
        }

        if ($this->size !== null && $count !== $this->size) {
            $violations[] = new ValidationViolation(ValidationEnum::SIZE->value, ['size' => $this->size, 'count' => $count]);
        }

        if ($this->listOnly && !$this->isList($value)) {
            $violations[] = new ValidationViolation(ValidationEnum::LIST->value);
        }

        if ($this->distinct && !$this->isDistinct($value)) {
            $violations[] = new ValidationViolation(ValidationEnum::DISTINCT->value);
        }

        if ($this->contains !== null && !$this->containsAll($value, $this->contains)) {
            $violations[] = new ValidationViolation(ValidationEnum::CONTAINS->value, ['values' => $this->contains]);
        }

        if ($this->doesntContain !== null && $this->containsAny($value, $this->doesntContain)) {
            $violations[] = new ValidationViolation(ValidationEnum::DOESNT_CONTAIN->value, ['values' => $this->doesntContain]);
        }

        if ($this->inArrayField !== null) {
            $allowed = $this->collectValues($data, $this->inArrayField);
            if (!$this->containsAll($allowed, $value)) {
                $violations[] = new ValidationViolation(ValidationEnum::IN_ARRAY->value, ['other' => $this->inArrayField]);
            }
        }

        if ($this->inArrayKeys !== null && $this->inArrayKeys !== []) {
            if (!$this->hasAnyKey($value, $this->inArrayKeys)) {
                $violations[] = new ValidationViolation(ValidationEnum::IN_ARRAY_KEYS->value, [
                    'values' => $this->inArrayKeys,
                ]);
            }
        }

        return $violations;
    }

    public function messages(): array
    {
        return [
            ValidationEnum::ARRAY->value          => 'Поле {field} должно быть массивом.',
            ValidationEnum::MIN->value            => 'Количество элементов в {field} должно быть не меньше {min}.',
            ValidationEnum::MAX->value            => 'Количество элементов в {field} должно быть не больше {max}.',
            ValidationEnum::BETWEEN->value        => 'Количество элементов в {field} должно быть между {min} и {max}.',
            ValidationEnum::SIZE->value           => 'Количество элементов в {field} должно быть равно {size}.',
            ValidationEnum::LIST->value           => 'Поле {field} должно быть списком.',
            ValidationEnum::DISTINCT->value       => 'Поле {field} должно содержать уникальные значения.',
            ValidationEnum::CONTAINS->value       => 'Поле {field} должно содержать {values}.',
            ValidationEnum::DOESNT_CONTAIN->value => 'Поле {field} не должно содержать {values}.',
            ValidationEnum::IN_ARRAY->value       => 'Поле {field} должно присутствовать в значениях {other}.',
            ValidationEnum::IN_ARRAY_KEYS->value  => 'Поле {field} должно иметь хотя бы один ключ из {values}.',
        ];
    }

    /**
     * @param array<int, mixed> $values
     */
    private function containsAll(array $data, array $values): bool
    {
        foreach ($values as $value) {
            if (!in_array($value, $data, true)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param array<int, mixed> $values
     */
    private function containsAny(array $data, array $values): bool
    {
        foreach ($values as $value) {
            if (in_array($value, $data, true)) {
                return true;
            }
        }

        return false;
    }

    private function isDistinct(array $data): bool
    {
        $seen = [];
        foreach ($data as $value) {
            $key = is_object($value) ? (string) spl_object_id($value) : serialize($value);
            if (isset($seen[$key])) {
                return false;
            }
            $seen[$key] = true;
        }

        return true;
    }

    /**
     * @param array<string|int, mixed> $data
     * @param array<int, string|int> $keys
     */
    private function hasAnyKey(array $data, array $keys): bool
    {
        foreach ($keys as $key) {
            if (array_key_exists($key, $data)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array<int, mixed>
     */
    /**
     * @param array<string, mixed> $data
     * @return array<int, mixed>
     */
    private function collectValues(array $data, string $pattern): array
    {
        $matches = DataPath::extract($data, $pattern);
        $values  = [];

        foreach ($matches as $match) {
            if ($match->present) {
                $values[] = $match->value;
            }
        }

        return $values;
    }

    private function isList(array $data): bool
    {
        if (function_exists('array_is_list')) {
            return array_is_list($data);
        }

        $expected = 0;
        foreach ($data as $key => $value) {
            if ($key !== $expected) {
                return false;
            }
            $expected++;
        }

        return true;
    }
}
