<?php

declare(strict_types=1);

namespace PhpSoftBox\Validator\Rule;

use PhpSoftBox\Validator\ValidationEnum;
use PhpSoftBox\Validator\ValidationViolation;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UploadedFileInterface;

use function abs;
use function array_filter;
use function array_map;
use function array_unique;
use function explode;
use function extension_loaded;
use function file_exists;
use function filesize;
use function finfo_buffer;
use function finfo_close;
use function finfo_open;
use function function_exists;
use function getimagesizefromstring;
use function in_array;
use function is_numeric;
use function is_string;
use function mb_detect_encoding;
use function pathinfo;
use function preg_match;
use function round;
use function strlen;
use function strtolower;
use function trim;
use function version_compare;

use const FILEINFO_EXTENSION;
use const FILEINFO_MIME_ENCODING;
use const FILEINFO_MIME_TYPE;
use const PATHINFO_EXTENSION;
use const PHP_VERSION;
use const UPLOAD_ERR_OK;

/**
 * Проверяет загруженные файлы: размер, тип, расширение, кодировки, изображения.
 */
final class FileValidation extends AbstractRule
{
    /**
     * Требовать валидный UploadedFile.
     */
    private bool $file = false;
    /**
     * Требовать изображение.
     */
    private bool $image = false;
    /**
     * Максимальный размер в байтах.
     */
    private ?int $maxBytes = null;
    /**
     * Точный размер в байтах.
     */
    private ?int $sizeBytes = null;
    /**
     * Диапазон размера в байтах.
     *
     * @var array{min: int, max: int}|null
     */
    private ?array $betweenBytes = null;
    /**
     * Разрешенные расширения.
     *
     * @var array<int, string>|null
     */
    private ?array $extensions = null;
    /**
     * Разрешенные MIME-типы.
     *
     * @var array<int, string>|null
     */
    private ?array $mimeTypes = null;
    /**
     * Разрешенные расширения, сопоставляемые по MIME.
     *
     * @var array<int, string>|null
     */
    private ?array $mimeTypeByExtension = null;
    /**
     * Разрешенные кодировки.
     *
     * @var array<int, string>|null
     */
    private ?array $encodings = null;
    /**
     * Ограничения размеров изображения.
     *
     * @var array{width?: int, height?: int, minWidth?: int, maxWidth?: int, minHeight?: int, maxHeight?: int, ratio?: float}|null
     */
    private ?array $dimensions = null;

    /**
     * Значение должно быть успешно загруженным файлом.
     */
    public function file(): self
    {
        $this->file = true;

        return $this;
    }

    /**
     * Значение должно быть изображением.
     */
    public function image(): self
    {
        $this->image = true;

        return $this;
    }

    /**
     * Максимальный размер файла.
     */
    public function max(int|float|string $size): self
    {
        $this->maxBytes = $this->toBytes($size);

        return $this;
    }

    /**
     * Точный размер файла.
     */
    public function size(int|float|string $size): self
    {
        $this->sizeBytes = $this->toBytes($size);

        return $this;
    }

    /**
     * Размер файла должен быть в диапазоне.
     */
    public function between(int|float|string $min, int|float|string $max): self
    {
        $this->betweenBytes = ['min' => $this->toBytes($min), 'max' => $this->toBytes($max)];

        return $this;
    }

    /**
     * Допустимые расширения файла.
     */
    public function extensions(string ...$extensions): self
    {
        $this->extensions = $this->normalizeList($extensions);

        return $this;
    }

    /**
     * Допустимые MIME типы.
     */
    public function mimeTypes(string ...$types): self
    {
        $this->mimeTypes = $this->normalizeList($types);

        return $this;
    }

    /**
     * Допустимые расширения по определенному MIME типу.
     */
    public function mimeTypeByExtension(string ...$extensions): self
    {
        $this->mimeTypeByExtension = $this->normalizeList($extensions);

        return $this;
    }

    /**
     * Допустимые кодировки файла.
     */
    public function encoding(string ...$encodings): self
    {
        $this->encodings = $this->normalizeList($encodings);

        return $this;
    }

