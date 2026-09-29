# Required и nullable

## required

```php
use PhpSoftBox\Validator\Rule\StringValidation;

$rule = (new StringValidation())->required();
```

Если обязательное поле отсутствует или пустое (`null`, `''`, `[]`), возвращается только
ошибка `required` (или `required_*`), остальные правила этого поля не выполняются —
в любом режиме остановки.

## nullable

Если значение пустое и правило nullable — валидатор пропускает остальные проверки,
а значение (например, `null`) попадает в `filteredData`.

```php
$rule = (new StringValidation())->nullable();
```

## required-сценарии

Доступные методы:
- `requiredIf(callable $callback, string $other = 'условие')`
- `requiredIfAccepted(string $field)`
- `requiredIfDeclined(string $field)`
- `requiredUnless(string $field, mixed ...$values)`
- `requiredWith(string ...$fields)`
- `requiredWithAll(string ...$fields)`
- `requiredWithout(string ...$fields)`
- `requiredWithoutAll(string ...$fields)`

Пример:

```php
$rule = (new StringValidation())
    ->requiredIfAccepted('terms')
    ->requiredUnless('role', 'guest');
```

## Ограничения

Методы `required*` и `nullable` не поддерживаются для следующих правил:
- `PresentValidation`
- `MissingValidation`
- `FilledValidation`
- `ExcludeValidation`
- `ProhibitedValidation`
- `BailValidation`

Для обязательности используйте:
- `PresentValidation` / `present*` — чтобы требовать наличие поля;
- типовые правила (`StringValidation`, `IntValidation` и т.д.) — чтобы совместить тип и required‑сценарии.

При попытке вызвать `required*` или `nullable` на этих правилах будет выброшен `LogicException`.
