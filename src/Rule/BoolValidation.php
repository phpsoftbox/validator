<?php

declare(strict_types=1);

namespace PhpSoftBox\Validator\Rule;

use PhpSoftBox\Validator\Support\DataPath;
use PhpSoftBox\Validator\ValidationEnum;
use PhpSoftBox\Validator\ValidationViolation;

use function in_array;
use function is_bool;
use function is_string;
use function strtolower;
use function trim;

/**
 * Проверяет булевы значения и правила accepted/declined.
 */
final class BoolValidation extends AbstractRule
{
    /**
     * Требовать принятие (true/yes/1).
     */
    private bool $accepted = false;
    /**
     * Требовать отклонение (false/no/0).
     */
    private bool $declined = false;
    /**
     * Условные правила принятия (accepted_if).
     *
     * @var list<array{field: string, values: array}>
     */
    private array $acceptedIf = [];
    /**
     * Условные правила отклонения (declined_if).
     *
     * @var list<array{field: string, values: array}>
     */
    private array $declinedIf = [];

    /**
     * Значение должно быть принято.
     */
    public function accepted(): self
    {
        $this->accepted = true;

        return $this;
    }

    /**
     * Значение должно быть принято, если связанное поле равно любому из значений.
     */
    public function acceptedIf(string $field, mixed ...$values): self
    {
        $this->acceptedIf[] = ['field' => $field, 'values' => $values];

        return $this;
    }

    /**
     * Значение должно быть отклонено.
     */
    public function declined(): self
    {
        $this->declined = true;

        return $this;
    }

    /**
     * Значение должно быть отклонено, если связанное поле равно любому из значений.
     */
    public function declinedIf(string $field, mixed ...$values): self
    {
        $this->declinedIf[] = ['field' => $field, 'values' => $values];

        return $this;
    }

    public function validate(mixed $value, string $field, bool $present, array $data): array
    {
        $acceptedIfRule = $this->acceptedIfViolation($value, $data);
        if ($acceptedIfRule !== null) {
            return [$acceptedIfRule];
        }

        $declinedIfRule = $this->declinedIfViolation($value, $data);
        if ($declinedIfRule !== null) {
            return [$declinedIfRule];
        }

        if ($this->accepted && !$this->isAccepted($value)) {
            return [new ValidationViolation(ValidationEnum::ACCEPTED->value)];
        }

        if ($this->declined && !$this->isDeclined($value)) {
            return [new ValidationViolation(ValidationEnum::DECLINED->value)];
        }

        if (is_bool($value)) {
            return [];
        }

        if (($value === 0 || $value === 1)) {
            return [];
        }

        if (is_string($value)) {
            $normalized = strtolower(trim($value));
            if (in_array($normalized, ['1', '0', 'true', 'false', 'yes', 'no', 'on', 'off'], true)) {
                return [];
            }
        }

        return [new ValidationViolation(ValidationEnum::BOOLEAN->value)];
    }

    public function messages(): array
    {
        return [
            ValidationEnum::BOOLEAN->value     => 'Поле {field} должно быть булевым.',
            ValidationEnum::ACCEPTED->value    => 'Поле {field} должно быть принято.',
            ValidationEnum::ACCEPTED_IF->value => 'Поле {field} должно быть принято, если {other} равно {values}.',
            ValidationEnum::DECLINED->value    => 'Поле {field} должно быть отклонено.',
            ValidationEnum::DECLINED_IF->value => 'Поле {field} должно быть отклонено, если {other} равно {values}.',
        ];
    }

    private function acceptedIfViolation(mixed $value, array $data): ?ValidationViolation
    {
        foreach ($this->acceptedIf as $condition) {
            $field   = $condition['field'];
            $values  = $condition['values'];
            $matches = DataPath::extract($data, $field);
            foreach ($matches as $match) {
                if (!$match->present) {
                    continue;
                }
                if (in_array($match->value, $values, true)) {
                    if (!$this->isAccepted($value)) {
                        return new ValidationViolation(ValidationEnum::ACCEPTED_IF->value, [
                            'other'  => $field,
                            'values' => $values,
                        ]);
                    }

                    return null;
                }
            }
        }

        return null;
    }

    private function declinedIfViolation(mixed $value, array $data): ?ValidationViolation
    {
        foreach ($this->declinedIf as $condition) {
            $field   = $condition['field'];
            $values  = $condition['values'];
            $matches = DataPath::extract($data, $field);
            foreach ($matches as $match) {
                if (!$match->present) {
                    continue;
                }
                if (in_array($match->value, $values, true)) {
                    if (!$this->isDeclined($value)) {
                        return new ValidationViolation(ValidationEnum::DECLINED_IF->value, [
                            'other'  => $field,
                            'values' => $values,
                        ]);
                    }

                    return null;
                }
            }
        }

        return null;
    }
}
