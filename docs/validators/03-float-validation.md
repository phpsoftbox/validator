# FloatValidation

Проверяет, что значение является числом.

## Методы

### numeric
`numeric()` — использовать правило `numeric` вместо `float`.

### min / max / between / size
- `min(float $min)` — минимальное значение
- `max(float $max)` — максимальное значение
- `between(float $min, float $max)` — значение в диапазоне
- `size(float $size)` — значение равно размеру

### digits / digitsBetween / minDigits / maxDigits
- `digits(int $digits)` — точное количество цифр (только для целых значений)
- `digitsBetween(int $min, int $max)` — диапазон цифр
- `minDigits(int $min)` — минимум цифр
- `maxDigits(int $max)` — максимум цифр

### decimal
`decimal(int $min, ?int $max = null)` — количество знаков после запятой.

### multipleOf
`multipleOf(float $divisor)` — значение кратно делителю.

### in
`in(float ...$values)` — значение в списке.

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
`float`, `numeric`, `min`, `max`, `between`, `size`,
`digits`, `digits_between`, `min_digits`, `max_digits`,
`decimal`, `multiple_of`, `in`, `same`, `different`,
`greater_than`, `greater_than_or_equal`, `less_than`, `less_than_or_equal`.

## Пример

```php
use PhpSoftBox\Validator\Rule\FloatValidation;

$rules = [
    'price' => [(new FloatValidation())->min(0.01)],
];
```
