# FileValidation

Проверяет загруженные файлы (`UploadedFileInterface`).

## Методы

### file
`file()` — значение должно быть успешно загруженным файлом.

### image
`image()` — значение должно быть изображением.

### max / between / size
Размеры задаются в **килобайтах**, если передано число.
Можно передавать строку с единицами: `b`, `kb`, `mb`, `gb`, `tb`.
Сравнения выполняются в байтах.

- `max(int|float|string $size)`
- `between(int|float|string $min, int|float|string $max)`
- `size(int|float|string $size)`

### extensions
`extensions(string ...$extensions)` — список допустимых расширений (по имени файла).
Рекомендуется использовать вместе с `mimeTypes` или `mimeTypeByExtension`.

### mimeTypes
`mimeTypes(string ...$types)` — список допустимых MIME‑типов (по содержимому).

### mimeTypeByExtension
`mimeTypeByExtension(string ...$extensions)` — допустимые расширения по MIME‑типу (определяется по содержимому файла).

### encoding
`encoding(string ...$encodings)` — допустимые кодировки.

### dimensions
`dimensions(...)` — ограничения размеров изображения.

Доступные параметры (удобно использовать именованные аргументы):
- `width`, `height` — точные размеры
- `minWidth`, `maxWidth` — диапазон ширины
- `minHeight`, `maxHeight` — диапазон высоты
- `ratio` — соотношение сторон

### required / nullable
Доступны, см. required‑сценарии.

## Сообщения

Основные ключи:
`file`, `image`, `between`, `max`, `size`, `extensions`, `mime_types`,
`mime_type_by_extension`, `encoding`, `dimensions`.

## Пример

```php
use PhpSoftBox\Validator\Rule\FileValidation;

$rules = [
    'avatar' => [
        (new FileValidation())
            ->image()
            ->max('2mb')
            ->extensions('jpg', 'png')
            ->dimensions(minWidth: 200, minHeight: 200),
    ],
];
```
