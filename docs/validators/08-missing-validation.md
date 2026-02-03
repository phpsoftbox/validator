# MissingValidation

Проверяет, что поле отсутствует в данных.
Без условий работает как `missing`.
Если поле присутствует, возвращается ошибка.

## Методы

### missingIf
Запрещает наличие поля, если `field` равно любому из `values`.

### missingUnless
Запрещает наличие поля, если `field` не равно ни одному из `values`.

### missingWith
Запрещает наличие поля при наличии хотя бы одного из `fields`.

### missingWithAll
Запрещает наличие поля при наличии всех `fields`.

### Ограничения
Методы `required*` и `nullable` не поддерживаются.

Сравнение значений выполняется строго (`===`).

## Сообщения

- `missing`
- `missing_if`
- `missing_unless`
- `missing_with`
- `missing_with_all`

## Пример

```php
use PhpSoftBox\Validator\Rule\MissingValidation;

$rules = [
    'token' => [(new MissingValidation())->missingWith('guest')],
];
```
