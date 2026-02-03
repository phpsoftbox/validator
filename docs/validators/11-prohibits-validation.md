# ProhibitsValidation

Если поле присутствует, запрещает наличие других полей.
Если поле отсутствует, ошибок нет.

## Методы

- `__construct(string ...$fields)`
- `required()/nullable()` — см. required‑сценарии

## Сообщения

- `prohibits`

## Пример

```php
use PhpSoftBox\Validator\Rule\ProhibitsValidation;

$rules = [
    'role' => [new ProhibitsValidation('admin_flag')],
];
```
