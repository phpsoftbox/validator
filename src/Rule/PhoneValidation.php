<?php

declare(strict_types=1);

namespace PhpSoftBox\Validator\Rule;

use PhpSoftBox\Filter\Phone\Drivers\PhoneCountryDriverInterface;
use PhpSoftBox\Filter\Phone\Drivers\PhoneDriverEnum;
use PhpSoftBox\Validator\ValidationEnum;
use PhpSoftBox\Validator\ValidationViolation;

use function is_numeric;
use function is_string;

/**
 * Проверяет корректность номера телефона через драйверы стран.
 */
final class PhoneValidation extends AbstractRule
{
    /**
     * Драйвер конкретной страны.
     */
    private PhoneCountryDriverInterface $driver;

    /**
     * Возвращать формат для хранения в БД.
     */
    private bool $prepareForDb = true;

    /**
     * Форматировать с кодом страны.
     */
    private bool $withCountryCode = false;

    public function __construct(PhoneDriverEnum $driver = PhoneDriverEnum::RU)
    {
        $this->driver = $driver->create();
    }

    public function driver(PhoneDriverEnum $driver): self
    {
        $this->driver = $driver->create();

        return $this;
    }

    public function prepareForDb(bool $value = true): self
    {
        $this->prepareForDb = $value;

        return $this;
    }

    public function withCountryCode(bool $value = true): self
    {
        $this->withCountryCode = $value;

        return $this;
    }

    public function validate(mixed $value, string $field, bool $present, array $data): array
    {
        if (!is_string($value) && !is_numeric($value)) {
            return [new ValidationViolation(ValidationEnum::PHONE->value)];
        }

        $raw    = (string) $value;
        $result = $this->driver->format($raw, $this->prepareForDb, $this->withCountryCode);

        if ($result->isValid()) {
            return [];
        }

        $reason       = $result->getErrorMessage();
        $reasonSuffix = $reason !== null && $reason !== '' ? ' ' . $reason : '';

        return [new ValidationViolation(ValidationEnum::PHONE->value, ['reason' => $reasonSuffix])];
    }

    public function messages(): array
    {
        return [
            ValidationEnum::PHONE->value => 'Поле {field} должно быть корректным номером телефона.{reason}',
        ];
    }
}
