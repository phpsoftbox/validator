# IntValidation

Проверяет, что значение является целым числом.

## Методы

### min / max / between / size
- `min(int $min)` — минимальное значение
- `max(int $max)` — максимальное значение
- `between(int $min, int $max)` — значение в диапазоне
- `size(int $size)` — значение равно размеру

### digits / digitsBetween / minDigits / maxDigits
- `digits(int $digits)` — точное количество цифр
- `digitsBetween(int $min, int $max)` — диапазон цифр
- `minDigits(int $min)` — минимум цифр
- `maxDigits(int $max)` — максимум цифр

### multipleOf
`multipleOf(int $divisor)` — значение кратно делителю.

### in
`in(int ...$values)` — значение в списке.

### same / different
- `same(string $field)` — равно значению другого поля
- `different(string $field)` — отличается от значения другого поля

### greaterThan / greaterThanOrEqual / lessThan / lessThanOrEqual
Сравнения с другим полем:
- `greaterThan(string $field)`
- `greaterThanOrEqual(string $field)`
- `lessThan(string $field)`
- `lessThanOrEqual(string $field)`

### required / nullable
Доступны, см. required‑сценарии.

## Сообщения

Основные ключи:
`integer`, `min`, `max`, `between`, `size`,
`digits`, `digits_between`, `min_digits`, `max_digits`,
`multiple_of`, `in`, `same`, `different`,
`greater_than`, `greater_than_or_equal`, `less_than`, `less_than_or_equal`.

## Пример

```php
use PhpSoftBox\Validator\Rule\IntValidation;

$rules = [
    'age' => [(new IntValidation())->min(18)->max(100)],
];
```
