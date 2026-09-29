# AnyOfValidation

Считает правило успешным, если проходит хотя бы одно из переданных правил.
Если все правила не прошли, возвращается ошибка `any_of`.

## Методы

- `__construct(ValidationRuleInterface ...$rules)`
- `required()/nullable()` — см. required‑сценарии

## Выполнение вложенных правил

При проверке через `Validator` вложенные правила выполняются тем же конвейером, что
и обычные: specification‑правила (например, DB‑правила из `phpsoftbox/validator-db`)
выполняются через `RuleExecutorRegistry`, а правилам доступны `context()` и `route()`.

Прямой вызов `AnyOfValidation::validate()` (без `Validator`) проверяет только обычные
правила и не передаёт им runtime‑состояние.

Флаги `required*`/`nullable` вложенных правил не учитываются — задавайте их на самом `AnyOfValidation`.

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
