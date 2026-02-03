# PhpSoftBox Validator

Компонент для валидации данных через набор правил‑объектов.

## Установка

```bash
composer require phpsoftbox/validator
```

## Быстрый старт

```php
<?php

use PhpSoftBox\Validator\Validator;
use PhpSoftBox\Validator\Rule\StringValidation;
use PhpSoftBox\Validator\Rule\IntValidation;

$validator = new Validator();

$result = $validator->validate(
    data: [
        'name' => 'Alex',
        'age' => 17,
    ],
    rules: [
        'name' => [(new StringValidation())->min(2)->max(50)->required()],
        'age' => [(new IntValidation())->min(18)],
    ],
);

if ($result->hasErrors()) {
    $errors = $result->errorBag()->all();
}
```

## Form Validation Base

Для переиспользуемых валидаторов форм доступны:

- `FormValidationInterface`
- `AbstractFormValidation`

Они позволяют строить form-like валидаторы поверх `Validator` и использовать единые typed getters (`getString`, `getInt`, `getBool`, ...).

## Документация

- [docs/01-usage.md](docs/01-usage.md) — базовое использование и результат валидации
- [docs/02-data-paths.md](docs/02-data-paths.md) — dot‑пути и wildcard
- [docs/03-messages.md](docs/03-messages.md) — сообщения, плейсхолдеры, attributes
- [docs/04-required.md](docs/04-required.md) — required/nullable и required‑сценарии
- `phpsoftbox/filter` — отдельный компонент фильтров для подготовки данных
- [docs/06-specification-executor-migration.md](docs/06-specification-executor-migration.md) — breaking-переход на архитектуру Specification + Executor
- [docs/validators/01-string-validation.md](docs/validators/01-string-validation.md) — StringValidation
- [docs/validators/02-int-validation.md](docs/validators/02-int-validation.md) — IntValidation
- [docs/validators/03-float-validation.md](docs/validators/03-float-validation.md) — FloatValidation
- [docs/validators/04-bool-validation.md](docs/validators/04-bool-validation.md) — BoolValidation
- [docs/validators/05-array-validation.md](docs/validators/05-array-validation.md) — ArrayValidation
- [docs/validators/06-present-validation.md](docs/validators/06-present-validation.md) — PresentValidation
- [docs/validators/07-filled-validation.md](docs/validators/07-filled-validation.md) — FilledValidation
- [docs/validators/08-missing-validation.md](docs/validators/08-missing-validation.md) — MissingValidation
- [docs/validators/09-exclude-validation.md](docs/validators/09-exclude-validation.md) — ExcludeValidation
- [docs/validators/10-prohibited-validation.md](docs/validators/10-prohibited-validation.md) — ProhibitedValidation
- [docs/validators/11-prohibits-validation.md](docs/validators/11-prohibits-validation.md) — ProhibitsValidation
- [docs/validators/12-anyof-validation.md](docs/validators/12-anyof-validation.md) — AnyOfValidation
- [docs/validators/13-bail-validation.md](docs/validators/13-bail-validation.md) — BailValidation
- [docs/validators/14-date-validation.md](docs/validators/14-date-validation.md) — DateValidation
- [docs/validators/15-file-validation.md](docs/validators/15-file-validation.md) — FileValidation
- `phpsoftbox/validator-db` — DB rules ExistsValidation и UniqueValidation
- [docs/validators/18-phone-validation.md](docs/validators/18-phone-validation.md) — PhoneValidation

## Режимы остановки

```php
use PhpSoftBox\Validator\ValidationOptions;
use PhpSoftBox\Validator\ValidationStopMode;

$options = new ValidationOptions(stopMode: ValidationStopMode::FIRST_PER_FIELD);
```
