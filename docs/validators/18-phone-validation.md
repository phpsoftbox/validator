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
    ->driver(PhoneDriverEnum::RU);
```

- `driver()` — выбор драйвера страны (можно передать и в конструктор).

## Нормализация номера

Правило только проверяет номер и не изменяет значение: в `filteredData` попадает
исходная строка. Для приведения номера к формату хранения или вывода используйте
фильтр из `phpsoftbox/filter` до валидации (например, в `applyPayloadFilters()` формы):

```php
use PhpSoftBox\Filter\PhoneFilter;
use PhpSoftBox\Filter\Phone\Drivers\PhoneDriverEnum;

$phoneFilter = new PhoneFilter(
    driver: PhoneDriverEnum::RU,
    prepareForDb: true,
    withCountryCode: false,
);
```

или `FilterAdapter::phone($value, prepareForDb: true, withCountryCode: false)`.

Методы `prepareForDb()` и `withCountryCode()` удалены из `PhoneValidation`:
они не влияли на результат проверки.

Сообщение об ошибке может содержать причину (например, неверная длина).
