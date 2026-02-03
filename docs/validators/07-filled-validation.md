# FilledValidation

Проверяет, что поле не пустое.

## Методы

### filled
Проверяет, что значение не пустое, если поле присутствует в данных.
Пустыми считаются: `null`, пустая строка и пустой массив.
Если поле отсутствует, ошибок нет.

### Ограничения
Методы `required*` и `nullable` не поддерживаются.
Если нужно требовать присутствие поля, используйте `PresentValidation`.

## Сообщения

- `filled`

## Пример

```php
use PhpSoftBox\Validator\Rule\FilledValidation;

$rules = [
    'title' => [new FilledValidation()],
];
```
