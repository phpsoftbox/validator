<?php

declare(strict_types=1);

namespace PhpSoftBox\Validator\Tests;

use PhpSoftBox\Validator\Rule\FileValidation;
use PhpSoftBox\Validator\Validator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UploadedFileInterface;
use RuntimeException;

use function base64_decode;
use function fclose;
use function feof;
use function fopen;
use function fread;
use function fseek;
use function fstat;
use function ftell;
use function fwrite;
use function rewind;
use function str_contains;
use function str_repeat;
use function stream_get_contents;
use function stream_get_meta_data;

use const SEEK_SET;
use const UPLOAD_ERR_OK;

#[CoversClass(Validator::class)]
#[CoversClass(FileValidation::class)]
final class ValidatorFileRulesTest extends TestCase
{
    /**
     * Проверяет правило file.
     */
    #[Test]
    public function fileRuleRequiresUploadedFile(): void
    {
        $validator = new Validator();
        $rules     = [
            'file' => [new FileValidation()->file()],
        ];

        $result = $validator->validate(['file' => 'not-a-file'], $rules);

        self::assertSame(['Поле file должно быть загруженным файлом.'], $result->errorBag()->get('file'));
    }

    /**
     * Проверяет ограничения размера файла.
     */
    #[Test]
    public function fileSizeRules(): void
    {
        $validator = new Validator();
        $rules     = [
            'file' => [new FileValidation()->max(1)->size(1)->between(3, 4)],
        ];

        $file   = TestUploadedFile::fromString(str_repeat('a', 2048));
        $result = $validator->validate(['file' => $file], $rules);

        self::assertSame(
            [
                'Размер файла file не должен превышать 1024 байт.',
                'Размер файла file должен быть 1024 байт.',
                'Размер файла file должен быть между 3072 и 4096 байт.',
            ],
            $result->errorBag()->get('file'),
        );
    }

    /**
     * Проверяет правило extensions.
     */
    #[Test]
    public function extensionsRule(): void
    {
        $validator = new Validator();
        $rules     = [
            'file' => [new FileValidation()->extensions('jpg')],
        ];

        $file   = TestUploadedFile::fromString('data', 'photo.png');
        $result = $validator->validate(['file' => $file], $rules);

        self::assertSame(['Файл file должен иметь расширение из ["jpg"].'], $result->errorBag()->get('file'));
    }

    /**
     * Проверяет правило mime_types.
     */
    #[Test]
    public function mimeTypesRule(): void
    {
        $validator = new Validator();
        $rules     = [
            'file' => [new FileValidation()->mimeTypes('image/png')],
        ];

        $file   = TestUploadedFile::fromString('data', 'photo.txt', 'text/plain');
        $result = $validator->validate(['file' => $file], $rules);

        self::assertSame(['Файл file должен иметь MIME тип из ["image/png"].'], $result->errorBag()->get('file'));
    }

    /**
     * Проверяет правило mime_type_by_extension.
     */
    #[Test]
    public function mimeTypeByExtensionRule(): void
    {
        $validator = new Validator();
        $rules     = [
            'file' => [new FileValidation()->mimeTypeByExtension('jpg')],
        ];

        $file   = TestUploadedFile::fromString(self::pngData(), 'image.png');
        $result = $validator->validate(['file' => $file], $rules);

        self::assertSame(['Файл file должен иметь расширение из ["jpg"].'], $result->errorBag()->get('file'));
    }

    /**
     * Проверяет правило image.
     */
    #[Test]
    public function imageRule(): void
    {
        $validator = new Validator();
        $rules     = [
            'file' => [new FileValidation()->image()],
        ];

        $file   = TestUploadedFile::fromString('not-image');
        $result = $validator->validate(['file' => $file], $rules);

        self::assertSame(['Поле file должно быть изображением.'], $result->errorBag()->get('file'));
    }

    /**
     * Проверяет правило dimensions.
     */
    #[Test]
    public function dimensionsRule(): void
    {
        $validator = new Validator();
        $rules     = [
            'file' => [new FileValidation()->dimensions(minWidth: 2, minHeight: 2)],
        ];

        $file   = TestUploadedFile::fromString(self::pngData());
        $result = $validator->validate(['file' => $file], $rules);

        self::assertSame(['Изображение file имеет недопустимые размеры.'], $result->errorBag()->get('file'));
    }

    /**
     * Проверяет правило encoding.
     */
    #[Test]
    public function encodingRule(): void
    {
        $validator = new Validator();
        $rules     = [
            'file' => [new FileValidation()->encoding('utf-16')],
        ];

        $file   = TestUploadedFile::fromString('hello');
        $result = $validator->validate(['file' => $file], $rules);

        self::assertSame(['Файл file должен иметь кодировку из ["utf-16"].'], $result->errorBag()->get('file'));
    }

