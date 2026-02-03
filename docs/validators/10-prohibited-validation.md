# ProhibitedValidation

Запрещает наличие поля.
Без условий работает как `prohibited`.
Если поле отсутствует, ошибок нет.

## Методы

### prohibited
Запрещает поле всегда, если оно присутствует.

### prohibitedIf
Запрещает поле, если `field` равно любому из `values`.

### prohibitedUnless
Запрещает поле, если `field` не равно ни одному из `values`.

### prohibitedIfAccepted
Запрещает поле, если `field` принято (`yes`, `on`, `1`, `true`).

### prohibitedIfDeclined
Запрещает поле, если `field` отклонено (`no`, `off`, `0`, `false`).

### Ограничения
Методы `required*` и `nullable` не поддерживаются.

Сравнение значений выполняется строго (`===`).

## Сообщения

- `prohibited`
- `prohibited_if`
- `prohibited_unless`
- `prohibited_if_accepted`
- `prohibited_if_declined`

## Пример

```php
use PhpSoftBox\Validator\Rule\ProhibitedValidation;

$rules = [
    'token' => [(new ProhibitedValidation())->prohibitedIf('status', 'banned')],
];
```
