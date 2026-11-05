<?php

declare(strict_types=1);

namespace PhpSoftBox\Validator\Rule;

use PhpSoftBox\Filter\Phone\Drivers\PhoneCountryDriverInterface;
use PhpSoftBox\Filter\Phone\Drivers\PhoneDriverEnum;
use PhpSoftBox\Filter\Phone\PhoneNumber;
use PhpSoftBox\Validator\ValidationEnum;
use PhpSoftBox\Validator\ValidationViolation;

use function is_numeric;
use function is_string;

/**
 * Проверяет корректность номера телефона через драйверы стран.
 *
 * По умолчанию — номер одной страны (драйвер RU). {@see self::international()} принимает номер любой поддержанной
 * страны (или из списка допустимых), в том числе сохранённый E.164.
 *
 * Значение не изменяется: для приведения номера к формату хранения используйте
 * фильтр `PhoneFilter` / `InternationalPhoneFilter` из `phpsoftbox/filter`.
 */
final class PhoneValidation extends AbstractRule
{
    /**
     * Драйвер конкретной страны.
     */
    private PhoneCountryDriverInterface $driver;

    /**
     * Режим нескольких стран: допустимые страны (null — все); false — режим одной страны.
     *
     * @var list<PhoneDriverEnum>|false|null
     */
    private array|false|null $countries = false;

    private bool $mobileOnly = true;

    public function __construct(PhoneDriverEnum $driver = PhoneDriverEnum::RU)
    {
        $this->driver = $driver->create();
    }

    public function driver(PhoneDriverEnum $driver): self
    {
        $this->driver    = $driver->create();
        $this->countries = false;

        return $this;
    }

    /**
     * Номер любой поддержанной страны; $countries — допустимые страны (null — все), $mobileOnly — как у драйвера.
     *
     * @param list<PhoneDriverEnum>|null $countries
     */
    public function international(?array $countries = null, bool $mobileOnly = true): self
    {
        $this->countries  = $countries;
        $this->mobileOnly = $mobileOnly;

        return $this;
    }

    public function validate(mixed $value, string $field, bool $present, array $data): array
    {
        if (!is_string($value) && !is_numeric($value)) {
            return [new ValidationViolation(ValidationEnum::PHONE->value)];
        }

        $raw = (string) $value;

        if ($this->countries !== false) {
            return PhoneNumber::parse($raw, candidates: $this->countries, mobileOnly: $this->mobileOnly) instanceof PhoneNumber
                ? []
                : [new ValidationViolation(ValidationEnum::PHONE->value, ['reason' => ''])];
        }

        // Правило только проверяет номер; нормализация значения выполняется фильтром PhoneFilter.
        $result = $this->driver->format($raw, prepareForDb: true, withCountryCode: false);

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
