# AnyOfValidation

Считает правило успешным, если проходит хотя бы одно из переданных правил.
Если все правила не прошли, возвращается ошибка `any_of`.

## Методы

- `__construct(ValidationRuleInterface ...$rules)`
- `required()/nullable()` — см. required‑сценарии

## Сообщения

- `any_of`

## Пример

```php
use PhpSoftBox\Validator\Rule\AnyOfValidation;
use PhpSoftBox\Validator\Rule\IntValidation;
use PhpSoftBox\Validator\Rule\StringValidation;

$rules = [
    'value' => [new AnyOfValidation(new IntValidation(), new StringValidation())],
];
```
