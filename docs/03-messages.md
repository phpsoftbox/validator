# Сообщения и плейсхолдеры

## Кастомные сообщения

```php
use PhpSoftBox\Validator\ValidationEnum;

$messages = [
    'name' => [
        ValidationEnum::REQUIRED->value => 'Поле {field} обязательно.',
    ],
];
```

Можно задавать сообщения и на уровне правила:

```php
use PhpSoftBox\Validator\Rule\StringValidation;
use PhpSoftBox\Validator\ValidationEnum;

$rule = (new StringValidation())
    ->message(ValidationEnum::MIN, 'Минимум {min} символа.');
```

## Плейсхолдеры

Доступные значения:
- `{field}` — название поля (с учетом `attributes`)
- `{value}` — текущее значение
- `{min}` / `{max}` — параметры правил
- `{size}` — точный размер
- `{digits}` — количество цифр
- `{multiple}` — кратность (делитель)
- `{other}` — связанное поле
- `{values}` — список значений (JSON‑формат)
- `{pattern}` — регулярное выражение
- `{date}` — дата для сравнения
- `{formats}` — список форматов даты (JSON‑формат)
- `{table}` — таблица базы данных
- `{columns}` — список колонок (JSON‑формат)
- `{connection}` — имя подключения
- `{min_width}` / `{max_width}` — минимальная/максимальная ширина
- `{min_height}` / `{max_height}` — минимальная/максимальная высота
- `{width}` / `{height}` — точные размеры
- `{ratio}` — соотношение сторон

## Attributes

```php
$attributes = [
    'user.name' => 'Имя',
];
```

Если используется wildcard, можно задать шаблон:

```php
$attributes = [
    'items.*.name' => 'Название',
];
```
