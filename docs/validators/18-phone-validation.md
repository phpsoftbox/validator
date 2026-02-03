# PhoneValidation

Проверяет, что значение является корректным номером телефона.

Используются встроенные драйверы стран (RU/KZ/AM/AZ/BY) и проверка длины/кодов операторов.

## Пример

```php
use PhpSoftBox\Validator\Rule\PhoneValidation;

$rules = [
    'phone' => [
        new PhoneValidation(),
    ],
];
```

## Настройки

```php
use PhpSoftBox\Validator\Rule\PhoneValidation;
use PhpSoftBox\Filter\Phone\Drivers\PhoneDriverEnum;

$rule = (new PhoneValidation())
    ->driver(PhoneDriverEnum::RU)
    ->prepareForDb(true)
    ->withCountryCode(false);
```

- `driver()` — выбор драйвера страны.
- `prepareForDb()` — использовать формат для хранения в БД (по умолчанию `true`).
- `withCountryCode()` — форматировать с кодом страны (для вывода).

Сообщение об ошибке может содержать причину (например, неверная длина).
