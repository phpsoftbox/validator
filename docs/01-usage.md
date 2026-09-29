# Использование

## Базовый вызов

```php
use PhpSoftBox\Validator\Validator;
use PhpSoftBox\Validator\Rule\StringValidation;

$validator = new Validator();

$result = $validator->validate(
    data: ['name' => 'Alex'],
    rules: [
        'name' => [(new StringValidation())->min(2)],
    ],
);
```

Для specification-правил (например, DB-правила) `Validator` должен быть создан
с `RuleExecutorRegistry`, содержащим executor-ы для этих правил.

## Сигнатура validate

```php
public function validate(
    array $data,
    array $rules,
    array $messages = [],
    array $attributes = [],
    ?ValidationOptions $options = null,
    mixed $context = null,
): ValidationResult
```

Параметры:
- `data` — входные данные
- `rules` — правила валидации по путям
- `messages` — кастомные сообщения (см. `docs/03-messages.md`)
- `attributes` — человекочитаемые имена полей
- `options` — режимы остановки
- `context` — произвольный контекст для callback‑правил

## ValidationResult

- `hasErrors()` — есть ли ошибки
- `errors()` — массив ошибок по полям
- `errorBag()` — объект для работы с ошибками (`has/get/all`)
- `filteredData()` — только поля, покрытые правилами и прошедшие проверку
- `only()` / `except()` / `all()` — выборка из `filteredData`

## filteredData

`filteredData()` безопасно использовать для массового присваивания: в него попадают
только поля, для которых заданы правила (включая вложенные и wildcard‑пути), и только
если проверка этих полей прошла.

- Поля без правил в результат не попадают.
- Поле с ошибкой не попадает; не попадают и все вложенные в него поля.
- Если для массива заданы правила вложенных путей (`items` и `items.*.id`), массив
  не копируется целиком — в результат попадают только проверенные вложенные поля,
  лишние ключи элементов отбрасываются.
- Массив без правил для вложенных путей (`tags` → `ArrayValidation`) попадает целиком.
- Пустое значение `nullable`‑поля (`null`, `''`, `[]`) считается валидным и попадает в результат.
- Непереданные необязательные поля в результат не попадают.
- Поля, исключённые через `ExcludeValidation`, не попадают (вместе с вложенными).

```php
$result = $validator->validate(
    data: [
        'items'    => [['id' => 'x', 'owner_id' => 5], ['id' => 2, 'owner_id' => 6]],
        'is_admin' => true,
    ],
    rules: [
        'items'      => [new ArrayValidation()],
        'items.*.id' => [new IntValidation()],
    ],
);

$result->filteredData(); // ['items' => [1 => ['id' => 2]]]
```

## Режимы остановки

```php
use PhpSoftBox\Validator\ValidationOptions;
use PhpSoftBox\Validator\ValidationStopMode;

$options = new ValidationOptions(stopMode: ValidationStopMode::FIRST_PER_FIELD);
```

Доступные режимы:
- `FIRST_ERROR` — остановка при первой ошибке
- `FIRST_PER_FIELD` — первая ошибка на поле
- `ALL` — собрать все ошибки

Независимо от режима, если обязательное поле отсутствует или пустое, возвращается только
ошибка `required*`: остальные правила поля не выполняются.

## Контекст

`context` передается в callback‑правила, например `excludeIf` или `requiredIf`.
Если `context` не задан, callback получает массив `data`.

В собственных правилах (наследниках `AbstractRule`) данные и контекст доступны через
`context()` и `route()` только во время вызова `validate()`: валидатор устанавливает их
перед проверкой (`setRuntimeState()`) и сбрасывает сразу после (`resetRuntimeState()`),
чтобы экземпляр правила не удерживал payload запроса в долгоживущих воркерах.
