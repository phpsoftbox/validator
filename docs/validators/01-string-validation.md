# StringValidation

Проверяет, что значение является строкой, и применяет набор строковых правил.

## Методы

### min / max / size
Ограничения по длине строки:
- `min(int $min)`
- `max(int $max)`
- `size(int $size)`

### alpha / alphaDash / alphaNumeric
- `alpha()` — только буквы
- `alphaDash()` — буквы, цифры, `-` и `_`
- `alphaNumeric()` — буквы и цифры

### ascii
`ascii()` — только ASCII символы.

### email / url / activeUrl
- `email()` — корректный email
- `url()` — корректный URL
- `activeUrl()` — URL с активным хостом (DNS или IP)
Для доменных имен используется DNS‑проверка, для IP — без DNS.

### ipAddress / macAddress
- `ipAddress()` — корректный IP адрес
- `macAddress()` — корректный MAC адрес

### json
`json()` — корректный JSON.

### lowercase / uppercase
- `lowercase()` — строка в нижнем регистре
- `uppercase()` — строка в верхнем регистре

### hexColor
`hexColor()` — hex‑цвет `#RGB` или `#RRGGBB`.

### uuid / ulid
- `uuid()` — UUID формата `8-4-4-4-12`
- `ulid()` — ULID (26 символов Crockford Base32)

### startsWith / endsWith
- `startsWith(string ...$needles)`
- `endsWith(string ...$needles)`

### doesntStartWith / doesntEndWith
- `doesntStartWith(string ...$needles)`
- `doesntEndWith(string ...$needles)`

### in / notIn
- `in(string ...$values)`
- `notIn(string ...$values)`
Сравнение значений выполняется строго (`===`).

### regex / notRegex
- `regex(string $pattern)`
- `notRegex(string $pattern)`
Если выражение некорректно, будет выброшен `InvalidArgumentException`.

### same / different / confirmed
- `same(string $field)` — равно другому полю
- `different(string $field)` — отличается от другого поля
- `confirmed(?string $field = null)` — совпадает с полем подтверждения (`{field}_confirmation`)
Сравнение значений выполняется строго (`===`).

### currentPassword
`currentPassword(callable $checker)` — проверка текущего пароля.  
Callback получает `(string $value, array $data): bool`.  
Если callback принимает один аргумент, ему передается только значение.

### enumClass
`enumClass(string $enumClass)` — значение входит в enum (`BackedEnum` или enum по имени кейса).
Если enum backed, сравнение идёт по `value`, иначе по `name`.
Если класс не является enum, будет выброшен `InvalidArgumentException`.

### required / nullable
Доступны, см. required‑сценарии.

## Сообщения

Основные ключи:
`string`, `min`, `max`, `size`, `alpha`, `alpha_dash`, `alpha_numeric`, `ascii`,
`email`, `url`, `active_url`, `ip_address`, `mac_address`, `json`,
`lowercase`, `uppercase`, `hex_color`, `uuid`, `ulid`,
`starts_with`, `ends_with`, `doesnt_start_with`, `doesnt_end_with`,
`in`, `not_in`, `regex`, `not_regex`, `same`, `different`, `confirmed`,
`current_password`, `enum`.

## Пример

```php
use PhpSoftBox\Validator\Rule\StringValidation;

$rules = [
    'email' => [(new StringValidation())->email()->lowercase()],
    'token' => [(new StringValidation())->size(32)->alphaNumeric()],
];
```
