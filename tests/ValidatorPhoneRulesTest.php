<?php

declare(strict_types=1);

namespace PhpSoftBox\Validator\Tests;

use PhpSoftBox\Filter\Phone\Drivers\PhoneDriverEnum;
use PhpSoftBox\Validator\Rule\PhoneValidation;
use PhpSoftBox\Validator\Validator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(PhoneValidation::class)]
#[CoversMethod(PhoneValidation::class, 'international')]
#[CoversMethod(PhoneValidation::class, 'validate')]
final class ValidatorPhoneRulesTest extends TestCase
{
    /**
     * Проверяет, что валидный телефон проходит проверку.
     */
    #[Test]
    public function phoneValidationAcceptsValidPhone(): void
    {
        $validator = new Validator();
        $rules     = [
            'phone' => [new PhoneValidation()],
        ];

        $result = $validator->validate(['phone' => '+7 (999) 123-45-67'], $rules);

        self::assertFalse($result->hasErrors());
    }

    /**
     * Проверяет, что невалидный телефон возвращает ошибку.
     */
    #[Test]
    public function phoneValidationRejectsInvalidPhone(): void
    {
        $validator = new Validator();
        $rules     = [
            'phone' => [new PhoneValidation()],
        ];

        $result = $validator->validate(['phone' => '123'], $rules);

        self::assertSame(
            ['Поле phone должно быть корректным номером телефона. Указан некорректный код оператора.'],
            $result->errorBag()->get('phone'),
        );
    }

    /**
     * Проверяет, что невалидная длина телефона возвращает ошибку длины.
     */
    #[Test]
    public function phoneValidationRejectsInvalidLength(): void
    {
        $validator = new Validator();
        $rules     = [
            'phone' => [new PhoneValidation()],
        ];

        $result = $validator->validate(['phone' => '900123'], $rules);

        self::assertSame(
            ['Поле phone должно быть корректным номером телефона. Номер телефона имеет некорректную длину.'],
            $result->errorBag()->get('phone'),
        );
    }

    /**
     * Проверяет, что валидация работает для AM/AZ/BY драйверов.
     */
    #[Test]
    public function phoneValidationAcceptsValidPhonesForNewDrivers(): void
    {
        $validator = new Validator();

        $am = $validator->validate(
            ['phone' => '+374 (77) 123-456'],
            ['phone' => [new PhoneValidation(PhoneDriverEnum::AM)]],
        );
        self::assertFalse($am->hasErrors());

        $az = $validator->validate(
            ['phone' => '+994 (50) 123-45-67'],
            ['phone' => [new PhoneValidation(PhoneDriverEnum::AZ)]],
        );
        self::assertFalse($az->hasErrors());

        $by = $validator->validate(
            ['phone' => '+375 (29) 123-45-67'],
            ['phone' => [new PhoneValidation(PhoneDriverEnum::BY)]],
        );
        self::assertFalse($by->hasErrors());
    }

    /**
     * Проверяет, что в режиме нескольких стран принимаются номер Китая и сохранённый E.164 России.
     *
     * @see PhoneValidation::international()
     * @see PhoneValidation::validate()
     */
    #[Test]
    public function internationalPhoneValidationAcceptsNumbersOfSupportedCountries(): void
    {
        $validator = new Validator();
        $rules     = [
            'cn' => [new PhoneValidation()->international()],
            'ru' => [new PhoneValidation()->international()],
        ];

        $result = $validator->validate(['cn' => '+86 138 1234 5678', 'ru' => '+79991234567'], $rules);

        self::assertFalse($result->hasErrors());
    }

    /**
     * Проверяет, что номер страны вне списка допустимых отклоняется.
     *
     * @see PhoneValidation::international()
     * @see PhoneValidation::validate()
     */
    #[Test]
    public function internationalPhoneValidationRejectsCountryOutsideList(): void
    {
        $validator = new Validator();
        $rules     = [
            'phone' => [new PhoneValidation()->international([PhoneDriverEnum::RU])],
        ];

        $result = $validator->validate(['phone' => '+86 138 1234 5678'], $rules);

        self::assertSame(['Поле phone должно быть корректным номером телефона.'], $result->errorBag()->get('phone'));
    }
}