    /**
     * Ограничения для размеров изображения.
     */
    public function dimensions(
        ?int $width = null,
        ?int $height = null,
        ?int $minWidth = null,
        ?int $maxWidth = null,
        ?int $minHeight = null,
        ?int $maxHeight = null,
        ?float $ratio = null,
    ): self {
        $this->dimensions = array_filter([
            'width'     => $width,
            'height'    => $height,
            'minWidth'  => $minWidth,
            'maxWidth'  => $maxWidth,
            'minHeight' => $minHeight,
            'maxHeight' => $maxHeight,
            'ratio'     => $ratio,
        ], static fn (mixed $value): bool => $value !== null);

        return $this;
    }

    public function validate(mixed $value, string $field, bool $present, array $data): array
    {
        $violations = [];

        if (!$value instanceof UploadedFileInterface || $value->getError() !== UPLOAD_ERR_OK) {
            if ($this->needsFileValidation()) {
                $violations[] = new ValidationViolation(ValidationEnum::FILE->value);
            }

            return $violations;
        }

        $size = $this->fileSize($value);

        if ($this->maxBytes !== null && ($size === null || $size > $this->maxBytes)) {
            $violations[] = new ValidationViolation(ValidationEnum::MAX->value, ['max' => $this->maxBytes]);
        }

        if ($this->sizeBytes !== null && ($size === null || $size !== $this->sizeBytes)) {
            $violations[] = new ValidationViolation(ValidationEnum::SIZE->value, ['size' => $this->sizeBytes]);
        }

        if ($this->betweenBytes !== null) {
            $min = $this->betweenBytes['min'];
            $max = $this->betweenBytes['max'];
            if ($size === null || $size < $min || $size > $max) {
                $violations[] = new ValidationViolation(ValidationEnum::BETWEEN->value, [
                    'min' => $min,
                    'max' => $max,
                ]);
            }
        }

        if ($this->extensions !== null) {
            $extension = $this->clientExtension($value);
            if ($extension === null || !in_array($extension, $this->extensions, true)) {
                $violations[] = new ValidationViolation(ValidationEnum::EXTENSIONS->value, ['values' => $this->extensions]);
            }
        }

        if ($this->mimeTypes !== null) {
            $mime = $this->detectMimeType($value);
            if ($mime === null || !in_array($mime, $this->mimeTypes, true)) {
                $violations[] = new ValidationViolation(ValidationEnum::MIME_TYPES->value, ['values' => $this->mimeTypes]);
            }
        }

        if ($this->mimeTypeByExtension !== null) {
            $extensions = $this->detectExtensionsByMime($value);
            if ($extensions === [] || !$this->hasAny($extensions, $this->mimeTypeByExtension)) {
                $violations[] = new ValidationViolation(ValidationEnum::MIME_TYPE_BY_EXTENSION->value, [
                    'values' => $this->mimeTypeByExtension,
                ]);
            }
        }

        if ($this->encodings !== null) {
            $encoding = $this->detectEncoding($value);
            if ($encoding === null || !in_array($encoding, $this->encodings, true)) {
                $violations[] = new ValidationViolation(ValidationEnum::ENCODING->value, ['values' => $this->encodings]);
            }
        }

        if ($this->image || $this->dimensions !== null) {
            $image = $this->getImageSize($value);
            if ($image === null) {
                $violations[] = new ValidationViolation(
                    $this->image ? ValidationEnum::IMAGE->value : ValidationEnum::DIMENSIONS->value,
                );
            } else {
                [$width, $height] = $image;
                if ($this->dimensions !== null && !$this->matchDimensions($width, $height, $this->dimensions)) {
                    $violations[] = new ValidationViolation(ValidationEnum::DIMENSIONS->value, [
                        'width'      => $this->dimensions['width'] ?? null,
                        'height'     => $this->dimensions['height'] ?? null,
                        'min_width'  => $this->dimensions['minWidth'] ?? null,
                        'max_width'  => $this->dimensions['maxWidth'] ?? null,
                        'min_height' => $this->dimensions['minHeight'] ?? null,
                        'max_height' => $this->dimensions['maxHeight'] ?? null,
                        'ratio'      => $this->dimensions['ratio'] ?? null,
                    ]);
                }
            }
        }

        return $violations;
    }

