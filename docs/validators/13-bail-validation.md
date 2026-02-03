# BailValidation

Останавливает проверку текущего поля после первой ошибки.
Не влияет на другие поля.

## Методы

- `__construct()`

## Ограничения

Методы `required*` и `nullable` не поддерживаются.

## Пример

```php
use PhpSoftBox\Validator\Rule\BailValidation;
use PhpSoftBox\Validator\Rule\StringValidation;

$rules = [
    'name' => [
        new BailValidation(),
        (new StringValidation())->min(2),
    ],
];
```
