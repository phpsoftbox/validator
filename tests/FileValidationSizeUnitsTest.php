<?php

declare(strict_types=1);

namespace PhpSoftBox\Validator\Tests;

use InvalidArgumentException;
use PhpSoftBox\Http\Message\StreamFactory;
use PhpSoftBox\Http\Message\UploadedFile;
use PhpSoftBox\Validator\Rule\FileValidation;
use PhpSoftBox\Validator\Validator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function str_repeat;

#[CoversClass(FileValidation::class)]
#[CoversMethod(FileValidation::class, 'max')]
#[CoversMethod(FileValidation::class, 'size')]
final class FileValidationSizeUnitsTest extends TestCase
{
    /**
     * Проверим, что единица tb переводится в байты.
     *
     * @see FileValidation::size()
     */
    #[Test]
    public function supportsTerabytes(): void
    {
        $validator = new Validator();

        $result = $validator->validate(
            ['file' => $this->file(10)],
            ['file' => [new FileValidation()->size('1tb')]],
        );

        self::assertSame(
            ['Размер файла file должен быть 1099511627776 байт.'],
            $result->errorBag()->get('file'),
        );
    }

    /**
     * Проверим, что числовая строка без единицы трактуется как килобайты.
     *
     * @see FileValidation::max()
     */
    #[Test]
    public function numericStringIsKilobytes(): void
    {
        $validator = new Validator();

        $result = $validator->validate(
            ['file' => $this->file(3000)],
            ['file' => [new FileValidation()->max('2')]],
        );

        self::assertSame(
            ['Размер файла file не должен превышать 2048 байт.'],
            $result->errorBag()->get('file'),
        );
    }

    /**
     * Проверим, что неизвестная единица размера приводит к исключению, а не молча трактуется как килобайты.
     *
     * @see FileValidation::max()
     */
    #[Test]
    public function unknownUnitThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new FileValidation()->max('2pb');
    }

    private function file(int $bytes): UploadedFile
    {
        $stream = new StreamFactory()->createStream(str_repeat('a', $bytes));

        return new UploadedFile($stream, $bytes, clientFilename: 'file.bin');
    }
}