    public function messages(): array
    {
        return [
            ValidationEnum::FILE->value                   => 'Поле {field} должно быть загруженным файлом.',
            ValidationEnum::IMAGE->value                  => 'Поле {field} должно быть изображением.',
            ValidationEnum::MAX->value                    => 'Размер файла {field} не должен превышать {max} байт.',
            ValidationEnum::SIZE->value                   => 'Размер файла {field} должен быть {size} байт.',
            ValidationEnum::BETWEEN->value                => 'Размер файла {field} должен быть между {min} и {max} байт.',
            ValidationEnum::EXTENSIONS->value             => 'Файл {field} должен иметь расширение из {values}.',
            ValidationEnum::MIME_TYPES->value             => 'Файл {field} должен иметь MIME тип из {values}.',
            ValidationEnum::MIME_TYPE_BY_EXTENSION->value => 'Файл {field} должен иметь расширение из {values}.',
            ValidationEnum::ENCODING->value               => 'Файл {field} должен иметь кодировку из {values}.',
            ValidationEnum::DIMENSIONS->value             => 'Изображение {field} имеет недопустимые размеры.',
        ];
    }

    private function needsFileValidation(): bool
    {
        return $this->file
            || $this->image
            || $this->maxBytes !== null
            || $this->sizeBytes !== null
            || $this->betweenBytes !== null
            || $this->extensions !== null
            || $this->mimeTypes !== null
            || $this->mimeTypeByExtension !== null
            || $this->encodings !== null
            || $this->dimensions !== null;
    }

    /**
     * @param array<int, string> $values
     * @return array<int, string>
     */
    private function normalizeList(array $values): array
    {
        $normalized = array_map(static fn (string $value): string => strtolower(trim($value)), $values);

        return array_unique($normalized);
    }

    private function toBytes(int|float|string $size): int
    {
        if (is_string($size)) {
            $match = [];
            if (!preg_match('/^(\d+(?:\.\d+)?)\s*(b|kb|mb|gb)$/i', trim($size), $match)) {
                return (int) round((float) $size * 1024);
            }

            $value = (float) $match[1];
            $unit  = strtolower($match[2]);

            return (int) round($value * $this->unitMultiplier($unit));
        }

        if (is_numeric($size)) {
            return (int) round((float) $size * 1024);
        }

        return (int) $size;
    }

    private function unitMultiplier(string $unit): int
    {
        return match ($unit) {
            'b'     => 1,
            'kb'    => 1024,
            'mb'    => 1024 * 1024,
            'gb'    => 1024 * 1024 * 1024,
            'tb'    => 1024 * 1024 * 1024 * 1024,
            default => 1024,
        };
    }

    private function fileSize(UploadedFileInterface $file): ?int
    {
        $size = $file->getSize();
        if ($size !== null) {
            return (int) $size;
        }

        $stream = $file->getStream();
        $meta   = $stream->getMetadata();
        if (isset($meta['uri']) && is_string($meta['uri']) && $meta['uri'] !== '') {
            if (is_string($meta['uri']) && file_exists($meta['uri'])) {
                return (int) filesize($meta['uri']);
            }
        }

        $content = $this->readStream($stream);

        return $content === '' ? null : strlen($content);
    }

    private function clientExtension(UploadedFileInterface $file): ?string
    {
        $name = $file->getClientFilename();
        if ($name === null || $name === '') {
            return null;
        }

        $extension = pathinfo($name, PATHINFO_EXTENSION);
        if ($extension === '') {
            return null;
        }

        return strtolower($extension);
    }

    private function detectMimeType(UploadedFileInterface $file): ?string
    {
        $shouldCloseInfo = version_compare(PHP_VERSION, '8.5.0', '<');
        $mime            = null;
        if (extension_loaded('fileinfo')) {
            $info = finfo_open(FILEINFO_MIME_TYPE);
            if ($info !== false) {
                $content = $this->readStream($file->getStream());
                if ($content !== '') {
                    $result = finfo_buffer($info, $content);
                    if (is_string($result) && $result !== '') {
                        $mime = strtolower($result);
                    }
                }
                if ($shouldCloseInfo) {
                    finfo_close($info);
                }
            }
        }

        if ($mime === null) {
            $client = $file->getClientMediaType();
            if ($client !== null && $client !== '') {
                $mime = strtolower($client);
            }
        }

        return $mime;
    }

