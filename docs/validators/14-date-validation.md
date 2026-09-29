# DateValidation

Проверяет дату, сравнение дат и часовые пояса.

## Методы

### date
`date()` — значение должно быть корректной, **не относительной** датой (например, `2024-01-01`).
Относительные строки вроде `tomorrow` не проходят.

### dateFormat
`dateFormat(string ...$formats)` — значение должно соответствовать одному из форматов.
Форматы такие же, как в `DateTime::createFromFormat`.
Рекомендуется использовать либо `date`, либо `dateFormat`, но не вместе.

Строковое значение разбирается **только** по указанным форматам (`strtotime` не используется):
- незаданные в формате части даты и времени обнуляются (как с модификатором `!`),
  поэтому `Y-m-d` даёт полночь;
- значение не должно давать ошибок и предупреждений разбора (`31/02/2024` отклоняется)
  и должно совпадать с датой, отформатированной обратно по тому же формату;
- если ни один формат не подошёл, возвращается ошибка `date_format` (без `date`);
- сравнения (`after`, `before`, `dateEquals`…) выполняются с датой, разобранной по формату:
  `01/02/2024` с форматом `d/m/Y` — это 1 февраля, а не 2 января.

Объект `DateTimeInterface` при заданном `dateFormat` принимается как есть.

### dateEquals
`dateEquals(string|DateTimeInterface|int $date)` — значение равно дате.

### after / afterOrEqual
- `after(string|DateTimeInterface|int $date)`
- `afterOrEqual(string|DateTimeInterface|int $date)`

### before / beforeOrEqual
- `before(string|DateTimeInterface|int $date)`
- `beforeOrEqual(string|DateTimeInterface|int $date)`

Для `after/before` можно передавать строку даты, `DateTimeInterface`, timestamp (`int`) или имя другого поля.
Если значение параметра совпадает с полем в данных — используется значение этого поля;
при заданном `dateFormat` значение другого поля сначала разбирается по тем же форматам.
Сравнения выполняются по `strtotime`, поэтому допустимы относительные значения (`tomorrow`, `+2 days`).

### afterToday / todayOrAfter / beforeToday / todayOrBefore
Удобные методы для сравнений с текущей датой:
- `afterToday()` — после сегодняшнего дня
- `todayOrAfter()` — сегодня или после
- `beforeToday()` — до сегодняшнего дня
- `todayOrBefore()` — сегодня или до

### timezone
`timezone()` — значение должно быть допустимым идентификатором из `DateTimeZone::listIdentifiers()`.

### required / nullable
Доступны, см. required‑сценарии.

## Сообщения

Основные ключи:
`date`, `date_format`, `date_equals`, `after`, `after_or_equal`, `before`, `before_or_equal`, `timezone`.

## Пример

```php
use PhpSoftBox\Validator\Rule\DateValidation;

$rules = [
    'start_date' => [(new DateValidation())->date()->after('today')],
    'end_date' => [(new DateValidation())->date()->afterOrEqual('start_date')],
    'timezone' => [(new DateValidation())->timezone()],
];
```
