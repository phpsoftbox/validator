# Миграция на Specification + Executor (breaking)

> DB-specific `ExistsValidation`, `UniqueValidation` и их executors перенесены в компонент
> `phpsoftbox/validator-db` и namespace `PhpSoftBox\Validator\Db\Rule`.

## Цель

Перевести валидацию на модель:
- `Rule` = декларация (specification)
- `Executor` = исполнение правила

Итог:
- `RequestSchema` и правила не зависят от инфраструктуры (БД, ORM, внешние адаптеры).
- Инфраструктурные зависимости находятся только в executor-слое и DI.
- Конструкторы `RequestSchema` упрощаются.

## Что ломаем

Переход делаем как полный breaking-change:
- Удаляем паттерн, где правило хранит adapter/service внутри себя.
- `Validator` перестает вызывать `rule->validate(...)` напрямую.
- Вводим executor-пайплайн как единственный механизм выполнения правил.

## Целевая архитектура

### 1) Спецификация правила

Правило хранит только декларативные параметры и сообщения.

Пример для `ExistsValidation`:
- table/column/columns/connection
- `messages()` и кастомные сообщения
- `required/nullable/exclude` механика остается на уровне правила

Без adapter в конструкторе.

### 2) Executor правила

Каждое исполняемое правило имеет отдельный executor:
- `ExistsRuleExecutor`
- `UniqueRuleExecutor`
- и т.д.

Executor получает infra-зависимости через DI.

### 3) Registry executor-ов

`RuleExecutorRegistry` сопоставляет `RuleClass -> Executor`.

`Validator`:
- проходит по rules,
- берет executor из registry,
- вызывает executor и получает `ValidationViolation[]`.

## Пошаговый план миграции

### Шаг 1. Новые контракты в `phpsoftbox/validator`

Добавить:
- `RuleSpecificationInterface` (базовый контракт декларативного правила)
- `RuleExecutorInterface` (контракт выполнения правила)
- `RuleExecutorRegistryInterface`
- `RuleExecutionContext` (если нужен общий контекст выполнения)

Решение по старому `ValidationRuleInterface`:
- удалить или сузить до метаданных (`required`, `nullable`, `messages`, `customMessages`, `requiredViolation`, `shouldExclude`).

### Шаг 2. Новый runtime в `Validator`

Переписать цикл в `Validator`:
- убрать прямой вызов `rule->validate(...)`,
- вызывать `executorRegistry->resolve($rule)->validate(...)`.

Сигнатура executor-а:
```php
public function validate(
    RuleSpecificationInterface $rule,
    mixed $value,
    string $field,
    bool $present,
    array $data,
): array;
```

### Шаг 3. Вынести DB-исполнение в `phpsoftbox/validator-db`

Рекомендуемое разделение:
- `validator`: базовые правила и runtime
- `validator-db`: DB-спецификации + DB-executor-ы + DB-контракты

Минимум для DB:
- `ExistsValidation` (spec)
- `UniqueValidation` (spec)
- `ExistsRuleExecutor`
- `UniqueRuleExecutor`
- `DatabaseValidationAdapterInterface` (уже есть)

### Шаг 4. DI-конфигурация

Сконфигурировать registry и executor-ы в приложении:
- зарегистрировать DB adapter
- зарегистрировать DB executor-ы
- зарегистрировать `RuleExecutorRegistryInterface`
- зарегистрировать `Validator` с registry

### Шаг 5. Миграция правил в коде приложений

До:
```php
(new ExistsValidation($dbValidation))->table('invites')->column('token')
```

После:
```php
ExistsValidation::make()->table('invites')->column('token')
```

Важное следствие:
- из `RequestSchema` убрать infra-зависимости, нужные только для правил.

### Шаг 6. Миграция `RequestSchema` и `Action`

После перехода на декларативные rules:
- упростить конструкторы `RequestSchema`,
- удалить прокидывание adapter-ов через `Action` в `RequestSchema`,
- оставить в `Action` только `new Schema($request)` или фабрику схем.

### Шаг 7. Удаление legacy API

После перевода проектов:
- удалить старые конструкторы rule-объектов с adapter-ами,
- удалить старые helper-методы/ветки в `Validator`,
- удалить тесты legacy-пути.

## Чеклист по пакетам

### `packages/Validator`
- [x] новые контракты specification/executor/registry
- [x] новый `Validator` runtime через registry
- [x] обновленные unit-тесты `Validator`
- [x] обновленная документация usage

### `packages/ValidatorDb`
- [x] декларативные `ExistsValidation` и `UniqueValidation`
- [x] executor-ы для DB-правил
- [x] тесты executor-ов (adapter mock/fake)
- [x] интеграционные тесты связки validator + validator-db

### Приложения (`chegdesklad`, второй проект)
- [ ] обновить все rules в `RequestSchema`
- [ ] убрать infra-зависимости из `RequestSchema` конструкторов
- [ ] упростить `Action`/factory wiring
- [ ] обновить unit/integration-тесты

## Тестовая стратегия

Проверять в три слоя:
- Unit specification: только декларативные параметры и сообщения
- Unit executor: корректная бизнес-логика выполнения правила
- Integration validator runtime: правило + registry + executor + ошибки/сообщения

Минимальный smoke-набор:
- `exists`: found/not found
- `unique`: unique/not unique/ignoreColumn
- `bail`: остановка после первой ошибки
- `messages`: custom + default шаблоны

## Рекомендуемый порядок внедрения

1. Сделать runtime и контракты в `validator`.
2. Перенести `exists/unique` в `validator-db`.
3. Перевести `chegdesklad`.
4. Перевести второй проект.
5. Удалить legacy-код.

## Риски

- Одновременная миграция контрактов и runtime затрагивает все правила.
- Ошибки в registry приведут к `rule has no executor`.
- Потребуется внимательно обновить сообщения ошибок и снапшоты тестов.

Снижение риска:
- идти small-steps по пакетам,
- прогонять полный unit+integration после каждого шага,
- держать временный feature-branch без смешивания с другими рефакторингами.