    /**
     * @return array<int, string>
     */
    private function detectExtensionsByMime(UploadedFileInterface $file): array
    {
        $shouldCloseInfo = version_compare(PHP_VERSION, '8.5.0', '<');
        if (extension_loaded('fileinfo')) {
            $info = finfo_open(FILEINFO_EXTENSION);
            if ($info !== false) {
                $content = $this->readStream($file->getStream());
                if ($content !== '') {
                    $result = finfo_buffer($info, $content);
                    if (is_string($result) && $result !== '') {
                        $extensions = $this->splitExtensions($result);
                        if ($extensions !== []) {
                            if ($shouldCloseInfo) {
                                finfo_close($info);
                            }

                            return $extensions;
                        }
                    }
                }
                if ($shouldCloseInfo) {
                    finfo_close($info);
                }
            }
        }

        $client = $this->clientExtension($file);

        return $client === null ? [] : [$client];
    }

    private function detectEncoding(UploadedFileInterface $file): ?string
    {
        $shouldCloseInfo = version_compare(PHP_VERSION, '8.5.0', '<');
        if (extension_loaded('fileinfo')) {
            $info = finfo_open(FILEINFO_MIME_ENCODING);
            if ($info !== false) {
                $content = $this->readStream($file->getStream());
                if ($content !== '') {
                    $result = finfo_buffer($info, $content);
                    if (is_string($result) && $result !== '') {
                        $encoding = strtolower($result);
                        if ($shouldCloseInfo) {
                            finfo_close($info);
                        }

                        return $encoding;
                    }
                }
                if ($shouldCloseInfo) {
                    finfo_close($info);
                }
            }
        }

        if (function_exists('mb_detect_encoding')) {
            $content = $this->readStream($file->getStream());
            if ($content !== '') {
                $encoding = mb_detect_encoding($content, null, true);
                if (is_string($encoding) && $encoding !== '') {
                    return strtolower($encoding);
                }
            }
        }

        return null;
    }

    /**
     * @return array{0: int, 1: int}|null
     */
    private function getImageSize(UploadedFileInterface $file): ?array
    {
        $content = $this->readStream($file->getStream());
        if ($content === '') {
            return null;
        }

        $size = @getimagesizefromstring($content);
        if ($size === false || !isset($size[0], $size[1])) {
            return null;
        }

        return [(int) $size[0], (int) $size[1]];
    }

    /**
     * @param array{width?: int, height?: int, minWidth?: int, maxWidth?: int, minHeight?: int, maxHeight?: int, ratio?: float} $rules
     */
    private function matchDimensions(int $width, int $height, array $rules): bool
    {
        if (isset($rules['width']) && $width !== $rules['width']) {
            return false;
        }

        if (isset($rules['height']) && $height !== $rules['height']) {
            return false;
        }

        if (isset($rules['minWidth']) && $width < $rules['minWidth']) {
            return false;
        }

        if (isset($rules['maxWidth']) && $width > $rules['maxWidth']) {
            return false;
        }

        if (isset($rules['minHeight']) && $height < $rules['minHeight']) {
            return false;
        }

        if (isset($rules['maxHeight']) && $height > $rules['maxHeight']) {
            return false;
        }

        if (isset($rules['ratio']) && $height !== 0) {
            $ratio = $width / $height;
            if (abs($ratio - $rules['ratio']) > 0.00001) {
                return false;
            }
        }

        return true;
    }

    private function readStream(StreamInterface $stream): string
    {
        if ($stream->isSeekable()) {
            $stream->rewind();
        }

        $content = $stream->getContents();

        if ($stream->isSeekable()) {
            $stream->rewind();
        }

        return $content;
    }

    /**
     * @param array<int, string> $haystack
     * @param array<int, string> $needles
     */
    private function hasAny(array $haystack, array $needles): bool
    {
        foreach ($needles as $needle) {
            if (in_array($needle, $haystack, true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array<int, string>
     */
    private function splitExtensions(string $extensions): array
    {
        $extensions = strtolower(trim($extensions));
        if ($extensions === '') {
            return [];
        }

        $chunks = explode('/', $extensions);
        $result = [];
        foreach ($chunks as $chunk) {
            $chunk = trim($chunk);
            if ($chunk !== '') {
                $result[] = $chunk;
            }
        }

        return array_unique($result);
    }
}
