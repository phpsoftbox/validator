# Использование

## Базовый вызов

```php
use PhpSoftBox\Validator\Validator;
use PhpSoftBox\Validator\Rule\StringValidation;

$validator = new Validator();

$result = $validator->validate(
    data: ['name' => 'Alex'],
    rules: [
        'name' => [(new StringValidation())->min(2)],
    ],
);
```

Для specification-правил (например, DB-правила) `Validator` должен быть создан
с `RuleExecutorRegistry`, содержащим executor-ы для этих правил.

## Сигнатура validate

```php
public function validate(
    array $data,
    array $rules,
    array $messages = [],
    array $attributes = [],
    ?ValidationOptions $options = null,
    mixed $context = null,
): ValidationResult
```

Параметры:
- `data` — входные данные
- `rules` — правила валидации по путям
- `messages` — кастомные сообщения (см. `docs/03-messages.md`)
- `attributes` — человекочитаемые имена полей
- `options` — режимы остановки
- `context` — произвольный контекст для callback‑правил

## ValidationResult

- `hasErrors()` — есть ли ошибки
- `errors()` — массив ошибок по полям
- `errorBag()` — объект для работы с ошибками (`has/get/all`)
- `filteredData()` — только валидные поля
- `only()` / `except()` / `all()` — выборка из `filteredData`

`filteredData` не содержит поля, исключённые через `ExcludeValidation`.

## Режимы остановки

```php
use PhpSoftBox\Validator\ValidationOptions;
use PhpSoftBox\Validator\ValidationStopMode;

$options = new ValidationOptions(stopMode: ValidationStopMode::FIRST_PER_FIELD);
```

Доступные режимы:
- `FIRST_ERROR` — остановка при первой ошибке
- `FIRST_PER_FIELD` — первая ошибка на поле
- `ALL` — собрать все ошибки

## Контекст

`context` передается в callback‑правила, например `excludeIf` или `requiredIf`.
Если `context` не задан, callback получает массив `data`.