    private static function pngData(): string
    {
        return base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/wwAAgMBgLwVtWQAAAAASUVORK5CYII=');
    }
}

final class TestUploadedFile implements UploadedFileInterface
{
    private StreamInterface $stream;
    private ?int $size;
    private int $error;
    private ?string $clientFilename;
    private ?string $clientMediaType;

    public function __construct(StreamInterface $stream, ?int $size, int $error, ?string $clientFilename, ?string $clientMediaType)
    {
        $this->stream          = $stream;
        $this->size            = $size;
        $this->error           = $error;
        $this->clientFilename  = $clientFilename;
        $this->clientMediaType = $clientMediaType;
    }

    public static function fromString(string $content, ?string $clientFilename = 'file.bin', ?string $clientMediaType = null): self
    {
        $stream = TestStream::fromString($content);

        return new self($stream, $stream->getSize(), UPLOAD_ERR_OK, $clientFilename, $clientMediaType);
    }

    public function getStream(): StreamInterface
    {
        return $this->stream;
    }

    public function moveTo(string $targetPath): void
    {
        throw new RuntimeException('Not implemented.');
    }

    public function getSize(): ?int
    {
        return $this->size;
    }

    public function getError(): int
    {
        return $this->error;
    }

    public function getClientFilename(): ?string
    {
        return $this->clientFilename;
    }

    public function getClientMediaType(): ?string
    {
        return $this->clientMediaType;
    }
}

final class TestStream implements StreamInterface
{
    /**
     * @var resource|null
     */
    private $resource;

    private function __construct($resource)
    {
        $this->resource = $resource;
    }

    public static function fromString(string $content): self
    {
        $resource = fopen('php://temp', 'r+');
        fwrite($resource, $content);
        rewind($resource);

        return new self($resource);
    }

    public function __toString(): string
    {
        if ($this->resource === null) {
            return '';
        }

        $this->rewind();
        $content = stream_get_contents($this->resource);
        $this->rewind();

        return $content === false ? '' : $content;
    }

    public function close(): void
    {
        if ($this->resource !== null) {
            fclose($this->resource);
            $this->resource = null;
        }
    }

    public function detach()
    {
        $resource       = $this->resource;
        $this->resource = null;

        return $resource;
    }

    public function getSize(): ?int
    {
        if ($this->resource === null) {
            return null;
        }

        $stats = fstat($this->resource);

        return $stats['size'] ?? null;
    }

    public function tell(): int
    {
        if ($this->resource === null) {
            throw new RuntimeException('No resource.');
        }

        $position = ftell($this->resource);
        if ($position === false) {
            throw new RuntimeException('Cannot tell position.');
        }

        return $position;
    }

    public function eof(): bool
    {
        return $this->resource === null || feof($this->resource);
    }

    public function isSeekable(): bool
    {
        if ($this->resource === null) {
            return false;
        }

        $meta = stream_get_meta_data($this->resource);

        return (bool) ($meta['seekable'] ?? false);
    }

    public function seek(int $offset, int $whence = SEEK_SET): void
    {
        if ($this->resource === null) {
            throw new RuntimeException('No resource.');
        }

        fseek($this->resource, $offset, $whence);
    }

    public function rewind(): void
    {
        $this->seek(0);
    }

    public function isWritable(): bool
    {
        if ($this->resource === null) {
            return false;
        }

        $meta = stream_get_meta_data($this->resource);

        return str_contains($meta['mode'] ?? '', 'w') || str_contains($meta['mode'] ?? '', '+');
    }

    public function write(string $string): int
    {
        if ($this->resource === null) {
            throw new RuntimeException('No resource.');
        }

        $result = fwrite($this->resource, $string);
        if ($result === false) {
            throw new RuntimeException('Cannot write to stream.');
        }

        return $result;
    }

    public function isReadable(): bool
    {
        if ($this->resource === null) {
            return false;
        }

        $meta = stream_get_meta_data($this->resource);

        return str_contains($meta['mode'] ?? '', 'r') || str_contains($meta['mode'] ?? '', '+');
    }

    public function read(int $length): string
    {
        if ($this->resource === null) {
            throw new RuntimeException('No resource.');
        }

        $data = fread($this->resource, $length);
        if ($data === false) {
            throw new RuntimeException('Cannot read stream.');
        }

        return $data;
    }

    public function getContents(): string
    {
        if ($this->resource === null) {
            throw new RuntimeException('No resource.');
        }

        $data = stream_get_contents($this->resource);
        if ($data === false) {
            throw new RuntimeException('Cannot read stream.');
        }

        return $data;
    }

    public function getMetadata(?string $key = null): mixed
    {
        if ($this->resource === null) {
            return $key === null ? [] : null;
        }

        $meta = stream_get_meta_data($this->resource);

        if ($key === null) {
            return $meta;
        }

        return $meta[$key] ?? null;
    }
}
