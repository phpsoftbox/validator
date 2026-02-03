# ArrayValidation

Проверяет, что значение является массивом.

## Методы

### min / max / between / size
- `min(int $min)` — минимальная длина массива
- `max(int $max)` — максимальная длина массива
- `between(int $min, int $max)` — длина в диапазоне
- `size(int $size)` — длина равна размеру

### listOnly
`listOnly()` — массив должен быть списком.

### distinct
`distinct()` — элементы массива должны быть уникальны.

### contains / doesntContain
- `contains(mixed ...$values)` — массив содержит значения
- `doesntContain(mixed ...$values)` — массив не содержит значения

### inArray / inArrayKeys
- `inArray(string $field)` — каждый элемент массива присутствует в значениях другого массива (поддерживает wildcard, например `items.*`)
- `inArrayKeys(string|int ...$keys)` — массив содержит хотя бы один из указанных ключей

### required / nullable
Доступны, см. required‑сценарии.

## Сообщения

Основные ключи:
`array`, `min`, `max`, `between`, `size`,
`list`, `distinct`, `contains`, `doesnt_contain`,
`in_array`, `in_array_keys`.

## Пример

```php
use PhpSoftBox\Validator\Rule\ArrayValidation;

$rules = [
    'tags' => [(new ArrayValidation())->min(1)],
];
```
