# Пути данных и wildcard

В правилах и атрибутах используются dot‑пути:

```php
$rules = [
    'user.name' => [...],
];
```

## Wildcard

Можно использовать `*` для выборки массивов:

```php
$rules = [
    'items.*.name' => [...],
];
```

При wildcard:
- валидатор проверяет все совпавшие пути;
- ошибки и `filteredData` формируются по конкретным путям.

Если заданы правила и для массива, и для его вложенных путей, в `filteredData`
попадают только проверенные вложенные поля без ошибок:

```php
$rules = [
    'items'        => [new ArrayValidation()],
    'items.*.id'   => [new IntValidation()],
    'items.*.name' => [new StringValidation()],
];
// Ключи элементов items без правил (например, owner_id) в filteredData не попадут,
// а при ошибке в items.0.id не попадёт только items.0.id.
```

Пути также используются в правилах `same`, `different`, `confirmed` и в условных правилах.
