# PresentValidation

Проверяет, что поле присутствует в данных (значение может быть пустым).
Без условий работает как `present`.
Если поле отсутствует, возвращается ошибка.

## Методы

### presentIf
Требует присутствие поля, если `field` равно любому из `values`.

### presentUnless
Требует присутствие поля, если `field` не равно ни одному из `values`.

### presentWith
Требует присутствие поля, если присутствует хотя бы одно из `fields`.

### presentWithAll
Требует присутствие поля, если присутствуют все `fields`.

### Ограничения
Методы `required*` и `nullable` не поддерживаются.

Сравнение значений выполняется строго (`===`).

## Сообщения

- `present`
- `present_if`
- `present_unless`
- `present_with`
- `present_with_all`

## Пример

```php
use PhpSoftBox\Validator\Rule\PresentValidation;

$rules = [
    'email' => [(new PresentValidation())->presentIf('status', 'active')],
];
```
