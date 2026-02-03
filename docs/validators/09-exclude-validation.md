# ExcludeValidation

Исключает поле из валидации и из `filteredData`.
Исключенное поле не валидируется и не влияет на ошибки.
Без условий работает как `exclude`.

## Методы

### exclude
Исключает поле всегда.

### excludeIf
Исключает поле при выполнении callback.  
Callback получает `context` из `Validator::validate`, если он передан, иначе массив данных.

### excludeUnless
Исключает поле, если `field` не равно ни одному из `values`.

### excludeWith
Исключает поле при наличии хотя бы одного из `fields`.

### excludeWithAll
Исключает поле при наличии всех `fields`.

### excludeWithout
Исключает поле, если отсутствует хотя бы одно из `fields`.

### excludeWithoutAll
Исключает поле, если отсутствуют все `fields`.

### Ограничения
Методы `required*` и `nullable` не поддерживаются.

Сравнение значений выполняется строго (`===`).

## Пример

```php
use PhpSoftBox\Validator\Rule\ExcludeValidation;
use PhpSoftBox\Validator\Rule\StringValidation;

$rules = [
    'name' => [
        (new ExcludeValidation())->excludeWith('skip'),
        (new StringValidation())->min(3),
    ],
];
```
